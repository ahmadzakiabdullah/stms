<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Pool;
use App\Models\Result;
use App\Services\LeagueTableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeagueTableServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LeagueTableService $service;

    protected Organization $organization;

    protected Pool $pool;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new LeagueTableService;
        $this->organization = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $this->organization->id]);
        $this->pool = Pool::query()->create([
            'organization_id' => $this->organization->id,
            'event_id' => $event->id,
            'name' => 'Group A',
            'sort_order' => 0,
        ]);
    }

    private function createTeam(string $slug): Participant
    {
        return Participant::factory()->create([
            'organization_id' => $this->organization->id,
            'slug' => $slug,
        ]);
    }

    private function addToPool(Participant $participant): void
    {
        EventParticipant::query()->create([
            'organization_id' => $this->organization->id,
            'event_id' => $this->pool->event_id,
            'participant_id' => $participant->id,
            'pool_id' => $this->pool->id,
            'registration_date' => now(),
            'status' => 'confirmed',
        ]);
    }

    private function createFixture(int $matchNumber, Participant $home, Participant $away, int $homeScore, int $awayScore): void
    {
        $fixture = Fixture::query()->create([
            'organization_id' => $this->organization->id,
            'event_id' => $this->pool->event_id,
            'pool_id' => $this->pool->id,
            'match_number' => $matchNumber,
            'home_participant_id' => $home->id,
            'away_participant_id' => $away->id,
            'status' => 'completed',
        ]);

        Result::query()->create([
            'organization_id' => $this->organization->id,
            'match_id' => $fixture->id,
            'score_home' => $homeScore,
            'score_away' => $awayScore,
        ]);
    }

    private function findRow($standings, string $participantId): array
    {
        $row = $standings->firstWhere('participant_id', $participantId);

        return $row ?? [];
    }

    public function test_standings_are_empty_for_pool_without_participants_and_completed_matches(): void
    {
        $standings = $this->service->standings($this->pool);

        $this->assertTrue($standings->isEmpty());
    }

    public function test_pool_participants_appear_with_zeroed_stats_before_first_match(): void
    {
        $teamA = $this->createTeam('team-a');
        $teamB = $this->createTeam('team-b');
        $this->addToPool($teamA);
        $this->addToPool($teamB);

        $standings = $this->service->standings($this->pool);

        $this->assertCount(2, $standings);

        $a = $this->findRow($standings, $teamA->id);
        $b = $this->findRow($standings, $teamB->id);

        foreach ([$a, $b] as $row) {
            $this->assertSame(0, $row['played']);
            $this->assertSame(0, $row['won']);
            $this->assertSame(0, $row['drawn']);
            $this->assertSame(0, $row['lost']);
            $this->assertSame(0, $row['goals_for']);
            $this->assertSame(0, $row['goals_against']);
            $this->assertSame(0, $row['goal_difference']);
            $this->assertSame(0, $row['points']);
        }
    }

    public function test_standings_calculate_win_draw_loss_and_points(): void
    {
        $teamA = $this->createTeam('team-a');
        $teamB = $this->createTeam('team-b');
        $this->addToPool($teamA);
        $this->addToPool($teamB);

        // A beats B 3-1
        $this->createFixture(1, $teamA, $teamB, 3, 1);

        $standings = $this->service->standings($this->pool);
        $this->assertCount(2, $standings);

        $a = $this->findRow($standings, $teamA->id);
        $b = $this->findRow($standings, $teamB->id);

        $this->assertSame(1, $a['played']);
        $this->assertSame(1, $a['won']);
        $this->assertSame(0, $a['drawn']);
        $this->assertSame(0, $a['lost']);
        $this->assertSame(3, $a['goals_for']);
        $this->assertSame(1, $a['goals_against']);
        $this->assertSame(2, $a['goal_difference']);
        $this->assertSame(3, $a['points']);

        $this->assertSame(0, $b['won']);
        $this->assertSame(1, $b['lost']);
        $this->assertSame(0, $b['points']);
    }

    public function test_draw_awards_one_point_to_each_team(): void
    {
        $teamA = $this->createTeam('team-a');
        $teamB = $this->createTeam('team-b');
        $this->addToPool($teamA);
        $this->addToPool($teamB);

        $this->createFixture(1, $teamA, $teamB, 2, 2);

        $standings = $this->service->standings($this->pool);

        $this->assertSame(1, $this->findRow($standings, $teamA->id)['drawn']);
        $this->assertSame(1, $this->findRow($standings, $teamA->id)['points']);
        $this->assertSame(1, $this->findRow($standings, $teamB->id)['drawn']);
        $this->assertSame(1, $this->findRow($standings, $teamB->id)['points']);
    }

    public function test_standings_are_sorted_by_points_then_goal_difference(): void
    {
        $teamA = $this->createTeam('team-a');
        $teamB = $this->createTeam('team-b');
        $teamC = $this->createTeam('team-c');
        foreach ([$teamA, $teamB, $teamC] as $team) {
            $this->addToPool($team);
        }

        // A: 1W (5-0), B: 1W (1-0), C: 0W 1L
        $this->createFixture(1, $teamA, $teamC, 5, 0);
        $this->createFixture(2, $teamB, $teamC, 1, 0);

        $standings = $this->service->standings($this->pool);

        $this->assertSame($teamA->id, $standings[0]['participant_id']);
        $this->assertSame($teamB->id, $standings[1]['participant_id']);
        $this->assertSame($teamC->id, $standings[2]['participant_id']);

        $this->assertSame(5, $standings[0]['goal_difference']);
        $this->assertSame(1, $standings[1]['goal_difference']);
    }

    public function test_scheduled_matches_do_not_contribute_to_standings(): void
    {
        $teamA = $this->createTeam('team-a');
        $teamB = $this->createTeam('team-b');
        $this->addToPool($teamA);
        $this->addToPool($teamB);

        Fixture::query()->create([
            'organization_id' => $this->organization->id,
            'event_id' => $this->pool->event_id,
            'pool_id' => $this->pool->id,
            'match_number' => 1,
            'home_participant_id' => $teamA->id,
            'away_participant_id' => $teamB->id,
            'status' => 'scheduled',
        ]);

        $standings = $this->service->standings($this->pool);

        $this->assertCount(2, $standings);
        $this->assertSame(0, $standings->first()['played']);
        $this->assertSame(0, $standings->first()['points']);
    }

    public function test_team_not_in_pool_is_excluded(): void
    {
        $teamA = $this->createTeam('team-a');
        $teamB = $this->createTeam('team-b');
        $teamC = $this->createTeam('team-c');
        $this->addToPool($teamA);
        $this->addToPool($teamB);

        $this->createFixture(1, $teamA, $teamB, 1, 0);

        $standings = $this->service->standings($this->pool);

        $this->assertCount(2, $standings);
        $this->assertNull($this->findRow($standings, $teamC->id)['participant_id'] ?? null);
    }
}
