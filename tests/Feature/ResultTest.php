<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Result;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class ResultTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    public function test_non_super_admin_only_sees_own_organization_results_via_global_scope(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $eventA = Event::factory()->create(['organization_id' => $orgA->id]);
        $matchA = Fixture::factory()->create(['organization_id' => $orgA->id, 'event_id' => $eventA->id]);
        Result::factory()->create(['organization_id' => $orgA->id, 'match_id' => $matchA->id]);

        $eventB = Event::factory()->create(['organization_id' => $orgB->id]);
        $matchB = Fixture::factory()->create(['organization_id' => $orgB->id, 'event_id' => $eventB->id]);
        Result::factory()->create(['organization_id' => $orgB->id, 'match_id' => $matchB->id]);

        $userA = $this->createStaffUser($orgA);

        $response = $this->actingAs($userA)->get(route('results.index'));
        $response->assertOk();
    }

    public function test_super_admin_can_create_result(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $org->id]);
        $match = Fixture::factory()->create(['organization_id' => $org->id, 'event_id' => $event->id]);
        $super = $this->createSuperAdmin();

        $response = $this->actingAs($super)->post(route('results.store'), [
            'match_id' => $match->id,
            'score_home' => 3,
            'score_away' => 1,
        ]);

        $response->assertRedirect(route('results.index'));
        $this->assertDatabaseHas('results', [
            'match_id' => $match->id,
            'score_home' => 3,
            'score_away' => 1,
        ]);
    }

    public function test_org_admin_can_update_own_result(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        $event = Event::factory()->create(['organization_id' => $org->id]);
        $match = Fixture::factory()->create(['organization_id' => $org->id, 'event_id' => $event->id]);
        $result = Result::factory()->create([
            'organization_id' => $org->id,
            'match_id' => $match->id,
            'score_home' => 1,
            'score_away' => 0,
        ]);

        $response = $this->actingAs($admin)->put(route('results.update', $result), [
            'match_id' => $match->id,
            'score_home' => 2,
            'score_away' => 1,
        ]);

        $response->assertRedirect(route('results.index'));
        $this->assertDatabaseHas('results', ['score_home' => 2, 'score_away' => 1]);
    }

    public function test_non_super_admin_cannot_update_result_in_other_org(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $adminA = $this->createOrgAdmin($orgA);

        $eventB = Event::factory()->create(['organization_id' => $orgB->id]);
        $matchB = Fixture::factory()->create(['organization_id' => $orgB->id, 'event_id' => $eventB->id]);
        $resultB = Result::factory()->create([
            'organization_id' => $orgB->id,
            'match_id' => $matchB->id,
        ]);

        $response = $this->actingAs($adminA)->put(route('results.update', $resultB), [
            'match_id' => $matchB->id,
            'score_home' => 99,
        ]);

        $response->assertNotFound();
    }

    public function test_super_admin_can_delete_result(): void
    {
        $org = Organization::factory()->create();
        $super = $this->createSuperAdmin(['organization_id' => $org->id]);
        $match = Fixture::factory()->create(['organization_id' => $org->id]);
        $result = Result::factory()->create([
            'organization_id' => $org->id,
            'match_id' => $match->id,
        ]);

        $response = $this->actingAs($super)->delete(route('results.destroy', $result));
        $response->assertRedirect(route('results.index'));
        $this->assertSoftDeleted('results', ['id' => $result->id]);
    }
}
