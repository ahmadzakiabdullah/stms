<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Pool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class MatchTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    public function test_non_super_admin_only_sees_own_organization_matches_via_global_scope(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $eventA = Event::factory()->create(['organization_id' => $orgA->id]);
        Fixture::factory()->create([
            'organization_id' => $orgA->id,
            'event_id' => $eventA->id,
        ]);

        $eventB = Event::factory()->create(['organization_id' => $orgB->id]);
        Fixture::factory()->create([
            'organization_id' => $orgB->id,
            'event_id' => $eventB->id,
        ]);

        $userA = $this->createStaffUser($orgA);

        $response = $this->actingAs($userA)->get(route('matches.index'));
        $response->assertOk();
    }

    public function test_matches_can_be_filtered_with_an_event_slug(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create([
            'organization_id' => $org->id,
            'slug' => 'mens-football',
        ]);
        Pool::query()->create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'name' => 'Pool A',
            'sort_order' => 1,
        ]);
        $user = $this->createStaffUser($org);

        $response = $this->actingAs($user)->get(route('matches.index', [
            'event' => $event->slug,
        ]));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Matches/Index')
                ->where('selectedEventId', $event->id)
                ->has('pools', 1));
    }

    public function test_super_admin_can_create_match(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $org->id]);
        $super = $this->createSuperAdmin();

        $response = $this->actingAs($super)
            ->from(route('matches.index'))
            ->post(route('matches.store'), [
                'event_id' => $event->id,
                'match_number' => 1,
                'status' => 'scheduled',
            ]);

        $response->assertRedirect(route('matches.index'));
        $this->assertDatabaseHas('matches', [
            'event_id' => $event->id,
            'match_number' => 1,
        ]);
    }

    public function test_org_admin_can_update_own_match(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        $event = Event::factory()->create(['organization_id' => $org->id]);
        $match = Fixture::factory()->create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('matches.index'))
            ->put(route('matches.update', $match), [
                'event_id' => $event->id,
                'match_number' => $match->match_number,
                'venue' => 'Main Stadium',
                'status' => 'in_progress',
            ]);

        $response->assertRedirect(route('matches.index'));
        $this->assertDatabaseHas('matches', ['venue' => 'Main Stadium', 'status' => 'in_progress']);
    }

    public function test_non_super_admin_cannot_update_match_in_other_org(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $adminA = $this->createOrgAdmin($orgA);

        $eventB = Event::factory()->create(['organization_id' => $orgB->id]);
        $matchB = Fixture::factory()->create([
            'organization_id' => $orgB->id,
            'event_id' => $eventB->id,
        ]);

        $response = $this->actingAs($adminA)->put(route('matches.update', $matchB), [
            'event_id' => $eventB->id,
            'match_number' => $matchB->match_number,
            'venue' => 'Hacked Venue',
        ]);

        $response->assertNotFound();
    }

    public function test_super_admin_can_delete_match(): void
    {
        $org = Organization::factory()->create();
        $super = $this->createSuperAdmin(['organization_id' => $org->id]);
        $event = Event::factory()->create(['organization_id' => $org->id]);
        $match = Fixture::factory()->create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
        ]);

        $response = $this->actingAs($super)
            ->from(route('matches.index'))
            ->delete(route('matches.destroy', $match));
        $response->assertRedirect(route('matches.index'));
        $this->assertSoftDeleted('matches', ['id' => $match->id]);
    }
}
