<?php

namespace Tests\Feature\Policies;

use App\Models\Organization;
use App\Models\Participant;
use App\Models\Registration;
use App\Models\Tournament;
use App\Policies\RegistrationPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class RegistrationPolicyTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    private RegistrationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new RegistrationPolicy;
    }

    public function test_super_admin_can_perform_all_actions(): void
    {
        $super = $this->createSuperAdmin();
        $org = Organization::factory()->create();
        $tournament = Tournament::factory()->create(['organization_id' => $org->id]);
        $participant = Participant::factory()->create(['organization_id' => $org->id]);
        $registration = Registration::factory()->create([
            'organization_id' => $org->id,
            'tournament_id' => $tournament->id,
            'participant_id' => $participant->id,
        ]);

        $this->assertTrue($this->policy->viewAny($super));
        $this->assertTrue($this->policy->view($super, $registration));
        $this->assertTrue($this->policy->create($super));
        $this->assertTrue($this->policy->update($super, $registration));
        $this->assertTrue($this->policy->delete($super, $registration));
    }

    public function test_org_admin_can_manage_own_org_registrations(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        $tournament = Tournament::factory()->create(['organization_id' => $org->id]);
        $participant = Participant::factory()->create(['organization_id' => $org->id]);
        $registration = Registration::factory()->create([
            'organization_id' => $org->id,
            'tournament_id' => $tournament->id,
            'participant_id' => $participant->id,
        ]);

        $this->assertTrue($this->policy->viewAny($admin));
        $this->assertTrue($this->policy->create($admin));
        $this->assertTrue($this->policy->view($admin, $registration));
        $this->assertTrue($this->policy->update($admin, $registration));
        $this->assertTrue($this->policy->delete($admin, $registration));
    }

    public function test_org_admin_cannot_manage_other_org_registrations(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $adminA = $this->createOrgAdmin($orgA);

        $tournamentB = Tournament::factory()->create(['organization_id' => $orgB->id]);
        $participantB = Participant::factory()->create(['organization_id' => $orgB->id]);
        $registrationB = Registration::factory()->create([
            'organization_id' => $orgB->id,
            'tournament_id' => $tournamentB->id,
            'participant_id' => $participantB->id,
        ]);

        $this->assertFalse($this->policy->view($adminA, $registrationB));
        $this->assertFalse($this->policy->update($adminA, $registrationB));
        $this->assertFalse($this->policy->delete($adminA, $registrationB));
    }

    public function test_staff_without_perms_has_limited_access(): void
    {
        $org = Organization::factory()->create();
        $staff = $this->createStaffUser($org);
        $tournament = Tournament::factory()->create(['organization_id' => $org->id]);
        $participant = Participant::factory()->create(['organization_id' => $org->id]);
        $registration = Registration::factory()->create([
            'organization_id' => $org->id,
            'tournament_id' => $tournament->id,
            'participant_id' => $participant->id,
        ]);

        $this->assertFalse($this->policy->viewAny($staff));
        $this->assertTrue($this->policy->view($staff, $registration));
        $this->assertFalse($this->policy->create($staff));
        $this->assertFalse($this->policy->update($staff, $registration));
        $this->assertFalse($this->policy->delete($staff, $registration));
    }
}
