<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Result;
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
        $this->service = new RankingService();
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
}
