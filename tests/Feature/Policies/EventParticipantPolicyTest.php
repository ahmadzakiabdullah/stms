<?php

namespace Tests\Feature\Policies;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Organization;
use App\Models\Participant;
use App\Policies\EventParticipantPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class EventParticipantPolicyTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    private EventParticipantPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new EventParticipantPolicy;
        foreach (['view event participants', 'create event participants', 'edit event participants', 'delete event participants'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
    }

    private function createEventParticipant(Organization $org): EventParticipant
    {
        $event = Event::factory()->create(['organization_id' => $org->id]);
        $participant = Participant::factory()->create(['organization_id' => $org->id]);

        return EventParticipant::create([
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'status' => 'pending',
        ]);
    }

    public function test_super_admin_can_perform_all_actions(): void
    {
        $super = $this->createSuperAdmin();
        $org = Organization::factory()->create();
        $ep = $this->createEventParticipant($org);

        $this->assertTrue($this->policy->viewAny($super));
        $this->assertTrue($this->policy->view($super, $ep));
        $this->assertTrue($this->policy->create($super));
        $this->assertTrue($this->policy->update($super, $ep));
        $this->assertTrue($this->policy->delete($super, $ep));
    }

    public function test_org_admin_can_manage_own_org_event_participants(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        $ep = $this->createEventParticipant($org);

        $this->assertTrue($this->policy->viewAny($admin));
        $this->assertTrue($this->policy->create($admin));
        $this->assertTrue($this->policy->view($admin, $ep));
        $this->assertTrue($this->policy->update($admin, $ep));
        $this->assertTrue($this->policy->delete($admin, $ep));
    }

    public function test_org_admin_cannot_manage_other_org_event_participants(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $adminA = $this->createOrgAdmin($orgA);

        $epB = $this->createEventParticipant($orgB);

        $this->assertFalse($this->policy->view($adminA, $epB));
        $this->assertFalse($this->policy->update($adminA, $epB));
        $this->assertFalse($this->policy->delete($adminA, $epB));
    }

    public function test_staff_without_perms_has_limited_access(): void
    {
        $org = Organization::factory()->create();
        $staff = $this->createStaffUser($org);
        $ep = $this->createEventParticipant($org);

        $this->assertFalse($this->policy->viewAny($staff));
        $this->assertTrue($this->policy->view($staff, $ep));
        $this->assertFalse($this->policy->create($staff));
        $this->assertFalse($this->policy->update($staff, $ep));
        $this->assertFalse($this->policy->delete($staff, $ep));
    }
}
