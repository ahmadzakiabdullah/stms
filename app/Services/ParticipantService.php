<?php

namespace App\Services;

use App\Models\EventParticipant;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ParticipantService
{
    public function createParticipant(array $data): Participant
    {
        $user = Auth::user();

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $data['is_active'] = $data['is_active'] ?? true;
        $data['status'] = $data['status'] ?? 'registered';

        if (empty($data['organization_id'])) {
            $data['organization_id'] = $user->organization_id;
        }

        try {
            $participant = Participant::create($data);
            Log::info('Participant created', ['id' => $participant->id, 'name' => $participant->name, 'org_id' => $participant->organization_id]);

            $this->ensureUserLinked($participant, $data['organization_id']);

            return $participant;
        } catch (QueryException $e) {
            Log::error('Participant creation failed', ['name' => $data['name'], 'error' => $e->getMessage()]);
            if ($e->getCode() == 23000) {
                $msg = strtolower($e->getMessage());
                if (str_contains($msg, 'slug')) {
                    throw ValidationException::withMessages([
                        'slug' => ['The slug has already been taken.'],
                    ]);
                }
            }
            throw $e;
        }
    }

    public function ensureUserLinked(Participant $participant, ?string $organizationId = null): ?User
    {
        $email = $participant->email;

        if (empty($email)) {
            Log::warning('Cannot auto-create user for participant without email', ['participant_id' => $participant->id]);

            return null;
        }

        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {
            if (! $existingUser->participant_id) {
                $existingUser->update(['participant_id' => $participant->id]);
                Log::info('Linked existing user to participant', ['user_id' => $existingUser->uuid, 'participant_id' => $participant->id]);
            }

            return $existingUser;
        }

        $password = Str::password(12);

        $newUser = User::create([
            'name' => $participant->name,
            'email' => $email,
            'password' => Hash::make($password),
            'organization_id' => $organizationId ?? $participant->organization_id,
            'participant_id' => $participant->id,
        ]);

        Log::info('User auto-created for participant', [
            'participant_id' => $participant->id,
            'user_id' => $newUser->uuid,
            'email' => $email,
        ]);

        return $newUser;
    }

    public function updateParticipant(Participant $participant, array $data): Participant
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $data['is_active'] = $data['is_active'] ?? $participant->is_active;

        try {
            $participant->update($data);
            Log::info('Participant updated', ['id' => $participant->id, 'name' => $participant->name]);

            return $participant;
        } catch (QueryException $e) {
            Log::error('Participant update failed', ['id' => $participant->id, 'error' => $e->getMessage()]);
            if ($e->getCode() == 23000) {
                $msg = strtolower($e->getMessage());
                if (str_contains($msg, 'slug')) {
                    throw ValidationException::withMessages([
                        'slug' => ['The slug has already been taken.'],
                    ]);
                }
            }
            throw $e;
        }
    }

    public function deleteParticipant(Participant $participant): void
    {
        $participant->delete();
        Log::info('Participant deleted', ['id' => $participant->id, 'name' => $participant->name]);
    }

    public function registerToEvent(Participant $participant, string $eventId, array $data = []): EventParticipant
    {
        $existing = EventParticipant::withTrashed()
            ->withoutOrganizationScope()
            ->where('event_id', $eventId)
            ->where('participant_id', $participant->id)
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'participant_id' => ['This participant is already registered for this event.'],
            ]);
        }

        $registration = EventParticipant::create(array_merge([
            'event_id' => $eventId,
            'participant_id' => $participant->id,
            'organization_id' => $participant->organization_id,
            'registration_date' => now(),
            'status' => 'pending',
        ], $data));

        Log::info('Participant registered to event', ['participant_id' => $participant->id, 'event_id' => $eventId]);

        return $registration;
    }

    public function withdrawFromEvent(Participant $participant, string $eventId): void
    {
        $registration = EventParticipant::withoutOrganizationScope()
            ->where('event_id', $eventId)
            ->where('participant_id', $participant->id)
            ->first();

        if (! $registration) {
            throw ValidationException::withMessages([
                'participant_id' => ['This participant is not registered for this event.'],
            ]);
        }

        $registration->update(['status' => 'withdrawn']);
        Log::info('Participant withdrawn from event', ['participant_id' => $participant->id, 'event_id' => $eventId]);
    }
}
