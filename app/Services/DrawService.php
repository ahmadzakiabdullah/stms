<?php

namespace App\Services;

use App\Models\DrawVersion;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Fixture;
use App\Models\Pool;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DrawService
{
    /**
     * Phase 1 of the draw: randomly assign confirmed participants into groups.
     * Deletes any previous pools/fixtures, creates new pools based on event
     * pool_size and confirmed participant count, then shuffles participants
     * into the pools. Fixtures are NOT generated — call generateFixtures().
     */
    public function drawGroups(Event $event): array
    {
        $confirmedParticipants = EventParticipant::where('event_id', $event->id)
            ->where('status', 'confirmed')
            ->with('participant')
            ->get();

        if ($confirmedParticipants->count() < 2) {
            throw new \InvalidArgumentException('Need at least 2 confirmed participants to draw.');
        }

        return DB::transaction(function () use ($event, $confirmedParticipants) {
            // Hard-delete previous fixtures (and their results via cascade) so
            // match numbers restart cleanly at 1 on every re-draw.
            Fixture::where('event_id', $event->id)->forceDelete();
            EventParticipant::where('event_id', $event->id)->update(['pool_id' => null]);
            Pool::where('event_id', $event->id)->forceDelete();

            $orgId = $event->organization_id ?? Auth::user()->organization_id;
            $poolSize = $event->pool_size ?? 4;
            $total = $confirmedParticipants->count();

            $numPools = max(1, (int) ceil($total / $poolSize));

            $pools = [];
            $poolLetters = range('A', 'Z');
            for ($i = 0; $i < $numPools; $i++) {
                $pools[] = Pool::create([
                    'organization_id' => $orgId,
                    'event_id' => $event->id,
                    'name' => 'Group '.($poolLetters[$i] ?? ($i + 1)),
                    'sort_order' => $i,
                ]);
            }

            $seed = (string) Str::uuid();
            $shuffled = $confirmedParticipants
                ->sortBy(fn (EventParticipant $participant) => hash('sha256', $seed.$participant->id))
                ->values();
            $poolIndex = 0;
            foreach ($shuffled as $ep) {
                $ep->update(['pool_id' => $pools[$poolIndex]->id]);
                $poolIndex = ($poolIndex + 1) % $numPools;
            }

            Log::info('Draw groups created', [
                'event_id' => $event->id,
                'pools' => $numPools,
                'participants' => $total,
            ]);

            $this->recordSnapshot($event, 'drawn', $seed);

            return [
                'pools' => $numPools,
                'participants' => $total,
                'seed' => $seed,
            ];
        });
    }

    /**
     * Phase 2 of the draw: generate round-robin fixtures for every existing
     * pool and renumber them so match numbers run continuously from 1.
     */
    public function generateFixtures(Event $event): array
    {
        $pools = $event->pools()->orderBy('sort_order')->get();

        if ($pools->isEmpty()) {
            throw new \InvalidArgumentException('Cannot generate fixtures: no draw has been performed yet.');
        }

        return DB::transaction(function () use ($event, $pools) {
            $fixturesCreated = 0;
            foreach ($pools as $pool) {
                $fixturesCreated += $this->generateRoundRobinFixtures($event, $pool);
            }

            if ($fixturesCreated > 0) {
                $this->renumberEventFixtures($event);
            }

            Log::info('Fixtures generated', [
                'event_id' => $event->id,
                'pools' => $pools->count(),
                'fixtures' => $fixturesCreated,
            ]);

            $this->recordSnapshot($event, 'fixtures_generated');

            return [
                'pools' => $pools->count(),
                'fixtures' => $fixturesCreated,
            ];
        });
    }

    /**
     * Remove all pools and fixtures for an event so it can be drawn again.
     */
    public function resetDraw(Event $event): void
    {
        DB::transaction(function () use ($event) {
            $this->recordSnapshot($event, 'before_reset');
            Fixture::where('event_id', $event->id)->forceDelete();
            EventParticipant::where('event_id', $event->id)->update(['pool_id' => null]);
            Pool::where('event_id', $event->id)->delete();
        });
    }

    /**
     * Perform a full draw in one step (groups + fixtures).
     * Kept for backward compatibility (used by tests).
     */
    public function drawAndGenerateFixtures(Event $event): array
    {
        $groups = $this->drawGroups($event);
        $fixtures = $this->generateFixtures($event);

        return [
            'pools' => $groups['pools'],
            'participants' => $groups['participants'],
            'fixtures' => $fixtures['fixtures'],
        ];
    }

    /**
     * Generate round-robin fixtures within a pool.
     * Uses Circle Method algorithm to ensure every participant plays every other participant once.
     */
    public function generateRoundRobinFixtures(Event $event, Pool $pool): int
    {
        $participantIds = EventParticipant::where('pool_id', $pool->id)
            ->where('status', 'confirmed')
            ->pluck('participant_id')
            ->toArray();

        if (count($participantIds) < 2) {
            return 0;
        }

        $existingCount = Fixture::where('event_id', $event->id)->max('match_number') ?? 0;
        $orgId = $event->organization_id ?? Auth::user()->organization_id;
        $fixtures = [];
        $roundNumber = 1;

        $teams = $participantIds;
        $numTeams = count($teams);

        $hasBye = false;
        if ($numTeams % 2 !== 0) {
            $teams[] = null;
            $numTeams++;
            $hasBye = true;
        }

        $numRounds = $numTeams - 1;
        $half = $numTeams / 2;

        for ($round = 0; $round < $numRounds; $round++) {
            for ($match = 0; $match < $half; $match++) {
                $home = $teams[$match];
                $away = $teams[$numTeams - 1 - $match];

                if ($home === null || $away === null) {
                    continue;
                }

                $existingCount++;
                $fixtures[] = [
                    'id' => (string) Str::uuid(),
                    'organization_id' => $orgId,
                    'event_id' => $event->id,
                    'pool_id' => $pool->id,
                    'round' => $roundNumber,
                    'match_number' => $existingCount,
                    'home_participant_id' => $home,
                    'away_participant_id' => $away,
                    'status' => 'scheduled',
                ];
            }
            $roundNumber++;

            $first = $teams[0];
            $rest = array_slice($teams, 1);
            $last = array_pop($rest);
            array_unshift($rest, $last);
            $teams = array_merge([$first], $rest);
        }

        Fixture::insert($fixtures);

        return count($fixtures);
    }

    /**
     * Renumber all fixtures of an event so match numbers run continuously
     * from 1 to the last match, ordered by stage (group → semi-final →
     * bronze → final), then pool order, round and match number.
     * Prevents gaps after fixtures are regenerated for individual pools.
     */
    public function renumberEventFixtures(Event $event): int
    {
        $stageOrder = ['group' => 1, 'semi_final' => 2, 'bronze' => 3, 'final' => 4];

        $table = (new Fixture)->getTable();
        $fixtures = Fixture::where($table.'.event_id', $event->id)
            ->leftJoin('pools', 'pools.id', '=', $table.'.pool_id')
            ->select($table.'.*')
            ->get()
            ->sortBy([
                fn ($fixture) => $stageOrder[$fixture->stage ?? 'group'] ?? 0,
                fn ($fixture) => $fixture->pools_sort_order ?? PHP_INT_MAX,
                fn ($fixture) => $fixture->round ?? 0,
                fn ($fixture) => $fixture->match_number ?? 0,
            ])
            ->values();

        $ids = $fixtures->pluck('id')->all();
        if ($ids === []) {
            return 0;
        }

        // Temporarily park all match numbers out of sequence using a large
        // positive offset (never a negative, since match_number is UNSIGNED in
        // MySQL), keeping the (event_id, match_number) unique constraint intact.
        Fixture::whereIn('id', $ids)->update(['match_number' => DB::raw('match_number + 1000000000')]);

        $number = 1;
        foreach ($fixtures as $fixture) {
            Fixture::where('id', $fixture->id)->update(['match_number' => $number++]);
        }

        return $number - 1;
    }

    /**
     * Move a participant to a different pool and regenerate fixtures for affected pools.
     */
    public function moveParticipantToPool(Event $event, string $eventParticipantId, string $targetPoolId): void
    {
        DB::transaction(function () use ($event, $eventParticipantId, $targetPoolId) {
            $ep = EventParticipant::where('id', $eventParticipantId)
                ->where('event_id', $event->id)
                ->firstOrFail();

            $sourcePoolId = $ep->pool_id;

            if ($sourcePoolId === $targetPoolId) {
                return;
            }

            $targetPool = Pool::where('id', $targetPoolId)
                ->where('event_id', $event->id)
                ->firstOrFail();

            $ep->update(['pool_id' => $targetPoolId]);

            $affectedPoolIds = array_unique(array_filter([$sourcePoolId, $targetPoolId]));
            foreach ($affectedPoolIds as $poolId) {
                $pool = Pool::find($poolId);
                if ($pool) {
                    Fixture::where('event_id', $event->id)
                        ->where('pool_id', $poolId)
                        ->forceDelete();
                    $this->generateRoundRobinFixtures($event, $pool);
                }
            }

            $this->renumberEventFixtures($event);
            $this->recordSnapshot($event, 'participant_moved');
        });
    }

    public function rollback(Event $event, DrawVersion $version): void
    {
        if ($version->event_id !== $event->id || $version->organization_id !== $event->organization_id) {
            throw new \InvalidArgumentException('The selected draw version does not belong to this event.');
        }

        if (Fixture::where('event_id', $event->id)->whereIn('status', ['in_progress', 'completed'])->exists()) {
            throw new \InvalidArgumentException('Cannot roll back the draw after a match has started.');
        }

        DB::transaction(function () use ($event, $version) {
            $this->recordSnapshot($event, 'before_rollback');
            Fixture::where('event_id', $event->id)->forceDelete();
            EventParticipant::where('event_id', $event->id)->update(['pool_id' => null]);
            Pool::where('event_id', $event->id)->delete();

            $poolMap = [];
            foreach ($version->snapshot['pools'] ?? [] as $poolData) {
                $pool = Pool::create([
                    'organization_id' => $event->organization_id,
                    'event_id' => $event->id,
                    'name' => $poolData['name'],
                    'sort_order' => $poolData['sort_order'],
                ]);
                $poolMap[$poolData['id']] = $pool->id;

                EventParticipant::where('event_id', $event->id)
                    ->whereIn('participant_id', $poolData['participant_ids'] ?? [])
                    ->update(['pool_id' => $pool->id]);
            }

            foreach ($version->snapshot['fixtures'] ?? [] as $fixture) {
                Fixture::create([
                    'organization_id' => $event->organization_id,
                    'event_id' => $event->id,
                    'pool_id' => $poolMap[$fixture['pool_id']] ?? null,
                    'stage' => $fixture['stage'],
                    'round' => $fixture['round'],
                    'match_number' => $fixture['match_number'],
                    'home_participant_id' => $fixture['home_participant_id'],
                    'away_participant_id' => $fixture['away_participant_id'],
                    'venue' => $fixture['venue'],
                    'scheduled_at' => $fixture['scheduled_at'],
                    'status' => $fixture['status'],
                    'notes' => $fixture['notes'],
                ]);
            }

            $this->recordSnapshot($event, 'rollback', $version->seed);
        });
    }

    private function recordSnapshot(Event $event, string $action, ?string $seed = null): DrawVersion
    {
        $pools = Pool::where('event_id', $event->id)->orderBy('sort_order')->get()
            ->map(fn (Pool $pool) => [
                'id' => $pool->id,
                'name' => $pool->name,
                'sort_order' => $pool->sort_order,
                'participant_ids' => EventParticipant::where('event_id', $event->id)
                    ->where('pool_id', $pool->id)->pluck('participant_id')->all(),
            ])->all();

        $fixtures = Fixture::where('event_id', $event->id)->orderBy('match_number')->get()
            ->map(fn (Fixture $fixture) => $fixture->only([
                'pool_id', 'stage', 'round', 'match_number', 'home_participant_id',
                'away_participant_id', 'venue', 'scheduled_at', 'status', 'notes',
            ]))->all();

        $nextVersion = ((int) DrawVersion::where('event_id', $event->id)->max('version')) + 1;

        return DrawVersion::create([
            'organization_id' => $event->organization_id,
            'event_id' => $event->id,
            'actor_id' => Auth::id(),
            'version' => $nextVersion,
            'action' => $action,
            'seed' => $seed,
            'snapshot' => ['pools' => $pools, 'fixtures' => $fixtures],
        ]);
    }
}
