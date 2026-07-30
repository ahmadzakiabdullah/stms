<?php

namespace Tests\Feature\Policies;

use App\Models\Organization;
use App\Models\Participant;
use App\Policies\ParticipantPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class ParticipantPolicyTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    private ParticipantPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ParticipantPolicy;
    }

    public function test_super_admin_can_perform_all_actions(): void
    {
        $super = $this->createSuperAdmin();
        $org = Organization::factory()->create();
        $participant = Participant::factory()->create(['organization_id' => $org->id]);

        $this->assertTrue($this->policy->viewAny($super));
        $this->assertTrue($this->policy->view($super, $participant));
        $this->assertTrue($this->policy->create($super));
        $this->assertTrue($this->policy->update($super, $participant));
        $this->assertTrue($this->policy->delete($super, $participant));
    }

    public function test_org_admin_can_manage_own_org_participants(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        $participant = Participant::factory()->create(['organization_id' => $org->id]);

        $this->assertTrue($this->policy->viewAny($admin));
        $this->assertTrue($this->policy->create($admin));
        $this->assertTrue($this->policy->view($admin, $participant));
        $this->assertTrue($this->policy->update($admin, $participant));
        $this->assertTrue($this->policy->delete($admin, $participant));
    }

    public function test_org_admin_cannot_manage_other_org_participants(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $adminA = $this->createOrgAdmin($orgA);

        $participantB = Participant::factory()->create(['organization_id' => $orgB->id]);

        $this->assertFalse($this->policy->view($adminA, $participantB));
        $this->assertFalse($this->policy->update($adminA, $participantB));
        $this->assertFalse($this->policy->delete($adminA, $participantB));
    }

    public function test_staff_without_perms_has_limited_access(): void
    {
        $org = Organization::factory()->create();
        $staff = $this->createStaffUser($org);
        $participant = Participant::factory()->create(['organization_id' => $org->id]);

        $this->assertFalse($this->policy->viewAny($staff));
        $this->assertTrue($this->policy->view($staff, $participant));
        $this->assertFalse($this->policy->create($staff));
        $this->assertFalse($this->policy->update($staff, $participant));
        $this->assertFalse($this->policy->delete($staff, $participant));
    }
}
