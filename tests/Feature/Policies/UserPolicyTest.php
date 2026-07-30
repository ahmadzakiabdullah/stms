<?php

namespace Tests\Feature\Policies;

use App\Models\Organization;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class UserPolicyTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    private UserPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new UserPolicy;
    }

    public function test_super_admin_can_perform_all_actions(): void
    {
        $super = $this->createSuperAdmin();
        $org = Organization::factory()->create();
        $user = $this->createStaffUser($org);

        $this->assertTrue($this->policy->viewAny($super));
        $this->assertTrue($this->policy->view($super, $user));
        $this->assertTrue($this->policy->create($super));
        $this->assertTrue($this->policy->update($super, $user));
        $this->assertTrue($this->policy->delete($super, $user));
    }

    public function test_org_admin_can_view_any_create_but_only_view_update_delete_own_org_non_super(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $orgAdminA = $this->createOrgAdmin($orgA);

        $userA = $this->createStaffUser($orgA);
        $userB = $this->createStaffUser($orgB);

        $this->assertTrue($this->policy->viewAny($orgAdminA));
        $this->assertTrue($this->policy->create($orgAdminA));
        $this->assertTrue($this->policy->view($orgAdminA, $userA));
        $this->assertTrue($this->policy->update($orgAdminA, $userA));
        $this->assertTrue($this->policy->delete($orgAdminA, $userA));

        $this->assertFalse($this->policy->view($orgAdminA, $userB));
        $this->assertFalse($this->policy->update($orgAdminA, $userB));
        $this->assertFalse($this->policy->delete($orgAdminA, $userB));
    }

    public function test_cannot_update_or_delete_super_admin_user(): void
    {
        $org = Organization::factory()->create();
        $orgAdmin = $this->createOrgAdmin($org);
        $superUser = $this->createSuperAdmin(); // even if in org, but super

        $this->assertFalse($this->policy->update($orgAdmin, $superUser));
        $this->assertFalse($this->policy->delete($orgAdmin, $superUser));
    }

    public function test_staff_cannot_access_users(): void
    {
        $org = Organization::factory()->create();
        $staff = $this->createStaffUser($org);
        $otherUser = $this->createStaffUser($org);

        $this->assertFalse($this->policy->viewAny($staff));
        $this->assertFalse($this->policy->view($staff, $otherUser));
        $this->assertFalse($this->policy->create($staff));
        $this->assertFalse($this->policy->update($staff, $otherUser));
        $this->assertFalse($this->policy->delete($staff, $otherUser));
    }
}
