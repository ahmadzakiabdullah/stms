<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Session;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class RateLimitingTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    public function test_match_mutations_are_rate_limited(): void
    {
        $org = Organization::factory()->create();
        $session = Session::factory()->create(['organization_id' => $org->id]);
        $tournament = Tournament::factory()->create(['organization_id' => $org->id, 'session_id' => $session->id]);
        $sport = Sport::factory()->create(['organization_id' => $org->id]);
        $category = SportCategory::factory()->create(['organization_id' => $org->id, 'sport_id' => $sport->id]);
        $event = Event::factory()->create([
            'organization_id' => $org->id,
            'tournament_id' => $tournament->id,
            'sport_id' => $sport->id,
            'sport_category_id' => $category->id,
        ]);

        $user = $this->createSuperAdmin();

        for ($i = 0; $i < 30; $i++) {
            $response = $this->actingAs($user)->post(route('matches.store'), [
                'event_id' => $event->id,
                'home_participant_id' => 'test-1',
                'away_participant_id' => 'test-2',
                'status' => 'scheduled',
            ]);
        }

        $response = $this->actingAs($user)->post(route('matches.store'), [
            'event_id' => $event->id,
            'home_participant_id' => 'test-3',
            'away_participant_id' => 'test-4',
            'status' => 'scheduled',
        ]);

        $response->assertStatus(429);
    }

    public function test_result_mutations_are_rate_limited(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createSuperAdmin();

        for ($i = 0; $i < 30; $i++) {
            $response = $this->actingAs($user)->post(route('results.store'), [
                'match_id' => 'test-id',
                'score_home' => 1,
                'score_away' => 0,
            ]);
        }

        $response = $this->actingAs($user)->post(route('results.store'), [
            'match_id' => 'test-id-2',
            'score_home' => 2,
            'score_away' => 1,
        ]);

        $response->assertStatus(429);
    }

    public function test_ranking_strategy_updates_are_rate_limited(): void
    {
        $org = Organization::factory()->create();
        $session = Session::factory()->create(['organization_id' => $org->id]);
        $tournament = Tournament::factory()->create(['organization_id' => $org->id, 'session_id' => $session->id]);
        $user = $this->createSuperAdmin();

        for ($i = 0; $i < 10; $i++) {
            $response = $this->actingAs($user)->put(route('rankings.updateStrategy', ['tournament' => $tournament->id]), [
                'strategy' => 'points',
            ]);
        }

        $response = $this->actingAs($user)->put(route('rankings.updateStrategy', ['tournament' => $tournament->id]), [
            'strategy' => 'win_rate',
        ]);

        $response->assertStatus(429);
    }

    public function test_exports_are_rate_limited(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createStaffUser($org);

        for ($i = 0; $i < 10; $i++) {
            $response = $this->actingAs($user)->get(route('exports.fixtures.pdf'));
        }

        $response = $this->actingAs($user)->get(route('exports.fixtures.pdf'));

        $response->assertStatus(429);
    }

    public function test_match_index_not_rate_limited(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createStaffUser($org);

        for ($i = 0; $i < 35; $i++) {
            $response = $this->actingAs($user)->get(route('matches.index'));
        }

        $response->assertOk();
    }

    public function test_results_index_not_rate_limited(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createStaffUser($org);

        for ($i = 0; $i < 35; $i++) {
            $response = $this->actingAs($user)->get(route('results.index'));
        }

        $response->assertOk();
    }
}
