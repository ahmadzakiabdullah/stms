<?php

namespace Tests\Feature\Policies;

use App\Models\Organization;
use App\Models\Sport;
use App\Policies\SportPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class SportPolicyTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    private SportPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new SportPolicy;
    }

    public function test_super_admin_can_perform_all_actions(): void
    {
        $super = $this->createSuperAdmin();
        $org = Organization::factory()->create();
        $sport = Sport::factory()->create(['organization_id' => $org->id]);

        $this->assertTrue($this->policy->viewAny($super));
        $this->assertTrue($this->policy->view($super, $sport));
        $this->assertTrue($this->policy->create($super));
        $this->assertTrue($this->policy->update($super, $sport));
        $this->assertTrue($this->policy->delete($super, $sport));
    }

    public function test_org_admin_and_sport_coordinator_can_view_any_and_create_but_update_delete_with_perms_in_own_org(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $adminA = $this->createOrgAdmin($orgA);
        $coordinatorA = $this->createStaffUser($orgA);
        $coordinatorA->assignRole('sport-coordinator');

        $sportA = Sport::factory()->create(['organization_id' => $orgA->id]);
        $sportB = Sport::factory()->create(['organization_id' => $orgB->id]);

        // Org admin
        $this->assertTrue($this->policy->viewAny($adminA));
        $this->assertTrue($this->policy->create($adminA));
        $this->assertTrue($this->policy->view($adminA, $sportA));
        $this->assertTrue($this->policy->update($adminA, $sportA));
        $this->assertTrue($this->policy->delete($adminA, $sportA));

        // Sport coordinator via role for viewAny/create, needs perm for edit/delete
        $this->assertTrue($this->policy->viewAny($coordinatorA));
        $this->assertTrue($this->policy->create($coordinatorA));
        $this->assertFalse($this->policy->update($coordinatorA, $sportA));
        $this->assertFalse($this->policy->delete($coordinatorA, $sportA));

        // Cross org
        $this->assertFalse($this->policy->view($adminA, $sportB));

        // Grant perms to coordinator
        Permission::firstOrCreate(['name' => 'edit sports', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete sports', 'guard_name' => 'web']);
        $coordinatorA->givePermissionTo(['edit sports', 'delete sports']);

        $this->assertTrue($this->policy->update($coordinatorA, $sportA));
        $this->assertTrue($this->policy->delete($coordinatorA, $sportA));
    }

    public function test_staff_without_perms_has_limited_access(): void
    {
        $org = Organization::factory()->create();
        $staff = $this->createStaffUser($org);
        $sport = Sport::factory()->create(['organization_id' => $org->id]);

        $this->assertFalse($this->policy->viewAny($staff));
        $this->assertTrue($this->policy->view($staff, $sport)); // same org
        $this->assertFalse($this->policy->create($staff));
        $this->assertFalse($this->policy->update($staff, $sport));
        $this->assertFalse($this->policy->delete($staff, $sport));
    }
}
