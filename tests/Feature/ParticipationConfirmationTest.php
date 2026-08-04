<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Session;
use App\Models\Setting;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class ParticipationConfirmationTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('participation-confirmations.index'))->assertRedirect(route('login'));
    }

    public function test_org_admin_can_view_two_phase_confirmation_data_and_dean_details(): void
    {
        $organization = Organization::factory()->create();
        $session = Session::factory()->create(['organization_id' => $organization->id]);
        $tournament = Tournament::factory()->forSession($session)->create();
        $event = Event::factory()->forTournament($tournament)->create();
        $unregisteredEvent = Event::factory()->forTournament($tournament)->create();
        $event->sport()->update(['name' => 'Alpha Sport']);
        $unregisteredEvent->sport()->update(['name' => 'Zulu Sport']);
        $participant = Participant::factory()->create([
            'organization_id' => $organization->id,
            'session_id' => $session->id,
            'name' => 'FAIX',
        ]);
        $registration = EventParticipant::factory()->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'status' => 'confirmed',
        ]);
        Setting::create(['organization_id' => $organization->id, 'key' => 'secretariat_address', 'value' => 'SAF Secretariat']);
        $dean = $this->createUserInOrganization($organization, ['participant_id' => $participant->id, 'name' => 'Professor Dean']);
        $dean->assignRole(Role::firstOrCreate(['name' => 'dean', 'guard_name' => 'web']));

        $this->actingAs($this->createOrgAdmin($organization))
            ->get(route('participation-confirmations.index', [
                'participant_id' => $participant->id,
                'session_id' => $session->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ParticipationConfirmations/Index')
                ->where('participant.name', 'FAIX')
                ->where('branding.secretariat_address', 'SAF Secretariat')
                ->where('dean.name', 'Professor Dean')
                ->has('phases', 1)
                ->has('phases.0.rows', 2)
                ->where('phases.0.rows.0.yes', true)
                ->where('phases.0.rows.0.no', false)
                ->where('phases.0.rows.1.status', 'not_participating')
                ->where('phases.0.rows.1.no', true));
    }

    public function test_faculty_user_is_locked_to_own_participant(): void
    {
        $organization = Organization::factory()->create();
        $session = Session::factory()->create(['organization_id' => $organization->id]);
        $ownFaculty = Participant::factory()->create(['organization_id' => $organization->id, 'session_id' => $session->id, 'name' => 'FAIX']);
        $otherFaculty = Participant::factory()->create(['organization_id' => $organization->id, 'session_id' => $session->id, 'name' => 'FTMK']);
        $user = $this->createUserInOrganization($organization, ['participant_id' => $ownFaculty->id]);
        $user->assignRole(Role::firstOrCreate(['name' => 'faculty-representative', 'guard_name' => 'web']));

        $this->actingAs($user)
            ->get(route('participation-confirmations.index', ['participant_id' => $otherFaculty->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('canSelectParticipant', false)
                ->where('participant.id', $ownFaculty->id));
    }

    public function test_unrelated_role_cannot_view_confirmation_page(): void
    {
        $organization = Organization::factory()->create();

        $this->actingAs($this->createStaffUser($organization))
            ->get(route('participation-confirmations.index'))
            ->assertForbidden();
    }
}
