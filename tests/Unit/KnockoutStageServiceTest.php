<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Pool;
use App\Models\Result;
use App\Services\KnockoutStageService;
use App\Services\LeagueTableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnockoutStageServiceTest extends TestCase
{
    use RefreshDatabase;

    protected KnockoutStageService $service;

    protected Organization $organization;

    protected Event $event;

    protected Pool $groupA;

    protected Pool $groupB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new KnockoutStageService(new LeagueTableService);
        $this->organization = Organization::factory()->create();
        $this->event = Event::factory()->create([
            'organization_id' => $this->organization->id,
            'pool_size' => 4,
            'qualifiers_per_pool' => 2,
        ]);

        $this->groupA = $this->createPool('Group A', 0);
        $this->groupB = $this->createPool('Group B', 1);
    }

    private function createPool(string $name, int $sortOrder): Pool
    {
        return Pool::query()->create([
            'organization_id' => $this->organization->id,
            'event_id' => $this->event->id,
            'name' => $name,
            'sort_order' => $sortOrder,
        ]);
    }

    private function createTeam(string $slug): Participant
    {
        return Participant::factory()->create([
            'organization_id' => $this->organization->id,
            'slug' => $slug,
        ]);
    }

    private function addToPool(Participant $participant, Pool $pool): void
    {
        EventParticipant::query()->create([
            'organization_id' => $this->organization->id,
            'event_id' => $this->event->id,
            'participant_id' => $participant->id,
            'pool_id' => $pool->id,
            'registration_date' => now(),
            'status' => 'confirmed',
        ]);
    }

    private function createFixture(Pool $pool, int $matchNumber, Participant $home, Participant $away, ?array $result = null): Fixture
    {
        $fixture = Fixture::query()->create([
            'organization_id' => $this->organization->id,
            'event_id' => $this->event->id,
            'pool_id' => $pool->id,
            'stage' => 'group',
            'round' => 1,
            'match_number' => $matchNumber,
            'home_participant_id' => $home->id,
            'away_participant_id' => $away->id,
            'status' => $result ? 'completed' : 'scheduled',
        ]);

        if ($result) {
            Result::query()->create([
                'organization_id' => $this->organization->id,
                'match_id' => $fixture->id,
                'score_home' => $result['home'],
                'score_away' => $result['away'],
                'winner_participant_id' => $result['home'] === $result['away'] ? null : ($result['home'] > $result['away'] ? $home->id : $away->id),
            ]);
        }

        return $fixture;
    }

    /**
     * Build a completed league: 4 teams per group, all fixtures with results.
     * Returns the teams per group ordered by finish (first = #1, last = #4).
     */
    private function completeLeague(): array
    {
        $teamsA = [
            'a1' => $this->createTeam('a1'),
            'a2' => $this->createTeam('a2'),
            'a3' => $this->createTeam('a3'),
            'a4' => $this->createTeam('a4'),
        ];
        $teamsB = [
            'b1' => $this->createTeam('b1'),
            'b2' => $this->createTeam('b2'),
            'b3' => $this->createTeam('b3'),
            'b4' => $this->createTeam('b4'),
        ];

        foreach ($teamsA as $team) {
            $this->addToPool($team, $this->groupA);
        }
        foreach ($teamsB as $team) {
            $this->addToPool($team, $this->groupB);
        }

        // Group A: a1 beats everyone, a2 beats a3 & a4, a3 beats a4.
        $this->createFixture($this->groupA, 1, $teamsA['a1'], $teamsA['a2'], ['home' => 3, 'away' => 0]);
        $this->createFixture($this->groupA, 2, $teamsA['a3'], $teamsA['a4'], ['home' => 2, 'away' => 1]);
        $this->createFixture($this->groupA, 3, $teamsA['a1'], $teamsA['a4'], ['home' => 4, 'away' => 0]);
        $this->createFixture($this->groupA, 4, $teamsA['a2'], $teamsA['a3'], ['home' => 1, 'away' => 0]);
        $this->createFixture($this->groupA, 5, $teamsA['a1'], $teamsA['a3'], ['home' => 5, 'away' => 1]);
        $this->createFixture($this->groupA, 6, $teamsA['a2'], $teamsA['a4'], ['home' => 2, 'away' => 0]);

        // Group B: b1 beats everyone, b2 beats b3 & b4, b3 beats b4.
        $this->createFixture($this->groupB, 7, $teamsB['b1'], $teamsB['b2'], ['home' => 2, 'away' => 1]);
        $this->createFixture($this->groupB, 8, $teamsB['b3'], $teamsB['b4'], ['home' => 3, 'away' => 2]);
        $this->createFixture($this->groupB, 9, $teamsB['b1'], $teamsB['b4'], ['home' => 4, 'away' => 0]);
        $this->createFixture($this->groupB, 10, $teamsB['b2'], $teamsB['b3'], ['home' => 2, 'away' => 0]);
        $this->createFixture($this->groupB, 11, $teamsB['b1'], $teamsB['b3'], ['home' => 3, 'away' => 0]);
        $this->createFixture($this->groupB, 12, $teamsB['b2'], $teamsB['b4'], ['home' => 1, 'away' => 0]);

        return [
            'groupA' => ['a1' => $teamsA['a1'], 'a2' => $teamsA['a2'], 'a3' => $teamsA['a3'], 'a4' => $teamsA['a4']],
            'groupB' => ['b1' => $teamsB['b1'], 'b2' => $teamsB['b2'], 'b3' => $teamsB['b3'], 'b4' => $teamsB['b4']],
        ];
    }

    public function test_league_complete_is_false_when_no_group_fixtures(): void
    {
        $this->assertFalse($this->service->leagueComplete($this->event));
    }

    public function test_league_complete_is_false_while_matches_remain(): void
    {
        $teams = $this->completeLeague();

        // Mark one fixture back to scheduled.
        Fixture::query()
            ->where('event_id', $this->event->id)
            ->where('stage', 'group')
            ->orderBy('match_number')
            ->first()
            ->update(['status' => 'scheduled']);

        $this->assertFalse($this->service->leagueComplete($this->event));

        // Restore and confirm true.
        Fixture::query()
            ->where('event_id', $this->event->id)
            ->where('stage', 'group')
            ->update(['status' => 'completed']);

        $this->assertTrue($this->service->leagueComplete($this->event));
    }

    public function test_generate_creates_cross_pool_bracket_with_resolved_semis(): void
    {
        $teams = $this->completeLeague();

        $count = $this->service->generate($this->event);

        $this->assertSame(4, $count);

        $semis = $this->service->fixtures($this->event);

        $this->assertCount(4, $semis);

        $sf1 = $semis[0];
        $sf2 = $semis[1];

        $this->assertSame('semi_final', $sf1->stage);
        $this->assertSame($teams['groupA']['a1']->id, $sf1->home_participant_id);
        $this->assertSame($teams['groupB']['b2']->id, $sf1->away_participant_id);

        $this->assertSame($teams['groupB']['b1']->id, $sf2->home_participant_id);
        $this->assertSame($teams['groupA']['a2']->id, $sf2->away_participant_id);

        $this->assertSame('bronze', $semis[2]->stage);
        $this->assertNull($semis[2]->home_participant_id);

        $this->assertSame('final', $semis[3]->stage);
        $this->assertNull($semis[3]->home_participant_id);

        // Match numbers continue from the league.
        $this->assertSame(13, $semis[0]->match_number);
        $this->assertSame(16, $semis[3]->match_number);
    }

    public function test_generate_throws_when_league_not_complete(): void
    {
        $teams = $this->completeLeague();

        Fixture::query()
            ->where('event_id', $this->event->id)
            ->where('stage', 'group')
            ->latest('match_number')
            ->first()
            ->update(['status' => 'scheduled']);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->generate($this->event);
    }

    public function test_generate_throws_when_stage_already_exists(): void
    {
        $this->completeLeague();
        $this->service->generate($this->event);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->generate($this->event);
    }

    public function test_generate_throws_for_single_group_event(): void
    {
        Pool::query()->where('event_id', $this->event->id)->where('name', 'Group B')->delete();

        $teamsA = collect(['a1', 'a2'])->map(fn ($slug) => $this->createTeam($slug));
        foreach ($teamsA as $team) {
            $this->addToPool($team, $this->groupA);
        }
        $this->createFixture($this->groupA, 1, $teamsA[0], $teamsA[1], ['home' => 2, 'away' => 0]);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->generate($this->event);
    }

    public function test_sync_bracket_fills_final_and_bronze_from_semi_results(): void
    {
        $teams = $this->completeLeague();
        $this->service->generate($this->event);

        $semis = $this->service->fixtures($this->event);
        $sf1 = $semis[0];
        $sf2 = $semis[1];

        // SF1: a1 beats b2. SF2: b1 beats a2.
        Result::query()->create([
            'organization_id' => $this->organization->id,
            'match_id' => $sf1->id,
            'score_home' => 2,
            'score_away' => 1,
            'winner_participant_id' => $teams['groupA']['a1']->id,
        ]);
        Result::query()->create([
            'organization_id' => $this->organization->id,
            'match_id' => $sf2->id,
            'score_home' => 3,
            'score_away' => 2,
            'winner_participant_id' => $teams['groupB']['b1']->id,
        ]);

        $this->service->syncBracket($this->event);

        $fixtures = $this->service->fixtures($this->event);

        $final = $fixtures[3];
        $bronze = $fixtures[2];

        $this->assertSame($teams['groupA']['a1']->id, $final->fresh()->home_participant_id);
        $this->assertSame($teams['groupB']['b1']->id, $final->fresh()->away_participant_id);

        $this->assertSame($teams['groupB']['b2']->id, $bronze->fresh()->home_participant_id);
        $this->assertSame($teams['groupA']['a2']->id, $bronze->fresh()->away_participant_id);
    }

    public function test_sync_bracket_fills_each_side_after_single_semi_final(): void
    {
        $teams = $this->completeLeague();
        $this->service->generate($this->event);

        $semis = $this->service->fixtures($this->event);
        $sf1 = $semis[0];

        // Only SF1 recorded: a1 beats b2.
        Result::query()->create([
            'organization_id' => $this->organization->id,
            'match_id' => $sf1->id,
            'score_home' => 2,
            'score_away' => 1,
            'winner_participant_id' => $teams['groupA']['a1']->id,
        ]);

        $this->service->syncBracket($this->event);

        $fixtures = $this->service->fixtures($this->event);

        // Final home + Bronze home filled; away sides still TBD.
        $this->assertSame($teams['groupA']['a1']->id, $fixtures[3]->fresh()->home_participant_id);
        $this->assertNull($fixtures[3]->fresh()->away_participant_id);

        $this->assertSame($teams['groupB']['b2']->id, $fixtures[2]->fresh()->home_participant_id);
        $this->assertNull($fixtures[2]->fresh()->away_participant_id);
    }

    public function test_sync_bracket_does_nothing_when_semis_incomplete(): void
    {
        $teams = $this->completeLeague();
        $this->service->generate($this->event);

        $this->service->syncBracket($this->event);

        $fixtures = $this->service->fixtures($this->event);

        $this->assertNull($fixtures[2]->fresh()->home_participant_id);
        $this->assertNull($fixtures[3]->fresh()->home_participant_id);
    }
}
