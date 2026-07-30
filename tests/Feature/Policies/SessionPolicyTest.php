<?php

namespace Tests\Feature\Policies;

use App\Models\Organization;
use App\Models\Session;
use App\Policies\SessionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class SessionPolicyTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    private SessionPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new SessionPolicy;
    }

    public function test_super_admin_can_perform_all_actions(): void
    {
        $super = $this->createSuperAdmin();
        $org = Organization::factory()->create();
        $session = Session::factory()->create(['organization_id' => $org->id]);

        $this->assertTrue($this->policy->viewAny($super));
        $this->assertTrue($this->policy->view($super, $session));
        $this->assertTrue($this->policy->create($super));
        $this->assertTrue($this->policy->update($super, $session));
        $this->assertTrue($this->policy->delete($super, $session));
    }

    public function test_org_admin_can_do_everything_in_own_org_but_not_cross_org(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $orgAdminA = $this->createOrgAdmin($orgA);

        $sessionA = Session::factory()->create(['organization_id' => $orgA->id]);
        $sessionB = Session::factory()->create(['organization_id' => $orgB->id]);

        $this->assertTrue($this->policy->viewAny($orgAdminA));
        $this->assertTrue($this->policy->view($orgAdminA, $sessionA));
        $this->assertTrue($this->policy->create($orgAdminA));
        $this->assertTrue($this->policy->update($orgAdminA, $sessionA));
        $this->assertTrue($this->policy->delete($orgAdminA, $sessionA));

        $this->assertFalse($this->policy->view($orgAdminA, $sessionB));
        $this->assertFalse($this->policy->update($orgAdminA, $sessionB));
        $this->assertFalse($this->policy->delete($orgAdminA, $sessionB));
    }

    public function test_staff_cannot_access_sessions(): void
    {
        $org = Organization::factory()->create();
        $staff = $this->createStaffUser($org);
        $session = Session::factory()->create(['organization_id' => $org->id]);

        $this->assertFalse($this->policy->viewAny($staff));
        $this->assertTrue($this->policy->view($staff, $session));
        $this->assertFalse($this->policy->create($staff));
        $this->assertFalse($this->policy->update($staff, $session));
        $this->assertFalse($this->policy->delete($staff, $session));
    }
}
