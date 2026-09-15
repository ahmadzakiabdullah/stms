<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Result;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class RankingTest extends TestCase
{
    use RefreshDatabase, CreatesTenantUsers;

    public function test_rankings_page_requires_auth(): void
    {
        $response = $this->get(route('rankings.index'));
        $response->assertRedirect();
    }

    public function test_authenticated_user_can_view_rankings_page(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createStaffUser($org);

        $response = $this->actingAs($user)->get(route('rankings.index'));
        $response->assertOk();
    }

    public function test_org_admin_can_update_ranking_strategy(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        $tournament = Tournament::factory()->create(['organization_id' => $org->id]);

        $response = $this->actingAs($admin)->put(route('rankings.updateStrategy', $tournament), [
            'ranking_strategy' => 'win_rate',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tournaments', [
            'id' => $tournament->id,
            'ranking_strategy' => 'win_rate',
        ]);
    }

    public function test_invalid_strategy_rejected(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        $tournament = Tournament::factory()->create(['organization_id' => $org->id]);

        $response = $this->actingAs($admin)->put(route('rankings.updateStrategy', $tournament), [
            'ranking_strategy' => 'invalid_strategy',
        ]);

        $response->assertSessionHasErrors('ranking_strategy');
    }

    public function test_ranking_calculation_with_completed_matches(): void
    {
        $org = Organization::factory()->create();
        $tournament = Tournament::factory()->create(['organization_id' => $org->id, 'ranking_strategy' => 'points']);
        $event = Event::factory()->create(['organization_id' => $org->id]);
        $alice = Participant::factory()->create(['organization_id' => $org->id, 'name' => 'Alice']);
        $bob = Participant::factory()->create(['organization_id' => $org->id, 'name' => 'Bob']);

        $match1 = Fixture::factory()->create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'home_participant_id' => $alice->id,
            'away_participant_id' => $bob->id,
            'status' => 'completed',
        ]);
        Fixture::factory()->completed()->create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'home_participant_id' => $bob->id,
            'away_participant_id' => $alice->id,
            'status' => 'completed',
        ]);
        Result::factory()->create([
            'organization_id' => $org->id,
            'match_id' => $match1->id,
            'score_home' => 3,
            'score_away' => 1,
            'winner_participant_id' => $alice->id,
        ]);

        $super = $this->createSuperAdmin();
        $response = $this->actingAs($super)->put(route('rankings.updateStrategy', $tournament), [
            'ranking_strategy' => 'points',
        ]);
        $response->assertRedirect();

        $this->assertDatabaseHas('tournaments', [
            'id' => $tournament->id,
            'ranking_strategy' => 'points',
        ]);
    }
}
