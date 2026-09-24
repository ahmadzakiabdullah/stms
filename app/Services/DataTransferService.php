<?php

namespace App\Services;

use App\Exports\FixtureExport;
use App\Exports\MedalTallyExport;
use App\Exports\RankingExport;
use App\Exports\ResultExport;
use App\Imports\EventParticipantImport;
use App\Jobs\ProcessDataTransfer;
use App\Models\DataTransfer;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Result;
use App\Models\Session;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class DataTransferService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function queue(User $actor, string $type, array $payload = [], ?string $sourcePath = null, ?string $idempotencyKey = null): DataTransfer
    {
        $organizationId = $actor->organization_id;
        abort_unless($organizationId && $actor->is_active, 403);

        $transfer = DB::transaction(function () use ($actor, $organizationId, $type, $payload, $sourcePath, $idempotencyKey): DataTransfer {
            Organization::whereKey($organizationId)->lockForUpdate()->firstOrFail();

            if ($idempotencyKey !== null) {
                $existing = DataTransfer::forOrganization($organizationId)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($existing) {
                    if ($existing->requested_by !== $actor->uuid || $existing->type !== $type || $existing->payload != $payload) {
                        throw ValidationException::withMessages(['idempotency_key' => 'This key is already used by a different transfer.']);
                    }
                    Gate::forUser($actor)->authorize('view', $existing);
                    if ($sourcePath && $sourcePath !== $existing->source_path) {
                        Storage::disk('local')->delete($sourcePath);
                    }

                    return $existing;
                }
            }

            $transfer = DataTransfer::create([
                'organization_id' => $organizationId,
                'requested_by' => $actor->uuid,
                'type' => $type,
                'status' => DataTransfer::STATUS_PENDING,
                'payload' => $payload,
                'source_path' => $sourcePath,
                'idempotency_key' => $idempotencyKey,
            ]);

            Gate::forUser($actor)->authorize('view', $transfer);
            ProcessDataTransfer::dispatch($transfer->id, $organizationId)->afterCommit();

            return $transfer;
        });

        $fresh = $transfer->fresh();
        $fresh->wasRecentlyCreated = $transfer->wasRecentlyCreated;

        return $fresh;
    }

    public function process(string $transferId, string $organizationId): void
    {
        if (TenantContext::requireOrganization() !== $organizationId) {
            throw new \LogicException('Transfer tenant does not match the queue context.');
        }

        $transfer = DB::transaction(function () use ($transferId, $organizationId): ?DataTransfer {
            $transfer = DataTransfer::forOrganization($organizationId)
                ->whereKey($transferId)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($transfer->status, [
                DataTransfer::STATUS_COMPLETED,
                DataTransfer::STATUS_COMPLETED_WITH_ERRORS,
            ], true)) {
                return null;
            }

            $transfer->forceFill([
                'status' => DataTransfer::STATUS_RUNNING,
                'failure_report' => null,
            ])->save();

            return $transfer->fresh();
        });

        if (! $transfer) {
            return;
        }

        try {
            $actor = $transfer->requestedBy()->firstOrFail();
            Gate::forUser($actor)->authorize('view', $transfer);
            match ($transfer->type) {
                DataTransfer::TYPE_EXPORT_FIXTURES,
                DataTransfer::TYPE_EXPORT_RESULTS,
                DataTransfer::TYPE_EXPORT_RANKINGS,
                DataTransfer::TYPE_EXPORT_MEDALS => $this->processExport($transfer),
                DataTransfer::TYPE_IMPORT_PARTICIPANTS => $this->processParticipantImport($transfer),
                DataTransfer::TYPE_IMPORT_EVENT_PARTICIPANTS => $this->processEventParticipantImport($transfer),
                default => throw new \InvalidArgumentException('Unsupported data transfer type.'),
            };
        } catch (Throwable $exception) {
            $this->fail($transfer, $exception);

            throw $exception;
        }
    }

    /** @return array<string, mixed> */
    public function status(DataTransfer $transfer): array
    {
        return [
            'id' => $transfer->id,
            'type' => $transfer->type,
            'status' => $transfer->status,
            'progress' => $transfer->progress,
            'processed' => $transfer->processed,
            'total' => $transfer->total,
            'failure_report' => $transfer->failure_report ?? [],
            'download_url' => $transfer->status === DataTransfer::STATUS_COMPLETED && $transfer->output_path
                ? route('data-transfers.download', $transfer->id)
                : null,
        ];
    }

    private function processExport(DataTransfer $transfer): void
    {
        $payload = $transfer->payload ?? [];
        $organization = $transfer->organization()->firstOrFail();
        $eventId = $payload['event_id'] ?? null;
        $fileName = match ($transfer->type) {
            DataTransfer::TYPE_EXPORT_FIXTURES => 'fixtures-'.now()->format('Y-m-d').'.xlsx',
            DataTransfer::TYPE_EXPORT_RESULTS => 'results-'.now()->format('Y-m-d').'.xlsx',
            DataTransfer::TYPE_EXPORT_RANKINGS => 'rankings-'.($payload['tournament_id'] ?? 'tournament').'-'.now()->format('Y-m-d').'.xlsx',
            DataTransfer::TYPE_EXPORT_MEDALS => 'medal-tally-'.($payload['session_id'] ?? 'session').'-'.now()->format('Y-m-d').'.xlsx',
        };

        $total = match ($transfer->type) {
            DataTransfer::TYPE_EXPORT_FIXTURES => Fixture::forOrganization($organization->id)->when($eventId, fn ($q) => $q->where('event_id', $eventId))->count(),
            DataTransfer::TYPE_EXPORT_RESULTS => Result::forOrganization($organization->id)->when($eventId, fn ($q) => $q->whereHas('match', fn ($m) => $m->where('event_id', $eventId)))->count(),
            default => null,
        };
        $transfer->forceFill(['total' => $total, 'progress' => 10])->save();

        $export = match ($transfer->type) {
            DataTransfer::TYPE_EXPORT_FIXTURES => new FixtureExport($organization, $eventId),
            DataTransfer::TYPE_EXPORT_RESULTS => new ResultExport($organization, $eventId),
            DataTransfer::TYPE_EXPORT_RANKINGS => new RankingExport($organization, (string) $payload['tournament_id']),
            DataTransfer::TYPE_EXPORT_MEDALS => new MedalTallyExport($organization, (string) $payload['session_id']),
        };
        $path = 'transfers/'.$transfer->organization_id.'/output/'.$transfer->id.'/'.$fileName;

        if (! Excel::store($export, $path, 'local')) {
            throw new \RuntimeException('The export file could not be stored.');
        }

        $transfer->forceFill([
            'status' => DataTransfer::STATUS_COMPLETED,
            'progress' => 100,
            'processed' => $total ?? 0,
            'output_path' => $path,
            'output_name' => $fileName,
        ])->save();
    }

    private function processParticipantImport(DataTransfer $transfer): void
    {
        $rows = $this->sourceRows($transfer);
        $total = count($rows);
        $errors = [];
        $created = 0;
        $sessionId = $transfer->payload['session_id'] ?? null;
        if ($sessionId !== null) {
            Session::forOrganization($transfer->organization_id)->whereKey($sessionId)->firstOrFail();
        }

        foreach ($rows as $index => $row) {
            $data = $row['data'] ?? [];
            $rowNumber = $row['row_number'] ?? ($index + 2);

            try {
                $alreadyImported = Participant::forOrganization($transfer->organization_id)
                    ->withTrashed()
                    ->where('slug', $data['slug'] ?? '')
                    ->exists();

                if (! $alreadyImported) {
                    Participant::create([
                        ...Arr::only($data, ['name', 'slug', 'email', 'phone', 'participant_type', 'team_name', 'status', 'notes', 'is_active']),
                        'organization_id' => $transfer->organization_id,
                        'session_id' => $sessionId,
                    ]);
                    $created++;
                }
            } catch (Throwable $exception) {
                Log::warning('Queued participant import row failed', [
                    'transfer_id' => $transfer->id,
                    'row' => $rowNumber,
                    'error' => $exception->getMessage(),
                ]);
                $errors[] = 'Row '.$rowNumber.': participant could not be imported.';
            }

            $this->updateProgress($transfer, $index + 1, $total);
        }

        if ($errors !== []) {
            $this->completeWithErrors($transfer, $errors, $created, $total);

            return;
        }

        $this->complete($transfer, $created, $total);
    }

    private function processEventParticipantImport(DataTransfer $transfer): void
    {
        if (! $this->hasSource($transfer)) {
            throw new \RuntimeException('The import source file is missing.');
        }

        $participant = Participant::forOrganization($transfer->organization_id)
            ->whereKey($transfer->payload['participant_id'] ?? null)
            ->firstOrFail();
        $import = new EventParticipantImport($participant, $transfer->organization_id);
        Excel::import($import, Storage::disk('local')->path($transfer->source_path));
        $errors = $import->errors();
        $processed = $import->createdCount() + count($errors);

        if ($errors !== []) {
            $this->completeWithErrors($transfer, $errors, $processed, $processed);

            return;
        }

        $this->complete($transfer, $processed, $processed);
    }

    /** @return array<int, array<string, mixed>> */
    private function sourceRows(DataTransfer $transfer): array
    {
        if (! $this->hasSource($transfer)) {
            throw new \RuntimeException('The import source file is missing.');
        }

        $rows = json_decode(Storage::disk('local')->get($transfer->source_path), true);

        if (! is_array($rows)) {
            throw new \RuntimeException('The import source is invalid.');
        }

        return $rows;
    }

    private function updateProgress(DataTransfer $transfer, int $processed, int $total): void
    {
        $transfer->forceFill([
            'processed' => $processed,
            'total' => $total,
            'progress' => $total > 0 ? (int) floor(($processed / $total) * 100) : 100,
        ])->save();
    }

    private function complete(DataTransfer $transfer, int $processed, ?int $total): void
    {
        $transfer->forceFill([
            'status' => DataTransfer::STATUS_COMPLETED,
            'processed' => $processed,
            'total' => $total,
            'progress' => 100,
        ])->save();
        $this->cleanupSource($transfer);
    }

    /** @param array<int, string> $errors */
    private function completeWithErrors(DataTransfer $transfer, array $errors, int $processed = 0, ?int $total = null): void
    {
        $transfer->forceFill([
            'status' => DataTransfer::STATUS_COMPLETED_WITH_ERRORS,
            'processed' => $processed,
            'total' => $total,
            'progress' => 100,
            'failure_report' => $errors,
        ])->save();
        $this->cleanupSource($transfer);
    }

    private function fail(DataTransfer $transfer, Throwable $exception): void
    {
        Log::error('Data transfer failed', [
            'transfer_id' => $transfer->id,
            'organization_id' => $transfer->organization_id,
            'error' => $exception->getMessage(),
        ]);
        $transfer->forceFill([
            'status' => DataTransfer::STATUS_FAILED,
            'failure_report' => ['The transfer failed while processing. Please retry or contact an administrator.'],
        ])->save();
    }

    private function cleanupSource(DataTransfer $transfer): void
    {
        if ($this->hasSource($transfer)) {
            Storage::disk('local')->delete($transfer->source_path);
        }
    }

    private function hasSource(DataTransfer $transfer): bool
    {
        return $transfer->source_path
            && str_starts_with($transfer->source_path, 'transfers/'.$transfer->organization_id.'/input/')
            && ! str_contains($transfer->source_path, '..')
            && ! str_contains($transfer->source_path, '\\')
            && Storage::disk('local')->exists($transfer->source_path);
    }
}
