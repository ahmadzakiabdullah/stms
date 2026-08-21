<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Participant;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\Tournament;
use App\Models\User;
use App\Services\Concerns\ExecutesResilientQueries;
use Illuminate\Pagination\LengthAwarePaginator;

final class EventIndexService
{
    use ExecutesResilientQueries;

    private bool $dataLoadFailed = false;

    /**
     * @param  array{search?: ?string, tournament_id?: ?string, has_is_active?: bool, is_active?: mixed}  $filters
     * @return array<string, mixed>
     */
    public function dataFor(User $user, array $filters): array
    {
        $this->dataLoadFailed = false;
        $scopeToAdminSports = $user->hasRole('admin-sport') && ! $user->hasRole(['super-admin', 'org-admin']);
        $sportIds = $scopeToAdminSports ? $user->sports()->pluck('sports.id') : null;

        $events = $this->safePaginatedQuery(function () use ($sportIds, $filters) {
            $query = Event::with(['tournament', 'sport', 'sportCategory', 'organization'])
                ->withCount('pools')
                ->withCount([
                    'matches as matches_count',
                    'matches as completed_matches_count' => fn ($query) => $query->where('status', 'completed'),
                    'eventParticipants as registrations_count',
                    'eventParticipants as confirmed_participants_count' => fn ($query) => $query->where('status', 'confirmed'),
                    'eventParticipants as pending_participants_count' => fn ($query) => $query->where('status', 'pending'),
                ]);

            if ($sportIds !== null) {
                $query->whereIn('sport_id', $sportIds);
            }

            if ($tournamentId = ($filters['tournament_id'] ?? null)) {
                $query->where('tournament_id', $tournamentId);
            }

            if ($search = trim((string) ($filters['search'] ?? ''))) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhereHas('tournament', fn ($tournament) => $tournament->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('sport', fn ($sport) => $sport->where('name', 'like', "%{$search}%"));
                });
            }

            if ($filters['has_is_active'] ?? false) {
                $query->where('is_active', $filters['is_active'] ?? null);
            }

            return $query->orderBy('start_date', 'desc')
                ->paginate(15)
                ->withQueryString();
        }, function () {
            $this->dataLoadFailed = true;

            return new LengthAwarePaginator([], 0, 15, 1, ['path' => request()->url()]);
        });

        $participantCounts = $this->safeCollectionQuery(function () use ($events) {
            return Participant::query()
                ->where('is_active', true)
                ->whereIn('organization_id', collect($events->items())->pluck('organization_id')->unique()->filter())
                ->selectRaw('organization_id, count(*) as participants_count')
                ->groupBy('organization_id')
                ->pluck('participants_count', 'organization_id');
        }, fn () => collect());

        foreach ($events as $event) {
            $event->participants_count = $participantCounts[$event->organization_id] ?? 0;
        }

        $tournaments = $this->safeCollectionQuery(function () use ($sportIds) {
            return Tournament::query()
                ->with('sports:id,name')
                ->when($sportIds !== null, fn ($query) => $query->whereHas('sports', fn ($sports) => $sports->whereIn('sports.id', $sportIds)))
                ->orderBy('start_date', 'desc')
                ->get(['id', 'name', 'slug', 'start_date', 'end_date']);
        }, function () {
            $this->dataLoadFailed = true;

            return collect();
        });

        $sports = $this->safeCollectionQuery(function () use ($sportIds) {
            return Sport::query()
                ->when($sportIds !== null, fn ($query) => $query->whereIn('id', $sportIds))
                ->orderBy('name')
                ->get(['id', 'name', 'slug']);
        }, function () {
            $this->dataLoadFailed = true;

            return collect();
        });

        $categories = $this->safeCollectionQuery(function () use ($sportIds) {
            return SportCategory::query()
                ->with('sport')
                ->when($sportIds !== null, fn ($query) => $query->whereIn('sport_id', $sportIds))
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'sport_id']);
        }, function () {
            $this->dataLoadFailed = true;

            return collect();
        });

        $usedCategoryIds = Event::query()
            ->when($sportIds !== null, fn ($query) => $query->whereIn('sport_id', $sportIds))
            ->select('tournament_id', 'sport_id', 'sport_category_id')
            ->get()
            ->groupBy(fn ($event) => $event->tournament_id.':'.$event->sport_id)
            ->map(fn ($group) => $group->pluck('sport_category_id')->values()->toArray())
            ->toArray();

        return [
            'events' => $events,
            'tournaments' => $tournaments,
            'sports' => $sports,
            'categories' => $categories,
            'usedCategoryIds' => $usedCategoryIds,
            'dataLoadFailed' => $this->dataLoadFailed,
        ];
    }
}
