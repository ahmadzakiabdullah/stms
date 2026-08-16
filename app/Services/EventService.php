<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EventService
{
    /**
     * Create a new event.
     * Handles org scoping and slug generation.
     * Extracted to service layer for better separation (expanding from only TournamentService).
     */
    public function createEvent(array $data): Event
    {
        $user = Auth::user();

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $data['is_active'] = $data['is_active'] ?? true;

        if (empty($data['organization_id'])) {
            $data['organization_id'] = $user->organization_id;
        }

        try {
            $event = Event::create($data);
            Log::info('Event created', ['id' => $event->id, 'name' => $event->name, 'org_id' => $event->organization_id]);

            app(PublicPortalService::class)->forgetForOrganization($event->organization_id);

            return $event;
        } catch (QueryException $e) {
            Log::error('Event creation failed', ['name' => $data['name'], 'error' => $e->getMessage()]);
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

    /**
     * Update an existing event.
     */
    public function updateEvent(Event $event, array $data): Event
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $data['is_active'] = $data['is_active'] ?? $event->is_active;

        try {
            $event->update($data);
            Log::info('Event updated', ['id' => $event->id, 'name' => $event->name]);

            app(PublicPortalService::class)->forgetForOrganization($event->organization_id);

            return $event;
        } catch (QueryException $e) {
            Log::error('Event update failed', ['id' => $event->id, 'error' => $e->getMessage()]);
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

    /**
     * Delete an event (soft delete).
     */
    public function deleteEvent(Event $event): void
    {
        $event->delete();
        Log::info('Event deleted', ['id' => $event->id, 'name' => $event->name]);
        app(PublicPortalService::class)->forgetForOrganization($event->organization_id);
    }
}
