<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Session;
use App\Models\Sport;
use App\Models\Tournament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TournamentService
{
    /**
     * Create a tournament with associated sports (example of light service layer
     * for complex business flow, as per architecture plan).
     */
    public function createWithSports(array $data): Tournament
    {
        $user = Auth::user();
        $organizationId = $data['organization_id'] ?? $user?->organization_id;

        $this->ensureTournamentRelationsBelongToOrganization($organizationId, $data['session_id'] ?? null, $data['sports'] ?? []);
        $data['organization_id'] = $organizationId;

        $tournament = Tournament::create($data);

        if (! empty($data['sports'])) {
            $tournament->sports()->sync($data['sports']);
        }

        Log::info('Tournament created', ['id' => $tournament->id, 'name' => $tournament->name, 'org_id' => $tournament->organization_id]);

        return $tournament;
    }

    public function updateWithSports(Tournament $tournament, array $data): Tournament
    {
        if (array_key_exists('organization_id', $data) && $data['organization_id'] !== $tournament->organization_id) {
            throw ValidationException::withMessages([
                'organization_id' => ['A tournament cannot be moved to another organization.'],
            ]);
        }

        if (array_key_exists('session_id', $data)) {
            $sessionBelongsToTournamentOrganization = Session::withoutOrganizationScope()
                ->whereKey($data['session_id'])
                ->where('organization_id', $tournament->organization_id)
                ->exists();

            if (! $sessionBelongsToTournamentOrganization) {
                throw ValidationException::withMessages([
                    'session_id' => ['The selected session must belong to the tournament organization.'],
                ]);
            }
        }

        if (array_key_exists('sports', $data)) {
            $this->ensureSportsBelongToOrganization($tournament->organization_id, $data['sports'] ?? []);
            $tournament->sports()->sync($data['sports'] ?? []);
        }

        unset($data['sports']);
        $data['organization_id'] = $tournament->organization_id;
        $tournament->update($data);

        Log::info('Tournament updated', ['id' => $tournament->id, 'name' => $tournament->name]);

        return $tournament;
    }

    public function deleteWithSports(Tournament $tournament): void
    {
        $tournament->sports()->detach();
        $tournament->delete();

        Log::info('Tournament deleted', ['id' => $tournament->id, 'name' => $tournament->name]);
    }

    public function generateEventsFromCategories(Tournament $tournament): int
    {
        $count = 0;

        $sports = $tournament->sports()->get();

        $existingEvents = Event::withoutOrganizationScope()
            ->withTrashed()
            ->where('organization_id', $tournament->organization_id)
            ->where('tournament_id', $tournament->id)
            ->get()
            ->groupBy(function ($event) {
                return $event->sport_id.'_'.$event->sport_category_id;
            });

        DB::beginTransaction();

        try {
            foreach ($sports as $sport) {
                $categories = $sport->categories()
                    ->where('organization_id', $tournament->organization_id)
                    ->get();

                foreach ($categories as $category) {
                    $key = $sport->id.'_'.$category->id;
                    $existingEvent = $existingEvents->get($key)?->first();

                    if ($existingEvent) {
                        if ($existingEvent->trashed()) {
                            $existingEvent->restore();
                            $count++;
                        }

                        continue;
                    }

                    $baseName = "{$tournament->name} - {$sport->name} - {$category->name}";
                    $slug = $this->ensureUniqueEventSlug(Str::slug($baseName), $tournament->organization_id);

                    Event::create([
                        'organization_id' => $tournament->organization_id,
                        'tournament_id' => $tournament->id,
                        'sport_id' => $sport->id,
                        'sport_category_id' => $category->id,
                        'name' => $baseName,
                        'slug' => $slug,
                        'description' => null,
                        'start_date' => $tournament->start_date,
                        'end_date' => $tournament->end_date,
                        'is_active' => true,
                    ]);

                    $count++;
                }
            }

            DB::commit();

            Log::info('Events generated from categories', [
                'tournament_id' => $tournament->id,
                'tournament_name' => $tournament->name,
                'events_created' => $count,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to generate events from categories', [
                'tournament_id' => $tournament->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        return $count;
    }

    private function ensureUniqueEventSlug(string $slug, string $organizationId): string
    {
        $base = $slug;
        $counter = 1;

        while (Event::withoutOrganizationScope()->withTrashed()->where('organization_id', $organizationId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private function ensureTournamentRelationsBelongToOrganization(?string $organizationId, ?string $sessionId, array $sportIds): void
    {
        if (blank($organizationId)) {
            throw ValidationException::withMessages([
                'organization_id' => ['An organization is required.'],
            ]);
        }

        $user = Auth::user();
        if ($user && ! $user->hasRole('super-admin') && $organizationId !== $user->organization_id) {
            throw ValidationException::withMessages([
                'organization_id' => ['You may only manage tournaments in your organization.'],
            ]);
        }

        $sessionExists = Session::withoutOrganizationScope()
            ->whereKey($sessionId)
            ->where('organization_id', $organizationId)
            ->exists();

        if (! $sessionExists) {
            throw ValidationException::withMessages([
                'session_id' => ['The selected session must belong to the tournament organization.'],
            ]);
        }

        $this->ensureSportsBelongToOrganization($organizationId, $sportIds);
    }

    private function ensureSportsBelongToOrganization(string $organizationId, array $sportIds): void
    {
        $sportIds = collect($sportIds)->filter()->values();

        if ($sportIds->isEmpty()) {
            return;
        }

        $validCount = Sport::withoutOrganizationScope()
            ->where('organization_id', $organizationId)
            ->whereIn('id', $sportIds)
            ->count();

        if ($validCount !== $sportIds->unique()->count()) {
            throw ValidationException::withMessages([
                'sports' => ['All selected sports must belong to the tournament organization.'],
            ]);
        }
    }
}
