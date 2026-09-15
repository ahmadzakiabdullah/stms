<?php

namespace App\Imports;

use App\Models\SquadMember;
use App\Models\EventParticipant;
use App\Services\SquadQuotaService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Validation\ValidationException;

class SquadMembersImport implements ToCollection, WithHeadingRow
{
    public function __construct(
        public EventParticipant $eventParticipant,
        public string $organizationId
    ) {}

    public function collection(Collection $rows): void
    {
        $errors = [];
        $created = 0;
        $quotaService = app(SquadQuotaService::class);

        foreach ($rows as $index => $row) {
            $name = trim($row['name'] ?? '');
            $role = trim($row['role'] ?? '');
            $ic = trim($row['ic_passport'] ?? '');
            $phone = trim($row['phone'] ?? '');

            if (empty($name) || empty($role)) {
                $errors[] = "Row " . ($index + 2) . ": name and role are required.";
                continue;
            }

            $validRoles = ['athlete_male', 'athlete_female', 'assistant_manager', 'manager', 'coach', 'physio'];
            if (!in_array($role, $validRoles)) {
                $errors[] = "Row " . ($index + 2) . ": invalid role '{$role}'. Allowed: " . implode(', ', $validRoles);
                continue;
            }

            $quotaError = $quotaService->validateAddition($this->eventParticipant, $role);
            if ($quotaError) {
                $errors[] = "Row " . ($index + 2) . ": {$quotaError}";
                continue;
            }

            SquadMember::create([
                'event_participant_id' => $this->eventParticipant->id,
                'organization_id' => $this->organizationId,
                'name' => $name,
                'role' => $role,
                'identification_no' => $ic ?: null,
                'phone' => $phone ?: null,
            ]);
            $created++;
        }

        if (!empty($errors) && $created === 0) {
            throw ValidationException::withMessages(['file' => $errors]);
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages(['file' => array_merge(
                ["{$created} squad members imported with " . count($errors) . " errors:"],
                $errors
            )]);
        }
    }
}
