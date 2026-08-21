<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Participant;
use App\Models\User;
use App\Services\Concerns\ExecutesResilientQueries;
use Illuminate\Pagination\LengthAwarePaginator;

final class EventParticipantIndexService
{
    use ExecutesResilientQueries;

    private bool $dataLoadFailed = false;

    /**
     * @param  array{search?: ?string, sport_id?: ?string, category_id?: ?string, participant_id?: ?string, status?: ?string}  $filters
     * @return array<string, mixed>
     */
    public function dataFor(User $user, array $filters): array
    {
        $this->dataLoadFailed = false;

        $hasParticipant = ! is_null($user->participant_id);
        $isFacultyRepresentative = $user->hasRole('faculty-representative');
        $search = $filters['search'] ?? null;
        $sportId = $filters['sport_id'] ?? null;
        $categoryId = $filters['category_id'] ?? null;
        $participantId = $filters['participant_id'] ?? null;
        $status = $filters['status'] ?? null;

        $participants = $this->safePaginatedQuery(function () use ($hasParticipant, $isFacultyRepresentative, $user, $search, $sportId, $categoryId, $participantId, $status) {
            $query = Participant::query()
                ->with(['eventParticipants' => function ($query) use ($search, $sportId, $categoryId, $status) {
                    $query->with(['event.sport', 'event.sportCategory', 'event.tournament', 'squadMembers' => fn ($query) => $query->ordered()])
                        ->when($search, fn ($query, $value) => $query->where(fn ($query) => $query->whereHas('event', fn ($query) => $query->where('name', 'like', "%{$value}%"))
                            ->orWhereHas('event.sport', fn ($query) => $query->where('name', 'like', "%{$value}%"))
                            ->orWhereHas('event.sportCategory', fn ($query) => $query->where('name', 'like', "%{$value}%"))))
                        ->when($sportId, fn ($query) => $query->whereHas('event', fn ($query) => $query->where('sport_id', $sportId)))
                        ->when($categoryId, fn ($query) => $query->whereHas('event', fn ($query) => $query->where('sport_category_id', $categoryId)))
                        ->when($status, fn ($query, $value) => $query->where('status', $value));
                }])
                ->where('is_active', true);

            if ($hasParticipant) {
                $query->where('id', $user->participant_id);
            } elseif ($isFacultyRepresentative) {
                // A faculty account without a mapped participant must never
                // fall back to the organization-wide registration list.
                $query->whereRaw('1 = 0');
            } elseif (! $user->hasRole('super-admin')) {
                $query->whereHas('eventParticipants');
            }

            $query
                ->when($search, function ($query, $value) {
                    $query->where(function ($query) use ($value) {
                        $query->where('name', 'like', "%{$value}%")
                            ->orWhereHas('eventParticipants.event', fn ($query) => $query->where('name', 'like', "%{$value}%"))
                            ->orWhereHas('eventParticipants.event.sport', fn ($query) => $query->where('name', 'like', "%{$value}%"))
                            ->orWhereHas('eventParticipants.event.sportCategory', fn ($query) => $query->where('name', 'like', "%{$value}%"));
                    });
                })
                ->when($sportId, fn ($query, $value) => $query->whereHas('eventParticipants.event', fn ($query) => $query->where('sport_id', $value)))
                ->when($categoryId, fn ($query, $value) => $query->whereHas('eventParticipants.event', fn ($query) => $query->where('sport_category_id', $value)))
                ->when($participantId, fn ($query, $value) => $query->where('id', $value))
                ->orderBy('name');

            return $query->paginate(10)->withQueryString();
        }, function () {
            $this->dataLoadFailed = true;

            return new LengthAwarePaginator([], 0, 10, 1, ['path' => request()->url()]);
        });

        $events = $this->safeCollectionQuery(function () use ($search, $sportId, $categoryId) {
            return Event::query()
                ->with(['sport', 'sportCategory', 'tournament'])
                ->where('is_active', true)
                ->when($search, fn ($query, $value) => $query->where(fn ($query) => $query->where('name', 'like', "%{$value}%")
                    ->orWhereHas('sport', fn ($query) => $query->where('name', 'like', "%{$value}%"))
                    ->orWhereHas('sportCategory', fn ($query) => $query->where('name', 'like', "%{$value}%"))))
                ->when($sportId, fn ($query, $value) => $query->where('sport_id', $value))
                ->when($categoryId, fn ($query, $value) => $query->where('sport_category_id', $value))
                ->orderBy('name')
                ->get();
        }, function () {
            $this->dataLoadFailed = true;

            return collect();
        });

        $faculties = $this->safeCollectionQuery(function () {
            return Participant::query()
                ->with(['eventParticipants:id,participant_id,event_id'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']);
        }, function () {
            $this->dataLoadFailed = true;

            return collect();
        });

        $statusCounts = $this->safeCollectionQuery(function () use ($hasParticipant, $isFacultyRepresentative, $user, $search, $sportId, $categoryId, $participantId) {
            $query = EventParticipant::query()
                ->when($search, fn ($query, $value) => $query->where(fn ($query) => $query->whereHas('participant', fn ($query) => $query->where('name', 'like', "%{$value}%"))
                    ->orWhereHas('event', fn ($query) => $query->where('name', 'like', "%{$value}%"))
                    ->orWhereHas('event.sport', fn ($query) => $query->where('name', 'like', "%{$value}%"))
                    ->orWhereHas('event.sportCategory', fn ($query) => $query->where('name', 'like', "%{$value}%"))))
                ->when($sportId, fn ($query, $value) => $query->whereHas('event', fn ($query) => $query->where('sport_id', $value)))
                ->when($categoryId, fn ($query, $value) => $query->whereHas('event', fn ($query) => $query->where('sport_category_id', $value)))
                ->when($participantId, fn ($query, $value) => $query->where('participant_id', $value));

            if ($hasParticipant) {
                $query->where('participant_id', $user->participant_id);
            } elseif ($isFacultyRepresentative) {
                // Fail closed rather than exposing all organization counts if
                // the faculty-user mapping is incomplete.
                $query->whereRaw('1 = 0');
            }

            return $query->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');
        }, fn () => collect(['pending' => 0, 'confirmed' => 0, 'rejected' => 0]));
        $statusCounts = collect(['pending' => 0, 'confirmed' => 0, 'rejected' => 0])->merge($statusCounts);

        return [
            'participants' => $participants,
            'events' => $events,
            'faculties' => $faculties,
            'isFacultyRepresentative' => $isFacultyRepresentative,
            'statusCounts' => $statusCounts,
            'dataLoadFailed' => $this->dataLoadFailed,
        ];
    }
}
