<?php

namespace App\Services;

use App\Models\Fixture;
use App\Models\Organization;
use Illuminate\Support\Carbon;

final class MatchScheduleConflictValidator
{
    private const WINDOW_MINUTES = 120;

    /**
     * Detect scheduling conflicts for the supplied fixture data, ignoring the
     * fixture identified by $exceptId (used when rescheduling an existing
     * match so it does not clash with itself).
     *
     * A conflict is reported when another scheduled fixture shares the same
     * venue and its time window overlaps, or shares a participant and its time
     * window overlaps, on the same day.
     *
     * @return array<int, array{
     *     fixture_id: string,
     *     match_number: string,
     *     event: ?string,
     *     venue: ?string,
     *     scheduled_at: string,
     *     kind: string,
     * }>
     */
    public function conflictsFor(Organization $organization, array $data, ?string $exceptId = null): array
    {
        $scheduledAt = isset($data['scheduled_at']) ? Carbon::parse($data['scheduled_at']) : null;
        $venue = trim((string) ($data['venue'] ?? ''));
        $home = $data['home_participant_id'] ?? null;
        $away = $data['away_participant_id'] ?? null;

        if ($scheduledAt === null || ($venue === '' && ! $home && ! $away)) {
            return [];
        }

        $day = $scheduledAt->copy()->startOfDay();
        $nextDay = $day->copy()->addDay();

        $query = Fixture::query()
            ->where('organization_id', $organization->id)
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [$day, $nextDay]);

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        $fixtures = $query->get(['id', 'match_number', 'event_id', 'home_participant_id', 'away_participant_id', 'venue', 'scheduled_at']);

        $conflicts = [];

        foreach ($fixtures as $fixture) {
            $fixtureStart = $fixture->scheduled_at;
            $fixtureVenue = trim((string) $fixture->venue);

            $venueClash = $venue !== '' && $venue === $fixtureVenue;
            $participantClash = ($home && in_array($home, [$fixture->home_participant_id, $fixture->away_participant_id], true))
                || ($away && in_array($away, [$fixture->home_participant_id, $fixture->away_participant_id], true));

            if (! $venueClash && ! $participantClash) {
                continue;
            }

            if (! $this->overlaps($scheduledAt, $fixtureStart)) {
                continue;
            }

            $conflicts[] = [
                'fixture_id' => $fixture->id,
                'match_number' => $fixture->match_number ?? '-',
                'event' => $fixture->event?->name,
                'venue' => $fixtureVenue !== '' ? $fixtureVenue : null,
                'scheduled_at' => $fixtureStart->format('Y-m-d H:i'),
                'kind' => $venueClash && $participantClash ? 'venue_and_participant' : ($venueClash ? 'venue' : 'participant'),
            ];
        }

        return $conflicts;
    }

    private function overlaps(Carbon $startA, Carbon $startB): bool
    {
        return abs($startA->diffInMinutes($startB, false)) < self::WINDOW_MINUTES;
    }
}
