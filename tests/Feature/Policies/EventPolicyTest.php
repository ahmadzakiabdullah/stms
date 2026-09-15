<?php

namespace Tests\Feature\Policies;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\Tournament;
use App\Policies\EventPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class EventPolicyTest extends TestCase
{
    use RefreshDatabase, CreatesTenantUsers;

    private EventPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new EventPolicy();
    }

    public function test_super_admin_can_perform_all_actions(): void
    {
        $super = $this->createSuperAdmin();
        $org = Organization::factory()->create();
        $tournament = Tournament::factory()->create(['organization_id' => $org->id]);
        $sport = Sport::factory()->create(['organization_id' => $org->id]);
        $category = SportCategory::factory()->forSport($sport)->create();
        $event = Event::factory()->create([
            'organization_id' => $org->id,
            'tournament_id' => $tournament->id,
            'sport_id' => $sport->id,
            'sport_category_id' => $category->id,
        ]);

        $this->assertTrue($this->policy->viewAny($super));
        $this->assertTrue($this->policy->view($super, $event));
        $this->assertTrue($this->policy->create($super));
        $this->assertTrue($this->policy->update($super, $event));
        $this->assertTrue($this->policy->delete($super, $event));
    }

    public function test_org_admin_can_view_any_and_create_but_only_view_update_delete_own_org(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $orgAdminA = $this->createOrgAdmin($orgA);

        $tournamentA = Tournament::factory()->create(['organization_id' => $orgA->id]);
        $sportA = Sport::factory()->create(['organization_id' => $orgA->id]);
        $catA = SportCategory::factory()->forSport($sportA)->create();
        $eventA = Event::factory()->create([
            'organization_id' => $orgA->id,
            'tournament_id' => $tournamentA->id,
            'sport_id' => $sportA->id,
            'sport_category_id' => $catA->id,
        ]);

        $eventB = Event::factory()->create(['organization_id' => $orgB->id]);

        $this->assertTrue($this->policy->viewAny($orgAdminA));
        $this->assertTrue($this->policy->view($orgAdminA, $eventA));
        $this->assertTrue($this->policy->create($orgAdminA));
        $this->assertTrue($this->policy->update($orgAdminA, $eventA));
        $this->assertTrue($this->policy->delete($orgAdminA, $eventA));

        $this->assertFalse($this->policy->view($orgAdminA, $eventB));
        $this->assertFalse($this->policy->update($orgAdminA, $eventB));
        $this->assertFalse($this->policy->delete($orgAdminA, $eventB));
    }

    public function test_tournament_manager_can_view_any_and_create_but_update_delete_only_with_permission_and_own_org(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $managerA = $this->createStaffUser($orgA); // base staff
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'tournament-manager', 'guard_name' => 'web']);
        $managerA->assignRole('tournament-manager');

        $tournamentA = Tournament::factory()->create(['organization_id' => $orgA->id]);
        $sportA = Sport::factory()->create(['organization_id' => $orgA->id]);
        $catA = SportCategory::factory()->forSport($sportA)->create();
        $eventA = Event::factory()->create([
            'organization_id' => $orgA->id,
            'tournament_id' => $tournamentA->id,
            'sport_id' => $sportA->id,
            'sport_category_id' => $catA->id,
        ]);

        $eventB = Event::factory()->create(['organization_id' => $orgB->id]);

        // Can viewAny and create via role
        $this->assertTrue($this->policy->viewAny($managerA));
        $this->assertTrue($this->policy->create($managerA));

        // Cannot update/delete without permission even in own org
        $this->assertFalse($this->policy->update($managerA, $eventA));
        $this->assertFalse($this->policy->delete($managerA, $eventA));

        // Cross org false
        $this->assertFalse($this->policy->view($managerA, $eventB));

        // Grant permission
        Permission::firstOrCreate(['name' => 'edit events', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete events', 'guard_name' => 'web']);
        $managerA->givePermissionTo(['edit events', 'delete events']);

        $this->assertTrue($this->policy->update($managerA, $eventA));
        $this->assertTrue($this->policy->delete($managerA, $eventA));
    }

    public function test_staff_without_permissions_cannot_do_most_things(): void
    {
        $org = Organization::factory()->create();
        $staff = $this->createStaffUser($org);

        $tournament = Tournament::factory()->create(['organization_id' => $org->id]);
        $sport = Sport::factory()->create(['organization_id' => $org->id]);
        $cat = SportCategory::factory()->forSport($sport)->create();
        $event = Event::factory()->create([
            'organization_id' => $org->id,
            'tournament_id' => $tournament->id,
            'sport_id' => $sport->id,
            'sport_category_id' => $cat->id,
        ]);

        $this->assertFalse($this->policy->viewAny($staff));
        $this->assertTrue($this->policy->view($staff, $event)); // same org
        $this->assertFalse($this->policy->create($staff));
        $this->assertFalse($this->policy->update($staff, $event));
        $this->assertFalse($this->policy->delete($staff, $event));
    }

    public function test_staff_with_permissions_can_create_view_update_delete_in_own_org(): void
    {
        $org = Organization::factory()->create();
        $staff = $this->createStaffUser($org);

        Permission::firstOrCreate(['name' => 'view events', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create events', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit events', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete events', 'guard_name' => 'web']);

        $staff->givePermissionTo(['view events', 'create events', 'edit events', 'delete events']);

        $tournament = Tournament::factory()->create(['organization_id' => $org->id]);
        $sport = Sport::factory()->create(['organization_id' => $org->id]);
        $cat = SportCategory::factory()->forSport($sport)->create();
        $event = Event::factory()->create([
            'organization_id' => $org->id,
            'tournament_id' => $tournament->id,
            'sport_id' => $sport->id,
            'sport_category_id' => $cat->id,
        ]);

        $this->assertTrue($this->policy->viewAny($staff));
        $this->assertTrue($this->policy->view($staff, $event));
        $this->assertTrue($this->policy->create($staff));
        $this->assertTrue($this->policy->update($staff, $event));
        $this->assertTrue($this->policy->delete($staff, $event));
    }
}
