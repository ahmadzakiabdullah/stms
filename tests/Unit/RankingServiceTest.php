<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Result;
use App\Models\Session;
use App\Models\Tournament;
use App\Services\RankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingServiceTest extends TestCase
{
    use RefreshDatabase;

    private RankingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RankingService;
    }

    public function test_calculate_for_tournament_returns_ranked_participants(): void
    {
        $org = Organization::factory()->create();
        $tournament = Tournament::factory()->create([
            'organization_id' => $org->id,
            'ranking_strategy' => 'points',
        ]);
        $event = Event::factory()->create([
            'organization_id' => $org->id,
            'tournament_id' => $tournament->id,
        ]);

        $p1 = Participant::factory()->create(['organization_id' => $org->id, 'name' => 'Team Alpha']);
        $p2 = Participant::factory()->create(['organization_id' => $org->id, 'name' => 'Team Beta']);

        $match = Fixture::factory()->create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'home_participant_id' => $p1->id,
            'away_participant_id' => $p2->id,
            'status' => 'completed',
        ]);

        Result::factory()->create([
            'organization_id' => $org->id,
            'match_id' => $match->id,
            'score_home' => 3,
            'score_away' => 1,
            'winner_participant_id' => $p1->id,
        ]);

        $rankings = $this->service->calculateForTournament($tournament);

        $this->assertCount(2, $rankings);
        $this->assertEquals($p1->id, $rankings->first()['participant_id']);
        $this->assertEquals(3, $rankings->first()['points']);
    }

    public function test_win_rate_strategy(): void
    {
        $org = Organization::factory()->create();
        $tournament = Tournament::factory()->create([
            'organization_id' => $org->id,
            'ranking_strategy' => 'win_rate',
        ]);
        $event = Event::factory()->create([
            'organization_id' => $org->id,
            'tournament_id' => $tournament->id,
        ]);

        $p1 = Participant::factory()->create(['organization_id' => $org->id]);
        $p2 = Participant::factory()->create(['organization_id' => $org->id]);

        $match = Fixture::factory()->create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'home_participant_id' => $p1->id,
            'away_participant_id' => $p2->id,
            'status' => 'completed',
        ]);

        Result::factory()->create([
            'organization_id' => $org->id,
            'match_id' => $match->id,
            'score_home' => 2,
            'score_away' => 0,
            'winner_participant_id' => $p1->id,
        ]);

        $rankings = $this->service->calculateForTournament($tournament);

        $this->assertEquals(100.0, $rankings->first()['win_rate']);
    }

    public function test_empty_results_returns_empty(): void
    {
        $tournament = Tournament::factory()->create();
        $rankings = $this->service->calculateForTournament($tournament);
        $this->assertCount(0, $rankings);
    }

    public function test_medal_tally_awards_gold_silver_bronze_from_knockout_stage(): void
    {
        $org = Organization::factory()->create();
        $tournament = Tournament::factory()->create([
            'organization_id' => $org->id,
            'ranking_strategy' => 'medal_tally',
        ]);
        $event = Event::factory()->create([
            'organization_id' => $org->id,
            'tournament_id' => $tournament->id,
        ]);

        $gold = Participant::factory()->create(['organization_id' => $org->id, 'name' => 'FTKE']);
        $silver = Participant::factory()->create(['organization_id' => $org->id, 'name' => 'STEP']);
        $bronze = Participant::factory()->create(['organization_id' => $org->id, 'name' => 'FTKIP']);

        $final = Fixture::factory()->create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'stage' => 'final',
            'round' => 4,
            'home_participant_id' => $gold->id,
            'away_participant_id' => $silver->id,
            'status' => 'completed',
        ]);
        Result::factory()->create([
            'organization_id' => $org->id,
            'match_id' => $final->id,
            'score_home' => 2,
            'score_away' => 1,
            'winner_participant_id' => $gold->id,
        ]);

        $bronzeMatch = Fixture::factory()->create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'stage' => 'bronze',
            'round' => 3,
            'home_participant_id' => $bronze->id,
            'away_participant_id' => Participant::factory()->create(['organization_id' => $org->id, 'name' => 'FAIX'])->id,
            'status' => 'completed',
        ]);
        Result::factory()->create([
            'organization_id' => $org->id,
            'match_id' => $bronzeMatch->id,
            'score_home' => 3,
            'score_away' => 2,
            'winner_participant_id' => $bronze->id,
        ]);

        $rankings = $this->service->calculateForTournament($tournament);

        $this->assertSame($gold->id, $rankings[0]['participant_id']);
        $this->assertSame(1, $rankings[0]['gold']);
        $this->assertSame(0, $rankings[0]['silver']);
        $this->assertSame(0, $rankings[0]['bronze']);

        $this->assertSame($silver->id, $rankings[1]['participant_id']);
        $this->assertSame(0, $rankings[1]['gold']);
        $this->assertSame(1, $rankings[1]['silver']);

        $this->assertSame($bronze->id, $rankings[2]['participant_id']);
        $this->assertSame(1, $rankings[2]['bronze']);
    }

    public function test_medal_tally_sorts_by_gold_then_silver_then_bronze(): void
    {
        $org = Organization::factory()->create();
        $tournament = Tournament::factory()->create([
            'organization_id' => $org->id,
            'ranking_strategy' => 'medal_tally',
        ]);
        $event = Event::factory()->create([
            'organization_id' => $org->id,
            'tournament_id' => $tournament->id,
        ]);

        // Event 1: FTKE gold, STEP silver, FAIX bronze.
        $ftke = Participant::factory()->create(['organization_id' => $org->id, 'name' => 'FTKE']);
        $step = Participant::factory()->create(['organization_id' => $org->id, 'name' => 'STEP']);
        $faix = Participant::factory()->create(['organization_id' => $org->id, 'name' => 'FAIX']);
        $this->createCompletedKnockout($event, $ftke, $step, $faix);

        // Event 2: FTMK gold, FTKE silver, STEP bronze -> FTKE now 1G 1S.
        $event2 = Event::factory()->create([
            'organization_id' => $org->id,
            'tournament_id' => $tournament->id,
        ]);
        $ftmk = Participant::factory()->create(['organization_id' => $org->id, 'name' => 'FTMK']);
        $this->createCompletedKnockout($event2, $ftmk, $ftke, $step);

        $rankings = $this->service->calculateForTournament($tournament);

        $this->assertSame('FTKE', $rankings[0]['participant_name']);
        $this->assertSame(1, $rankings[0]['gold']);
        $this->assertSame(1, $rankings[0]['silver']);

        $this->assertSame('FTMK', $rankings[1]['participant_name']);
        $this->assertSame(1, $rankings[1]['gold']);

        $this->assertSame('STEP', $rankings[2]['participant_name']);
        $this->assertSame(1, $rankings[2]['silver']);
        $this->assertSame(1, $rankings[2]['bronze']);
    }

    public function test_medal_tally_ignores_events_without_completed_final(): void
    {
        $org = Organization::factory()->create();
        $tournament = Tournament::factory()->create([
            'organization_id' => $org->id,
            'ranking_strategy' => 'medal_tally',
        ]);
        $event = Event::factory()->create([
            'organization_id' => $org->id,
            'tournament_id' => $tournament->id,
        ]);

        $final = Fixture::factory()->create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'stage' => 'final',
            'home_participant_id' => Participant::factory()->create(['organization_id' => $org->id])->id,
            'away_participant_id' => Participant::factory()->create(['organization_id' => $org->id])->id,
            'status' => 'scheduled',
        ]);
        Result::factory()->create([
            'organization_id' => $org->id,
            'match_id' => $final->id,
        ]);

        $rankings = $this->service->calculateForTournament($tournament);

        $this->assertTrue($rankings->every(fn ($r) => $r['gold'] === 0 && $r['silver'] === 0 && $r['bronze'] === 0));
    }

    public function test_calculate_for_session_aggregates_medals_across_phases(): void
    {
        $org = Organization::factory()->create();
        $session = Session::factory()->create([
            'organization_id' => $org->id,
            'ranking_strategy' => 'medal_tally',
        ]);

        $ftke = Participant::factory()->create(['organization_id' => $org->id, 'name' => 'FTKE']);
        $step = Participant::factory()->create(['organization_id' => $org->id, 'name' => 'STEP']);
        $ftkip = Participant::factory()->create(['organization_id' => $org->id, 'name' => 'FTKIP']);

        $phase1 = Tournament::factory()->create([
            'organization_id' => $org->id,
            'session_id' => $session->id,
            'ranking_strategy' => 'medal_tally',
        ]);
        $event1 = Event::factory()->create([
            'organization_id' => $org->id,
            'tournament_id' => $phase1->id,
        ]);
        $this->createCompletedKnockout($event1, $ftke, $step, $ftkip);

        $phase2 = Tournament::factory()->create([
            'organization_id' => $org->id,
            'session_id' => $session->id,
            'ranking_strategy' => 'medal_tally',
        ]);
        $event2 = Event::factory()->create([
            'organization_id' => $org->id,
            'tournament_id' => $phase2->id,
        ]);
        $this->createCompletedKnockout($event2, $step, $ftke, $ftkip);

        $rankings = $this->service->calculateForSession($session);

        $byName = $rankings->keyBy('participant_name');

        $this->assertSame(1, $byName['FTKE']['gold']);
        $this->assertSame(1, $byName['FTKE']['silver']);
        $this->assertSame(0, $byName['FTKE']['bronze']);

        $this->assertSame(1, $byName['STEP']['gold']);
        $this->assertSame(1, $byName['STEP']['silver']);

        $this->assertSame(2, $byName['FTKIP']['bronze']);

        $this->assertSame('FTKE', $rankings->first()['participant_name']);
    }

    public function test_medal_tally_ties_share_the_same_rank_and_ignore_points(): void
    {
        $org = Organization::factory()->create();
        $tournament = Tournament::factory()->create([
            'organization_id' => $org->id,
            'ranking_strategy' => 'medal_tally',
        ]);

        $ftke = Participant::factory()->create(['organization_id' => $org->id, 'name' => 'FTKE']);
        $ftmk = Participant::factory()->create(['organization_id' => $org->id, 'name' => 'FTMK']);
        $step = Participant::factory()->create(['organization_id' => $org->id, 'name' => 'STEP']);
        $faix = Participant::factory()->create(['organization_id' => $org->id, 'name' => 'FAIX']);

        $event1 = Event::factory()->create(['organization_id' => $org->id, 'tournament_id' => $tournament->id]);
        $this->createCompletedKnockout($event1, $ftke, $step, $faix);

        $event2 = Event::factory()->create(['organization_id' => $org->id, 'tournament_id' => $tournament->id]);
        $this->createCompletedKnockout($event2, $ftmk, $step, $faix);

        $rankings = $this->service->calculateForTournament($tournament);

        $this->assertSame(1, $rankings[0]['rank']);
        $this->assertSame('FTKE', $rankings[0]['participant_name']);
        $this->assertSame(1, $rankings[1]['rank']);
        $this->assertSame('FTMK', $rankings[1]['participant_name']);
        $this->assertSame(3, $rankings[2]['rank']);
        $this->assertSame('STEP', $rankings[2]['participant_name']);
        $this->assertSame(2, $rankings[2]['silver']);
        $this->assertFalse(array_key_exists('points', $rankings[0]));
    }

    private function createCompletedKnockout(Event $event, Participant $gold, Participant $silver, Participant $bronze): void
    {
        $org = Organization::find($event->organization_id);
        $fourth = Participant::factory()->create(['organization_id' => $org->id, 'name' => $bronze->name.'-4th']);

        $final = Fixture::factory()->create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'stage' => 'final',
            'round' => 4,
            'home_participant_id' => $gold->id,
            'away_participant_id' => $silver->id,
            'status' => 'completed',
        ]);
        Result::factory()->create([
            'organization_id' => $org->id,
            'match_id' => $final->id,
            'score_home' => 2,
            'score_away' => 1,
            'winner_participant_id' => $gold->id,
        ]);

        $bronzeMatch = Fixture::factory()->create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'stage' => 'bronze',
            'round' => 3,
            'home_participant_id' => $bronze->id,
            'away_participant_id' => $fourth->id,
            'status' => 'completed',
        ]);
        Result::factory()->create([
            'organization_id' => $org->id,
            'match_id' => $bronzeMatch->id,
            'score_home' => 3,
            'score_away' => 2,
            'winner_participant_id' => $bronze->id,
        ]);
    }
}
