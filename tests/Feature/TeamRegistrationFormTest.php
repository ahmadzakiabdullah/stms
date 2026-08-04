<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Session;
use App\Models\SquadMember;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class TeamRegistrationFormTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    public function test_faculty_representative_can_view_own_team_form_with_roster(): void
    {
        $organization = Organization::factory()->create();
        $session = Session::factory()->create(['organization_id' => $organization->id]);
        $tournament = Tournament::factory()->forSession($session)->create();
        $event = Event::factory()->forTournament($tournament)->create();
        $event->sportCategory()->update([
            'quota_mode' => 'gender_based',
            'max_male_athletes' => 1,
            'max_female_athletes' => 2,
            'max_officials' => 2,
        ]);
        $participant = Participant::factory()->create([
            'organization_id' => $organization->id,
            'session_id' => $session->id,
            'name' => 'FTMK',
        ]);
        $registration = EventParticipant::factory()->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'status' => 'confirmed',
        ]);
        SquadMember::factory()->create([
            'organization_id' => $organization->id,
            'event_participant_id' => $registration->id,
            'name' => 'Nur Aina',
            'role' => 'athlete_female',
            'matrix_no' => 'B0123456',
        ]);
        SquadMember::factory()->create([
            'organization_id' => $organization->id,
            'event_participant_id' => $registration->id,
            'name' => 'Pn. Sara',
            'role' => 'manager',
        ]);
        $user = $this->createUserInOrganization($organization, ['participant_id' => $participant->id]);
        $user->assignRole(Role::firstOrCreate(['name' => 'faculty-representative', 'guard_name' => 'web']));

        $this->actingAs($user)
            ->get(route('event-participants.team-form', $registration))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('TeamRegistrationForms/Show')
                ->where('participant.name', 'FTMK')
                ->where('quotaRows.officials', 2)
                ->where('quotaRows.athletes', 3)
                ->where('athletes.0.name', 'Nur Aina')
                ->where('officials.0.name', 'Pn. Sara'));
    }

    public function test_faculty_representative_cannot_view_another_facultys_team_form(): void
    {
        $organization = Organization::factory()->create();
        $session = Session::factory()->create(['organization_id' => $organization->id]);
        $tournament = Tournament::factory()->forSession($session)->create();
        $event = Event::factory()->forTournament($tournament)->create();
        $ownParticipant = Participant::factory()->create(['organization_id' => $organization->id, 'session_id' => $session->id]);
        $otherParticipant = Participant::factory()->create(['organization_id' => $organization->id, 'session_id' => $session->id]);
        $registration = EventParticipant::factory()->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'participant_id' => $otherParticipant->id,
        ]);
        $user = $this->createUserInOrganization($organization, ['participant_id' => $ownParticipant->id]);
        $user->assignRole(Role::firstOrCreate(['name' => 'faculty-representative', 'guard_name' => 'web']));

        $this->actingAs($user)
            ->get(route('event-participants.team-form', $registration))
            ->assertForbidden();
    }

    public function test_cross_organization_user_cannot_view_team_form(): void
    {
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $registration = EventParticipant::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($this->createOrgAdmin($otherOrganization))
            ->get('/event-participants/'.$registration->id.'/team-form')
            ->assertNotFound();
    }
}
