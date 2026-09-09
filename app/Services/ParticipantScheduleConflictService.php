<?php

namespace App\Services;

use App\Models\EventParticipant;
use App\Models\Fixture;
use Illuminate\Support\Collection;

final class ParticipantScheduleConflictService
{
    /**
     * Detect scheduling clashes for a participant across the matches they
     * appear in (home or away). Two matches clash when they are scheduled on
     * the same calendar day and share the same venue.
     *
     * @return array<int, array{
     *     fixture_a_id: string,
     *     fixture_b_id: string,
     *     event_a: ?string,
     *     event_b: ?string,
     *     date: string,
     *     venue: string,
     *     time_a: string,
     *     time_b: string,
     * }>
     */
    public function conflictsFor(EventParticipant $eventParticipant, int $limit = 5): array
    {
        $participantId = $eventParticipant->participant_id;

        if (! $participantId) {
            return [];
        }

        $fixtures = Fixture::query()
            ->where(fn ($query) => $query
                ->where('home_participant_id', $participantId)
                ->orWhere('away_participant_id', $participantId))
            ->with('event:id,name')
            ->whereNotNull('scheduled_at')
            ->get(['id', 'event_id', 'home_participant_id', 'away_participant_id', 'venue', 'scheduled_at']);

        return $this->calculateConflictsForFixtures($fixtures, $limit);
    }

    /**
     * Compute conflicts for multiple EventParticipants efficiently in a single database query.
     *
     * @param  Collection<int, EventParticipant>  $eventParticipants
     * @return array<string, array<int, array{fixture_a_id: string, fixture_b_id: string, event_a: ?string, event_b: ?string, date: string, venue: string, time_a: string, time_b: string}>>
     */
    public function conflictsForMultiple(Collection $eventParticipants, int $limit = 5): array
    {
        $participantIds = $eventParticipants->pluck('participant_id')->filter()->unique()->values();

        if ($participantIds->isEmpty()) {
            return [];
        }

        $allFixtures = Fixture::query()
            ->where(fn ($query) => $query
                ->whereIn('home_participant_id', $participantIds)
                ->orWhereIn('away_participant_id', $participantIds))
            ->with('event:id,name')
            ->whereNotNull('scheduled_at')
            ->get(['id', 'event_id', 'home_participant_id', 'away_participant_id', 'venue', 'scheduled_at']);

        $fixturesByParticipant = [];
        foreach ($allFixtures as $fixture) {
            if ($fixture->home_participant_id) {
                $fixturesByParticipant[$fixture->home_participant_id][] = $fixture;
            }
            if ($fixture->away_participant_id && $fixture->away_participant_id !== $fixture->home_participant_id) {
                $fixturesByParticipant[$fixture->away_participant_id][] = $fixture;
            }
        }

        $results = [];
        foreach ($eventParticipants as $ep) {
            if (! $ep->participant_id || empty($fixturesByParticipant[$ep->participant_id])) {
                continue;
            }

            $participantFixtures = collect($fixturesByParticipant[$ep->participant_id]);
            $conflicts = $this->calculateConflictsForFixtures($participantFixtures, $limit);

            if ($conflicts !== []) {
                $results[$ep->id] = $conflicts;
            }
        }

        return $results;
    }

    /**
     * @param  Collection|array  $fixtures
     */
    private function calculateConflictsForFixtures($fixtures, int $limit): array
    {
        $conflicts = [];
        $fixturesArray = is_array($fixtures) ? $fixtures : $fixtures->values()->all();
        $count = count($fixturesArray);

        for ($index = 0; $index < $count; $index++) {
            $a = $fixturesArray[$index];
            for ($j = $index + 1; $j < $count; $j++) {
                $b = $fixturesArray[$j];

                $aScheduledAt = $a->scheduled_at;
                $bScheduledAt = $b->scheduled_at;

                if (! $aScheduledAt || ! $bScheduledAt) {
                    continue;
                }

                $venueA = trim((string) $a->venue);
                $venueB = trim((string) $b->venue);

                if ($venueA === '' || $venueB === '' || $venueA !== $venueB) {
                    continue;
                }

                if ($aScheduledAt->toDateString() !== $bScheduledAt->toDateString()) {
                    continue;
                }

                $conflicts[] = [
                    'fixture_a_id' => $a->id,
                    'fixture_b_id' => $b->id,
                    'event_a' => $a->event?->name,
                    'event_b' => $b->event?->name,
                    'date' => $aScheduledAt->toDateString(),
                    'venue' => $venueA,
                    'time_a' => $aScheduledAt->format('H:i'),
                    'time_b' => $bScheduledAt->format('H:i'),
                ];

                if (count($conflicts) >= $limit) {
                    return $conflicts;
                }
            }
        }

        return $conflicts;
    }
}
