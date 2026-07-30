<?php

namespace App\Services;

use App\Models\Registration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RegistrationService
{
    public function createRegistration(array $data): Registration
    {
        $user = Auth::user();

        $data['status'] = $data['status'] ?? 'pending';
        $data['registered_at'] = $data['registered_at'] ?? now();

        if (empty($data['organization_id'])) {
            $data['organization_id'] = $user->organization_id;
        }

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
}
