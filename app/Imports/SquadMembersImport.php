<?php

namespace App\Imports;

use App\Models\EventParticipant;
use App\Models\SquadMember;
use App\Services\SquadQuotaService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

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
        $hasOfficial = $this->eventParticipant
            ->squadMembers()
            ->whereIn('role', ['assistant_manager', 'manager', 'coach', 'physio'])
            ->exists();

        foreach ($rows as $index => $row) {
            $name = trim($row['name'] ?? '');
            $role = trim($row['role'] ?? '');
            $matrixNo = trim($row['matrix_no'] ?? '');
            $ic = trim($row['ic_passport'] ?? '');
            $phone = trim($row['phone'] ?? '');

            if (empty($name) || empty($role)) {
                $errors[] = 'Row '.($index + 2).': name and role are required.';

                continue;
            }

            $validRoles = ['athlete_male', 'athlete_female', 'assistant_manager', 'manager', 'coach', 'physio'];
            if (! in_array($role, $validRoles)) {
                $errors[] = 'Row '.($index + 2).": invalid role '{$role}'. Allowed: ".implode(', ', $validRoles);

                continue;
            }

            if (empty($matrixNo)) {
                $errors[] = 'Row '.($index + 2).': matrix number is required.';

                continue;
            }

            $isOfficial = in_array($role, ['assistant_manager', 'manager', 'coach', 'physio'], true);
            if ($isOfficial && empty($phone)) {
                $errors[] = 'Row '.($index + 2).': officials must provide a phone number.';

                continue;
            }

            if (! $isOfficial && ! $hasOfficial) {
                $errors[] = 'Row '.($index + 2).': add officials before athletes.';

                continue;
            }

            $quotaError = $quotaService->validateAddition($this->eventParticipant, $role);
            if ($quotaError) {
                $errors[] = 'Row '.($index + 2).": {$quotaError}";

                continue;
            }

            SquadMember::create([
                'event_participant_id' => $this->eventParticipant->id,
                'organization_id' => $this->organizationId,
                'name' => $name,
                'matrix_no' => $matrixNo,
                'role' => $role,
                'identification_no' => $ic ?: null,
                'phone' => $phone ?: null,
            ]);
            if ($isOfficial) {
                $hasOfficial = true;
            }
            $created++;
        }

        if (! empty($errors) && $created === 0) {
            throw ValidationException::withMessages(['file' => $errors]);
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages(['file' => array_merge(
                ["{$created} squad members imported with ".count($errors).' errors:'],
                $errors
            )]);
        }
    }
}
