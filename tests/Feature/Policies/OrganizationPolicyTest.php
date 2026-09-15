<?php

namespace Tests\Feature\Policies;

use App\Models\Organization;
use App\Policies\OrganizationPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class OrganizationPolicyTest extends TestCase
{
    use RefreshDatabase, CreatesTenantUsers;

    private OrganizationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new OrganizationPolicy();
    }

    public function test_super_admin_can_perform_all_actions(): void
    {
        $super = $this->createSuperAdmin();
        $org = Organization::factory()->create();

        $this->assertTrue($this->policy->viewAny($super));
        $this->assertTrue($this->policy->view($super, $org));
        $this->assertTrue($this->policy->create($super));
        $this->assertTrue($this->policy->update($super, $org));
        $this->assertTrue($this->policy->delete($super, $org));
    }

    public function test_org_admin_can_view_any_create_but_only_view_update_delete_own_org(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $orgAdminA = $this->createOrgAdmin($orgA);

        $this->assertTrue($this->policy->viewAny($orgAdminA));
        $this->assertTrue($this->policy->create($orgAdminA));
        $this->assertTrue($this->policy->view($orgAdminA, $orgA));
        $this->assertTrue($this->policy->update($orgAdminA, $orgA));
        $this->assertTrue($this->policy->delete($orgAdminA, $orgA));

        $this->assertFalse($this->policy->view($orgAdminA, $orgB));
        $this->assertFalse($this->policy->update($orgAdminA, $orgB));
        $this->assertFalse($this->policy->delete($orgAdminA, $orgB));
    }

    public function test_staff_without_permission_has_limited_access(): void
    {
        $org = Organization::factory()->create();
        $staff = $this->createStaffUser($org);

        $this->assertFalse($this->policy->viewAny($staff));
        $this->assertTrue($this->policy->view($staff, $org)); // same org
        $this->assertFalse($this->policy->create($staff));
        $this->assertFalse($this->policy->update($staff, $org));
        $this->assertFalse($this->policy->delete($staff, $org));
    }

    public function test_staff_with_permissions_can_manage_in_own_org(): void
    {
        $org = Organization::factory()->create();
        $staff = $this->createStaffUser($org);

        Permission::firstOrCreate(['name' => 'view organizations', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create organizations', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit organizations', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete organizations', 'guard_name' => 'web']);

        $staff->givePermissionTo(['view organizations', 'create organizations', 'edit organizations', 'delete organizations']);

        $this->assertTrue($this->policy->viewAny($staff));
        $this->assertTrue($this->policy->create($staff));
        $this->assertTrue($this->policy->view($staff, $org));
        $this->assertTrue($this->policy->update($staff, $org));
        $this->assertTrue($this->policy->delete($staff, $org));
    }
}
