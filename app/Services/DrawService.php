<?php

namespace App\Services;

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
     * Perform a random draw for an event:
     * 1. Delete existing pools and their associations
     * 2. Create new pools based on event pool_size and confirmed participant count
     * 3. Randomly assign confirmed participants to pools
     * 4. Generate round-robin fixtures within each pool
     */
    public function drawAndGenerateFixtures(Event $event): array
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

            $shuffled = $confirmedParticipants->shuffle();
            $poolIndex = 0;
            foreach ($shuffled as $ep) {
                $ep->update(['pool_id' => $pools[$poolIndex]->id]);
                $poolIndex = ($poolIndex + 1) % $numPools;
            }

            $fixturesCreated = 0;
            foreach ($pools as $pool) {
                $fixturesCreated += $this->generateRoundRobinFixtures($event, $pool);
            }

            Log::info('Draw completed', [
                'event_id' => $event->id,
                'pools' => $numPools,
                'participants' => $total,
                'fixtures' => $fixturesCreated,
            ]);

            return [
                'pools' => $numPools,
                'participants' => $total,
                'fixtures' => $fixturesCreated,
            ];
        });
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
        });
    }
}
