<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\Tournament;
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
        $organizationId = $this->resolveOrganizationId($data['organization_id'] ?? null);
        $data['organization_id'] = $organizationId;
        $this->ensureRelationsBelongToOrganization($data, $organizationId);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $data['is_active'] = $data['is_active'] ?? true;

        try {
            $event = Event::create($data);
            Log::info('Event created', ['id' => $event->id, 'name' => $event->name, 'org_id' => $event->organization_id]);

            $this->applyDefaultVenueToMatches($event);

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
        if (array_key_exists('organization_id', $data) && $data['organization_id'] !== $event->organization_id) {
            throw ValidationException::withMessages([
                'organization_id' => ['An event cannot be moved to another organization.'],
            ]);
        }

        $data['organization_id'] = $event->organization_id;
        $this->ensureRelationsBelongToOrganization($data, $event->organization_id, $event);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $data['is_active'] = $data['is_active'] ?? $event->is_active;

        try {
            $event->update($data);
            Log::info('Event updated', ['id' => $event->id, 'name' => $event->name]);

            $this->applyDefaultVenueToMatches($event);

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

    /**
     * Backfill the event's default venue onto existing matches that have no
     * venue assigned yet, so already-created matches use the event venue.
     */
    private function applyDefaultVenueToMatches(Event $event): void
    {
        $defaultVenue = $event->venues[0] ?? null;

        if ($defaultVenue === null) {
            return;
        }

        $event->matches()
            ->where(fn ($query) => $query->whereNull('venue')->orWhere('venue', ''))
            ->update(['venue' => $defaultVenue]);
    }

    private function resolveOrganizationId(?string $requestedOrganizationId): string
    {
        $user = Auth::user();
        $organizationId = $requestedOrganizationId ?: $user?->organization_id;

        if ($user && ! $user->hasRole('super-admin')) {
            $organizationId = $user->organization_id;
        }

        if (blank($organizationId) || ! Organization::whereKey($organizationId)->exists()) {
            throw ValidationException::withMessages([
                'organization_id' => ['A valid organization is required.'],
            ]);
        }

        return $organizationId;
    }

    private function ensureRelationsBelongToOrganization(array $data, string $organizationId, ?Event $event = null): void
    {
        $relations = [
            'tournament_id' => Tournament::class,
            'sport_id' => Sport::class,
        ];

        foreach ($relations as $field => $modelClass) {
            $id = $data[$field] ?? $event?->{$field};
            if (! $id || ! $modelClass::forOrganization($organizationId)->whereKey($id)->where('organization_id', $organizationId)->exists()) {
                throw ValidationException::withMessages([
                    $field => ['The selected relation must belong to the event organization.'],
                ]);
            }
        }

        $categoryId = $data['sport_category_id'] ?? $event?->sport_category_id;
        $sportId = $data['sport_id'] ?? $event?->sport_id;
        if (! $categoryId || ! SportCategory::forOrganization($organizationId)
            ->whereKey($categoryId)
            ->where('organization_id', $organizationId)
            ->where('sport_id', $sportId)
            ->exists()) {
            throw ValidationException::withMessages([
                'sport_category_id' => ['The selected category must belong to the event sport and organization.'],
            ]);
        }
    }
}
