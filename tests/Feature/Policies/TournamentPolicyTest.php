<?php

namespace Tests\Feature\Policies;

use App\Models\Organization;
use App\Models\Session;
use App\Models\Tournament;
use App\Policies\TournamentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class TournamentPolicyTest extends TestCase
{
    use RefreshDatabase, CreatesTenantUsers;

    private TournamentPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new TournamentPolicy();
    }

    public function test_super_admin_can_perform_all_actions(): void
    {
        $super = $this->createSuperAdmin();
        $org = Organization::factory()->create();
        $session = Session::factory()->create(['organization_id' => $org->id]);
        $tournament = Tournament::factory()->create([
            'organization_id' => $org->id,
            'session_id' => $session->id,
        ]);

        $this->assertTrue($this->policy->viewAny($super));
        $this->assertTrue($this->policy->view($super, $tournament));
        $this->assertTrue($this->policy->create($super));
        $this->assertTrue($this->policy->update($super, $tournament));
        $this->assertTrue($this->policy->delete($super, $tournament));
    }

    public function test_org_admin_can_do_everything_in_own_org_but_not_cross_org(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $orgAdminA = $this->createOrgAdmin($orgA);

        $sessionA = Session::factory()->create(['organization_id' => $orgA->id]);
        $tournamentA = Tournament::factory()->create([
            'organization_id' => $orgA->id,
            'session_id' => $sessionA->id,
        ]);

        $sessionB = Session::factory()->create(['organization_id' => $orgB->id]);
        $tournamentB = Tournament::factory()->create([
            'organization_id' => $orgB->id,
            'session_id' => $sessionB->id,
        ]);

        $this->assertTrue($this->policy->viewAny($orgAdminA));
        $this->assertTrue($this->policy->view($orgAdminA, $tournamentA));
        $this->assertTrue($this->policy->create($orgAdminA));
        $this->assertTrue($this->policy->update($orgAdminA, $tournamentA));
        $this->assertTrue($this->policy->delete($orgAdminA, $tournamentA));

        // Cross org
        $this->assertFalse($this->policy->view($orgAdminA, $tournamentB));
        $this->assertFalse($this->policy->update($orgAdminA, $tournamentB));
        $this->assertFalse($this->policy->delete($orgAdminA, $tournamentB));
    }

    public function test_staff_cannot_access_tournaments(): void
    {
        $org = Organization::factory()->create();
        $staff = $this->createStaffUser($org);

        $session = Session::factory()->create(['organization_id' => $org->id]);
        $tournament = Tournament::factory()->create([
            'organization_id' => $org->id,
            'session_id' => $session->id,
        ]);

        $this->assertFalse($this->policy->viewAny($staff));
        $this->assertTrue($this->policy->view($staff, $tournament));
        $this->assertFalse($this->policy->create($staff));
        $this->assertFalse($this->policy->update($staff, $tournament));
        $this->assertFalse($this->policy->delete($staff, $tournament));
    }

    public function test_cross_organization_access_is_denied_for_org_admin(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $orgAdminA = $this->createOrgAdmin($orgA);

        $sessionB = Session::factory()->create(['organization_id' => $orgB->id]);
        $tournamentB = Tournament::factory()->create([
            'organization_id' => $orgB->id,
            'session_id' => $sessionB->id,
        ]);

        $this->assertFalse($this->policy->view($orgAdminA, $tournamentB));
        $this->assertFalse($this->policy->update($orgAdminA, $tournamentB));
        $this->assertFalse($this->policy->delete($orgAdminA, $tournamentB));
    }
}
