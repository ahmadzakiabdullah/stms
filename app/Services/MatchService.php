<?php

namespace App\Services;

use App\Models\Fixture;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Pool;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MatchService
{
    public function getAllByOrganization(Organization $organization, array $filters = []): LengthAwarePaginator
    {
        return $this->baseQuery($organization)
            ->when($filters['event_id'] ?? null, fn ($q, $eventId) => $q->where('event_id', $eventId))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where('venue', 'like', "%{$search}%"))
            ->orderByDesc('scheduled_at')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function getAllByOrganizationForSelect(Organization $organization): Collection
    {
        return $this->baseQuery($organization)
            ->orderBy('match_number')
            ->get(['id', 'match_number', 'status', 'scheduled_at', 'event_id']);
    }

    public function getById(Organization $organization, string $id): Fixture
    {
        return $this->baseQuery($organization)
            ->with(['event', 'homeParticipant', 'awayParticipant', 'result'])
            ->where('matches.id', $id)
            ->firstOrFail();
    }

    public function create(Organization $organization, array $data): Fixture
    {
        $match = DB::transaction(function () use ($organization, $data) {
            $data['organization_id'] = $organization->id;
            $this->ensureRelationsBelongToOrganization($data, $organization->id);
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['match_number'] ?? Str::random(8));
            }
            if (empty($data['status'])) {
                $data['status'] = 'scheduled';
            }
            $match = Fixture::create($data);
            Log::info('Match created', ['id' => $match->id, 'org_id' => $organization->id]);

            return $match;
        });

        app(PublicPortalService::class)->forgetForOrganization($organization->id);

        return $match;
    }

    public function update(Organization $organization, string $id, array $data): Fixture
    {
        $match = DB::transaction(function () use ($organization, $id, $data) {
            $match = $this->getById($organization, $id);
            $data['organization_id'] = $organization->id;
            $this->ensureRelationsBelongToOrganization($data, $organization->id, $match);
            if (isset($data['slug']) && empty($data['slug'])) {
                $data['slug'] = Str::slug($data['match_number'] ?? Str::random(8));
            }
            $match->update($data);
            Log::info('Match updated', ['id' => $id, 'org_id' => $organization->id]);

            return $match->fresh();
        });

        app(PublicPortalService::class)->forgetForOrganization($organization->id);

        return $match;
    }

    public function delete(Organization $organization, string $id): void
    {
        DB::transaction(function () use ($organization, $id) {
            $match = $this->getById($organization, $id);
            $match->delete();
            Log::info('Match deleted', ['id' => $id, 'org_id' => $organization->id]);
        });

        app(PublicPortalService::class)->forgetForOrganization($organization->id);
    }

    public function countByOrganization(Organization $organization): int
    {
        return $this->baseQuery($organization)->count();
    }

    public function getStatusCounts(Organization $organization): array
    {
        return $this->baseQuery($organization)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    protected function baseQuery(Organization $organization)
    {
        return Fixture::where('organization_id', $organization->id);
    }

    private function ensureRelationsBelongToOrganization(array $data, string $organizationId, ?Fixture $match = null): void
    {
        $eventId = $data['event_id'] ?? $match?->event_id;
        $event = Event::forOrganization($organizationId)->whereKey($eventId)->first();
        if (! $event || $event->organization_id !== $organizationId) {
            throw ValidationException::withMessages([
                'event_id' => ['The selected event must belong to the match organization.'],
            ]);
        }

        $poolId = array_key_exists('pool_id', $data) ? $data['pool_id'] : $match?->pool_id;
        if ($poolId !== null && ! Pool::forOrganization($organizationId)
            ->whereKey($poolId)
            ->where('organization_id', $organizationId)
            ->where('event_id', $eventId)
            ->exists()) {
            throw ValidationException::withMessages([
                'pool_id' => ['The selected pool must belong to the event and organization.'],
            ]);
        }

        foreach (['home_participant_id', 'away_participant_id'] as $field) {
            $participantId = array_key_exists($field, $data) ? $data[$field] : $match?->{$field};
            if ($participantId !== null && ! Participant::forOrganization($organizationId)
                ->whereKey($participantId)
                ->where('organization_id', $organizationId)
                ->exists()) {
                throw ValidationException::withMessages([
                    $field => ['The selected participant must belong to the match organization.'],
                ]);
            }
        }
    }
}
