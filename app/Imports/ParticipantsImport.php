<?php

namespace App\Imports;

use App\Models\Participant;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ParticipantsImport implements ToCollection, WithCustomCsvSettings, WithHeadingRow
{
    private array $rows = [];

    private array $errors = [];

    private array $existingNames = [];

    private array $existingSlugs = [];

    public function __construct(
        public string $organizationId,
        public ?string $sessionId = null,
    ) {
        $this->existingNames = Participant::query()
            ->where('organization_id', $organizationId)
            ->pluck('name')
            ->map(fn ($name) => mb_strtolower(trim((string) $name)))
            ->all();

        $this->existingSlugs = Participant::query()
            ->where('organization_id', $organizationId)
            ->pluck('slug')
            ->map(fn ($slug) => mb_strtolower(trim((string) $slug)))
            ->all();
    }

    public function getCsvSettings(): array
    {
        return ['delimiter' => ',', 'input_encoding' => 'UTF-8'];
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $validated = $this->validateRow($rowNumber, $row);

            if (! empty($validated['errors'])) {
                $this->errors[] = 'Row '.$rowNumber.': '.implode(' ', $validated['errors']);

                continue;
            }

            $data = $validated['data'];
            $this->rows[] = [
                'row_number' => $rowNumber,
                'data' => $data,
            ];
        }
    }

    private function validateRow(int $rowNumber, Collection $row): array
    {
        $errors = [];
        $data = [];

        $name = trim((string) ($row['name'] ?? ''));
        if ($name === '') {
            $errors[] = 'name is required.';
        } else {
            $data['name'] = $name;
        }

        $type = mb_strtolower(trim((string) ($row['participant_type'] ?? 'team')));
        if ($type === '' || ! in_array($type, ['individual', 'team'], true)) {
            $errors[] = "participant_type '{$type}' is invalid (allowed: individual, team).";
        } else {
            $data['participant_type'] = $type;
        }

        $teamName = trim((string) ($row['team_name'] ?? ''));
        if ($teamName !== '') {
            $data['team_name'] = mb_substr($teamName, 0, 255);
        }

        $email = trim((string) ($row['email'] ?? ''));
        if ($email !== '') {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "email '{$email}' is invalid.";
            } else {
                $data['email'] = $email;
            }
        }

        $phone = trim((string) ($row['phone'] ?? ''));
        if ($phone !== '') {
            $data['phone'] = mb_substr($phone, 0, 50);
        }

        $status = mb_strtolower(trim((string) ($row['status'] ?? 'registered')));
        if (! in_array($status, ['registered', 'confirmed', 'withdrawn', 'disqualified'], true)) {
            $errors[] = "status '{$status}' is invalid (allowed: registered, confirmed, withdrawn, disqualified).";
        } else {
            $data['status'] = $status;
        }

        $isActiveRaw = mb_strtolower(trim((string) ($row['is_active'] ?? 'true')));
        $isActive = in_array($isActiveRaw, ['1', 'true', 'yes', 'y', 'aktif'], true)
            ? true
            : (in_array($isActiveRaw, ['0', 'false', 'no', 'n', 'tidak'], true) ? false : null);
        if ($isActive === null) {
            $errors[] = "is_active '{$isActiveRaw}' is invalid (allowed: true/false).";
        } else {
            $data['is_active'] = $isActive;
        }

        $slug = trim((string) ($row['slug'] ?? ''));
        $lowerName = mb_strtolower($name);
        if (in_array($lowerName, $this->existingNames, true)) {
            $errors[] = "'{$name}' already exists in this organization.";
        }

        if ($slug !== '') {
            if (! preg_match('/^[a-zA-Z0-9_-]+$/', $slug)) {
                $errors[] = "slug '{$slug}' must be alphanumeric with dashes or underscores.";
            } elseif (in_array(mb_strtolower($slug), $this->existingSlugs, true)) {
                $errors[] = "slug '{$slug}' is already taken.";
            } else {
                $data['slug'] = $slug;
            }
        }

        if (empty($errors) && $name !== '') {
            $data['session_id'] = $this->sessionId;
            $data['status'] ??= 'registered';
            $heuristic = $data['name'];
            $baseSlug = Str::slug($heuristic) ?: Str::random(8);
            $candidate = $baseSlug;
            $suffix = 2;
            while (in_array(mb_strtolower($candidate), $this->existingSlugs, true)) {
                $candidate = $baseSlug.'-'.$suffix++;
            }
            $data['slug'] = $slug !== '' ? $slug : $candidate;
            $this->existingSlugs[] = mb_strtolower($data['slug']);
            $this->existingNames[] = mb_strtolower($name);
        }

        return [
            'data' => $data,
            'errors' => $errors,
        ];
    }

    public function rows(): array
    {
        return $this->rows;
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
