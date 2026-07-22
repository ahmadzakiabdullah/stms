<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Tournament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TournamentService
{
    /**
     * Create a tournament with associated sports (example of light service layer
     * for complex business flow, as per architecture plan).
     */
    public function createWithSports(array $data): Tournament
    {
        $user = Auth::user();

        if (empty($data['organization_id'])) {
            $data['organization_id'] = $user->organization_id;
        }

        $tournament = Tournament::create($data);

        if (!empty($data['sports'])) {
            $tournament->sports()->sync($data['sports']);
        }

        Log::info('Tournament created', ['id' => $tournament->id, 'name' => $tournament->name, 'org_id' => $tournament->organization_id]);

        return $tournament;
    }

    public function updateWithSports(Tournament $tournament, array $data): Tournament
    {
        if (array_key_exists('sports', $data)) {
            $tournament->sports()->sync($data['sports'] ?? []);
        }

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

        DB::beginTransaction();

        try {
            foreach ($sports as $sport) {
                $categories = $sport->categories()
                    ->where('organization_id', $tournament->organization_id)
                    ->get();

                foreach ($categories as $category) {
                    $existingEvent = Event::withoutOrganizationScope()
                        ->withTrashed()
                        ->where('organization_id', $tournament->organization_id)
                        ->where('tournament_id', $tournament->id)
                        ->where('sport_id', $sport->id)
                        ->where('sport_category_id', $category->id)
                        ->first();

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
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
