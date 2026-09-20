<?php

namespace App\Services;

use App\Models\Registration;
use App\Models\Participant;
use App\Models\Organization;
use App\Models\Tournament;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RegistrationService
{
    public function createRegistration(array $data): Registration
    {
        $organizationId = $this->resolveOrganizationId($data['organization_id'] ?? null);
        $this->ensureRelationsBelongToOrganization($data, $organizationId);
        $data['organization_id'] = $organizationId;

        $data['status'] = $data['status'] ?? 'pending';
        $data['registered_at'] = $data['registered_at'] ?? now();

        try {
            $registration = Registration::create($data);
            Log::info('Registration created', ['id' => $registration->id, 'tournament_id' => $registration->tournament_id, 'participant_id' => $registration->participant_id]);

            return $registration;
        } catch (QueryException $e) {
            Log::error('Registration creation failed', ['error' => $e->getMessage()]);
            if ($e->getCode() == 23000) {
                throw ValidationException::withMessages([
                    'participant_id' => ['This participant is already registered for this tournament.'],
                ]);
            }
            throw $e;
        }
    }

    public function updateRegistration(Registration $registration, array $data): Registration
    {
        if (array_key_exists('organization_id', $data) && $data['organization_id'] !== $registration->organization_id) {
            throw ValidationException::withMessages([
                'organization_id' => ['A registration cannot be moved to another organization.'],
            ]);
        }

        $this->ensureRelationsBelongToOrganization($data, $registration->organization_id, $registration);
        $data['organization_id'] = $registration->organization_id;

        try {
            $registration->update($data);
            Log::info('Registration updated', ['id' => $registration->id, 'status' => $registration->status]);

            return $registration;
        } catch (QueryException $e) {
            Log::error('Registration update failed', ['id' => $registration->id, 'error' => $e->getMessage()]);
            if ($e->getCode() == 23000) {
                throw ValidationException::withMessages([
                    'participant_id' => ['This participant is already registered for this tournament.'],
                ]);
            }
            throw $e;
        }
    }

    public function deleteRegistration(Registration $registration): void
    {
        $registration->delete();
        Log::info('Registration deleted', ['id' => $registration->id]);
    }

    private function resolveOrganizationId(?string $requestedOrganizationId): string
    {
        $user = Auth::user();
        $organizationId = $user?->hasRole('super-admin')
            ? ($requestedOrganizationId ?: $user?->organization_id)
            : $user?->organization_id;

        if (blank($organizationId) || ! Organization::whereKey($organizationId)->exists()) {
            throw ValidationException::withMessages([
                'organization_id' => ['A valid organization is required.'],
            ]);
        }

        return $organizationId;
    }

    private function ensureRelationsBelongToOrganization(array $data, string $organizationId, ?Registration $registration = null): void
    {
        $tournamentId = $data['tournament_id'] ?? $registration?->tournament_id;
        $participantId = $data['participant_id'] ?? $registration?->participant_id;

        $tournament = Tournament::forOrganization($organizationId)->whereKey($tournamentId)->first();
        if (! $tournament || $tournament->organization_id !== $organizationId) {
            throw ValidationException::withMessages([
                'tournament_id' => ['The selected tournament must belong to the registration organization.'],
            ]);
        }

        $participant = Participant::forOrganization($organizationId)->whereKey($participantId)->first();
        if (! $participant || $participant->organization_id !== $organizationId) {
            throw ValidationException::withMessages([
                'participant_id' => ['The selected participant must belong to the registration organization.'],
            ]);
        }
    }
}
