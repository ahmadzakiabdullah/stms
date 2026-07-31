<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Fixture;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KnockoutStageService
{
    public const STAGE_GROUP = 'group';

    public const STAGE_SEMI_FINAL = 'semi_final';

    public const STAGE_BRONZE = 'bronze';

    public const STAGE_FINAL = 'final';

    public const KNOCKOUT_STAGES = [
        self::STAGE_SEMI_FINAL,
        self::STAGE_BRONZE,
        self::STAGE_FINAL,
    ];

    public function __construct(
        private readonly LeagueTableService $leagueTableService,
    ) {}

    /**
     * The league stage is complete when every group fixture has a result.
     */
    public function leagueComplete(Event $event): bool
    {
        $groupFixtureCount = Fixture::query()
            ->where('event_id', $event->id)
            ->where('stage', self::STAGE_GROUP)
            ->count();

        if ($groupFixtureCount === 0) {
            return false;
        }

        $completedCount = Fixture::query()
            ->where('event_id', $event->id)
            ->where('stage', self::STAGE_GROUP)
            ->where('status', 'completed')
            ->count();

        return $groupFixtureCount === $completedCount;
    }

    public function hasKnockoutStage(Event $event): bool
    {
        return Fixture::query()
            ->where('event_id', $event->id)
            ->whereIn('stage', self::KNOCKOUT_STAGES)
            ->exists();
    }

    /**
     * Generate the knockout stage once the league is complete.
     *
     * Standard cross-pool bracket for two groups:
     *   Semi-final 1: Group A #1 vs Group B #2
     *   Semi-final 2: Group B #1 vs Group A #2
     *   Bronze: losers of SF1/SF2 (participants resolved from results)
     *   Final:  winners of SF1/SF2 (participants resolved from results)
     *
     * @return int number of fixtures created
     */
    public function generate(Event $event): int
    {
        if (! $this->leagueComplete($event)) {
            throw new \InvalidArgumentException('The league stage must be fully completed before generating the knockout stage.');
        }

        if ($this->hasKnockoutStage($event)) {
            throw new \InvalidArgumentException('Knockout stage already generated for this event.');
        }

        $pools = $event->pools()->orderBy('sort_order')->get();

        if ($pools->count() < 2) {
            throw new \InvalidArgumentException('Knockout stage requires at least 2 groups.');
        }

        $qualifiers = $pools->mapWithKeys(function ($pool) use ($event) {
            $qualified = $this->leagueTableService->standings($pool)
                ->take($event->qualifiers_per_pool)
                ->pluck('participant_id')
                ->values();

            if ($qualified->count() < $event->qualifiers_per_pool) {
                throw new \InvalidArgumentException("Group {$pool->name} does not have enough participants to qualify.");
            }

            return [$pool->id => $qualified];
        });

        return DB::transaction(function () use ($event, $pools, $qualifiers) {
            $poolA = $pools->get(0);
            $poolB = $pools->get(1);

            $sf1Home = $qualifiers[$poolA->id]->get(0);
            $sf1Away = $qualifiers[$poolB->id]->get(1);
            $sf2Home = $qualifiers[$poolB->id]->get(0);
            $sf2Away = $qualifiers[$poolA->id]->get(1);

            $matchNumber = ((int) Fixture::query()->where('event_id', $event->id)->withTrashed()->max('match_number')) + 1;

            $orgId = $event->organization_id;
            $fixtures = [];

            foreach ([
                ['stage' => self::STAGE_SEMI_FINAL, 'round' => 1, 'home' => $sf1Home, 'away' => $sf1Away],
                ['stage' => self::STAGE_SEMI_FINAL, 'round' => 2, 'home' => $sf2Home, 'away' => $sf2Away],
                ['stage' => self::STAGE_BRONZE, 'round' => 3, 'home' => null, 'away' => null],
                ['stage' => self::STAGE_FINAL, 'round' => 4, 'home' => null, 'away' => null],
            ] as $fixture) {
                $fixtures[] = [
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'organization_id' => $orgId,
                    'event_id' => $event->id,
                    'pool_id' => null,
                    'stage' => $fixture['stage'],
                    'round' => $fixture['round'],
                    'match_number' => $matchNumber++,
                    'home_participant_id' => $fixture['home'],
                    'away_participant_id' => $fixture['away'],
                    'status' => 'scheduled',
                ];
            }

            Fixture::insert($fixtures);

            Log::info('Knockout stage generated', [
                'event_id' => $event->id,
                'fixtures' => count($fixtures),
            ]);

            return count($fixtures);
        });
    }

    /**
     * Resolve Bronze and Final participants from completed semi-final results.
     */
    public function syncBracket(Event $event): void
    {
        $semis = $event->matches()
            ->where('stage', self::STAGE_SEMI_FINAL)
            ->with('result')
            ->orderBy('round')
            ->get();

        if ($semis->count() < 2 || $semis->contains(fn (Fixture $f) => ! $f->result || ! $f->result->winner_participant_id)) {
            return;
        }

        $winners = $semis->map(fn (Fixture $f) => $f->result->winner_participant_id);
        $losers = $semis->map(function (Fixture $f) {
            $winner = $f->result->winner_participant_id;

            return $winner === $f->home_participant_id ? $f->away_participant_id : $f->home_participant_id;
        });

        DB::transaction(function () use ($event, $winners, $losers) {
            $event->matches()
                ->where('stage', self::STAGE_FINAL)
                ->update([
                    'home_participant_id' => $winners->get(0),
                    'away_participant_id' => $winners->get(1),
                ]);

            $event->matches()
                ->where('stage', self::STAGE_BRONZE)
                ->update([
                    'home_participant_id' => $losers->get(0),
                    'away_participant_id' => $losers->get(1),
                ]);
        });

        Log::info('Knockout bracket synced', ['event_id' => $event->id]);
    }

    /**
     * Knockout fixtures in display order (semi-finals, bronze, final).
     */
    public function fixtures(Event $event): Collection
    {
        return $event->matches()
            ->with(['homeParticipant', 'awayParticipant', 'result'])
            ->whereIn('stage', self::KNOCKOUT_STAGES)
            ->orderBy('round')
            ->orderBy('match_number')
            ->get();
    }
}
