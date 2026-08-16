<?php

namespace App\Services;

use App\Models\EventParticipant;
use App\Models\SquadMember;
use Illuminate\Validation\ValidationException;

final class SquadManagementService
{
    public function __construct(private readonly SquadQuotaService $quotaService) {}

    public function add(EventParticipant $eventParticipant, array $data): SquadMember
    {
        $eventParticipant = $eventParticipant->load('event.sportCategory');
        $this->ensureConfirmed($eventParticipant);
        $this->ensureRoleRequirements($eventParticipant, $data);

        return SquadMember::create([
            'event_participant_id' => $eventParticipant->id,
            'organization_id' => $eventParticipant->organization_id,
            ...$data,
        ]);
    }

    public function update(EventParticipant $eventParticipant, SquadMember $squadMember, array $data): SquadMember
    {
        $this->ensureOwnership($eventParticipant, $squadMember);
        $this->ensureConfirmed($eventParticipant);

        if ($squadMember->role !== $data['role']) {
            $this->ensureRoleRequirements($eventParticipant->load('event.sportCategory'), $data, $squadMember);
        }

        $squadMember->update($data);

        return $squadMember->refresh();
    }

    public function remove(EventParticipant $eventParticipant, SquadMember $squadMember): void
    {
        $this->ensureOwnership($eventParticipant, $squadMember);
        $squadMember->delete();
    }

    private function ensureConfirmed(EventParticipant $eventParticipant): void
    {
        if ($eventParticipant->status !== 'confirmed') {
            throw ValidationException::withMessages(['event_participant' => 'Only confirmed registrations can manage squad members.']);
        }
    }

    private function ensureOwnership(EventParticipant $eventParticipant, SquadMember $squadMember): void
    {
        abort_unless(
            $squadMember->event_participant_id === $eventParticipant->id
            && $squadMember->organization_id === $eventParticipant->organization_id,
            404,
        );
    }

    private function ensureRoleRequirements(EventParticipant $eventParticipant, array $data, ?SquadMember $current = null): void
    {
        $isOfficial = in_array($data['role'], SquadMember::OFFICIAL_ROLES, true);

        if ($isOfficial && blank($data['phone'])) {
            throw ValidationException::withMessages(['phone' => 'Officials must provide a phone number.']);
        }

        $officialExists = $eventParticipant->squadMembers()
            ->when($current, fn ($query) => $query->where('id', '!=', $current->id))
            ->whereIn('role', SquadMember::OFFICIAL_ROLES)
            ->exists();

        if (! $isOfficial && ! $officialExists) {
            throw ValidationException::withMessages(['role' => 'Add officials before athletes.']);
        }

        $quotaError = $this->quotaService->validateAddition($eventParticipant, $data['role']);
        if ($quotaError) {
            throw ValidationException::withMessages(['role' => $quotaError]);
        }
    }
}
