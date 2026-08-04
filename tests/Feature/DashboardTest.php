<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Session;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\SquadMember;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class DashboardTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    public function test_dashboard_shows_scoped_real_stats_and_recent_data(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        // Sessions for orgA and orgB
        $sessionA = Session::factory()->create(['organization_id' => $orgA->id, 'name' => 'SUKMA 2026', 'is_active' => true]);
        Session::factory()->create(['organization_id' => $orgB->id, 'name' => 'Other Session']);

        $staffA = $this->createStaffUser($orgA);

        $response = $this->actingAs($staffA)->get(route('dashboard'));

        $response->assertOk();
        $props = $response->viewData('page')['props'] ?? [];

        // Stats should be scoped (only orgA's sessions count as 1 active)
        $this->assertEquals(1, $props['stats']['activeSessions'] ?? 0);

        // Recent sessions should only include orgA's
        $recent = $props['recentSessions'] ?? [];
        $this->assertCount(1, $recent);
        $this->assertEquals('SUKMA 2026', $recent[0]['name'] ?? null);
    }

    public function test_super_admin_sees_all_in_dashboard(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        Session::factory()->create(['organization_id' => $orgA->id]);
        Session::factory()->create(['organization_id' => $orgB->id]);

        $super = $this->createSuperAdmin();

        $response = $this->actingAs($super)->get(route('dashboard'));

        $response->assertOk();
        $props = $response->viewData('page')['props'] ?? [];

        $this->assertGreaterThanOrEqual(2, $props['stats']['activeSessions'] ?? 0);
    }

    public function test_dashboard_includes_registration_overview_for_admins(): void
    {
        $orgA = Organization::factory()->create();
        $sessionA = Session::factory()->create(['organization_id' => $orgA->id]);
        $tournamentA = Tournament::factory()->create(['organization_id' => $orgA->id, 'session_id' => $sessionA->id]);
        $sportA = Sport::factory()->create(['organization_id' => $orgA->id, 'name' => 'Badminton']);
        $catA = SportCategory::factory()->create(['organization_id' => $orgA->id, 'sport_id' => $sportA->id, 'name' => 'Singles']);
        $eventA = Event::factory()->create([
            'organization_id' => $orgA->id, 'tournament_id' => $tournamentA->id,
            'sport_id' => $sportA->id, 'sport_category_id' => $catA->id, 'name' => 'Badminton - Singles',
        ]);
        $sportB = Sport::factory()->create(['organization_id' => $orgA->id, 'name' => 'Football']);
        $catB = SportCategory::factory()->create(['organization_id' => $orgA->id, 'sport_id' => $sportB->id, 'name' => 'Team']);
        $eventB = Event::factory()->create([
            'organization_id' => $orgA->id, 'tournament_id' => $tournamentA->id,
            'sport_id' => $sportB->id, 'sport_category_id' => $catB->id, 'name' => 'Football - Team',
        ]);
        $facA = Participant::factory()->create(['organization_id' => $orgA->id, 'session_id' => $sessionA->id, 'name' => 'Fakulti Kejuruteraan']);

        EventParticipant::create(['organization_id' => $orgA->id, 'event_id' => $eventA->id, 'participant_id' => $facA->id, 'status' => 'pending']);
        EventParticipant::create(['organization_id' => $orgA->id, 'event_id' => $eventB->id, 'participant_id' => $facA->id, 'status' => 'confirmed']);

        $staff = $this->createStaffUser($orgA);

        $response = $this->actingAs($staff)->get(route('dashboard'));

        $response->assertOk();
        $props = $response->viewData('page')['props'] ?? [];

        $this->assertEquals(2, $props['registrationStats']['totalRegistrations'] ?? 0);
        $this->assertEquals(1, $props['registrationStats']['pending'] ?? 0);
        $this->assertEquals(1, $props['registrationStats']['confirmed'] ?? 0);
        $this->assertCount(1, $props['facultyStats'] ?? []);
        $this->assertEquals(2, $props['facultyStats'][0]['total'] ?? 0);
        $this->assertEquals(1, $props['facultyStats'][0]['pending'] ?? 0);
        $this->assertEquals(1, $props['facultyStats'][0]['confirmed'] ?? 0);
        $this->assertEquals('Badminton - Singles', $props['eventStats']['data'][0]['name'] ?? null);
        $this->assertEquals(1, $props['eventStats']['data'][0]['total'] ?? 0);
    }

    public function test_dashboard_registration_overview_respects_filters(): void
    {
        $orgA = Organization::factory()->create();
        $sessionA = Session::factory()->create(['organization_id' => $orgA->id]);
        $tournamentA = Tournament::factory()->create(['organization_id' => $orgA->id, 'session_id' => $sessionA->id]);

        $sportA = Sport::factory()->create(['organization_id' => $orgA->id, 'name' => 'Badminton']);
        $catA = SportCategory::factory()->create(['organization_id' => $orgA->id, 'sport_id' => $sportA->id, 'name' => 'Singles']);
        $eventA = Event::factory()->create([
            'organization_id' => $orgA->id, 'tournament_id' => $tournamentA->id,
            'sport_id' => $sportA->id, 'sport_category_id' => $catA->id, 'name' => 'Badminton - Singles',
        ]);
        $sportB = Sport::factory()->create(['organization_id' => $orgA->id, 'name' => 'Football']);
        $catB = SportCategory::factory()->create(['organization_id' => $orgA->id, 'sport_id' => $sportB->id, 'name' => 'Team']);
        $eventB = Event::factory()->create([
            'organization_id' => $orgA->id, 'tournament_id' => $tournamentA->id,
            'sport_id' => $sportB->id, 'sport_category_id' => $catB->id, 'name' => 'Football - Team',
        ]);
        $facA = Participant::factory()->create(['organization_id' => $orgA->id, 'session_id' => $sessionA->id, 'name' => 'Fakulti Kejuruteraan']);

        EventParticipant::create(['organization_id' => $orgA->id, 'event_id' => $eventA->id, 'participant_id' => $facA->id, 'status' => 'pending']);
        EventParticipant::create(['organization_id' => $orgA->id, 'event_id' => $eventB->id, 'participant_id' => $facA->id, 'status' => 'confirmed']);

        $staff = $this->createStaffUser($orgA);

        $response = $this->actingAs($staff)->get(route('dashboard', ['status' => 'pending']));

        $response->assertOk();
        $props = $response->viewData('page')['props'] ?? [];

        $this->assertEquals(1, $props['facultyStats'][0]['total'] ?? 0);
        $this->assertEquals(1, $props['eventStats']['data'][0]['total'] ?? 0);
    }

    public function test_dashboard_loads_for_faculty_representative(): void
    {
        $orgA = Organization::factory()->create();
        $sessionA = Session::factory()->create(['organization_id' => $orgA->id]);
        $tournamentA = Tournament::factory()->create(['organization_id' => $orgA->id, 'session_id' => $sessionA->id]);
        $sportA = Sport::factory()->create(['organization_id' => $orgA->id, 'name' => 'Badminton']);
        $catA = SportCategory::factory()->create(['organization_id' => $orgA->id, 'sport_id' => $sportA->id, 'name' => 'Singles']);
        $eventA = Event::factory()->create([
            'organization_id' => $orgA->id,
            'tournament_id' => $tournamentA->id,
            'sport_id' => $sportA->id,
            'sport_category_id' => $catA->id,
            'name' => 'Badminton - Singles',
        ]);
        $facA = Participant::factory()->create(['organization_id' => $orgA->id, 'session_id' => $sessionA->id, 'name' => 'Fakulti Sains']);

        $user = $this->createUserInOrganization($orgA, ['participant_id' => $facA->id]);
        $user->assignRole(Role::firstOrCreate(['name' => 'faculty-representative', 'guard_name' => 'web']));

        $ep = EventParticipant::create(['organization_id' => $orgA->id, 'event_id' => $eventA->id, 'participant_id' => $facA->id, 'status' => 'confirmed']);
        SquadMember::factory()->create(['organization_id' => $orgA->id, 'event_participant_id' => $ep->id, 'role' => 'athlete_male']);
        SquadMember::factory()->create(['organization_id' => $orgA->id, 'event_participant_id' => $ep->id, 'role' => 'coach']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $props = $response->viewData('page')['props'] ?? [];

        $this->assertEquals('Faculty/Dashboard', $response->viewData('page')['component'] ?? null);
        $this->assertEquals($facA->id, $props['participant']['id'] ?? null);
        $this->assertCount(1, $props['registrations'] ?? []);
        $this->assertEquals('Badminton - Singles', $props['registrations'][0]['event']['name'] ?? null);
        $this->assertCount(2, $props['registrations'][0]['squad_members'] ?? []);
        $this->assertEquals(1, $props['totals']['male'] ?? 0);
        $this->assertEquals(0, $props['totals']['female'] ?? 0);
        $this->assertEquals(1, $props['totals']['officials'] ?? 0);
        $this->assertGreaterThanOrEqual(1, count($props['availableEvents'] ?? []));
        $this->assertCount(1, $props['sportCategories'] ?? []);
    }

    public function test_dashboard_includes_squad_composition_stats_and_respects_filters(): void
    {
        $orgA = Organization::factory()->create();
        $sessionA = Session::factory()->create(['organization_id' => $orgA->id]);
        $tournamentA = Tournament::factory()->create(['organization_id' => $orgA->id, 'session_id' => $sessionA->id]);
        $sportA = Sport::factory()->create(['organization_id' => $orgA->id, 'name' => 'Badminton']);
        $catA = SportCategory::factory()->create(['organization_id' => $orgA->id, 'sport_id' => $sportA->id, 'name' => 'Singles']);
        $eventA = Event::factory()->create([
            'organization_id' => $orgA->id, 'tournament_id' => $tournamentA->id,
            'sport_id' => $sportA->id, 'sport_category_id' => $catA->id, 'name' => 'Badminton - Singles',
        ]);
        $sportB = Sport::factory()->create(['organization_id' => $orgA->id, 'name' => 'Football']);
        $catB = SportCategory::factory()->create(['organization_id' => $orgA->id, 'sport_id' => $sportB->id, 'name' => 'Team']);
        $eventB = Event::factory()->create([
            'organization_id' => $orgA->id, 'tournament_id' => $tournamentA->id,
            'sport_id' => $sportB->id, 'sport_category_id' => $catB->id, 'name' => 'Football - Team',
        ]);
        $facA = Participant::factory()->create(['organization_id' => $orgA->id, 'session_id' => $sessionA->id, 'name' => 'Fakulti Kejuruteraan']);

        $epConfirmed = EventParticipant::create(['organization_id' => $orgA->id, 'event_id' => $eventA->id, 'participant_id' => $facA->id, 'status' => 'confirmed']);
        $epPending = EventParticipant::create(['organization_id' => $orgA->id, 'event_id' => $eventB->id, 'participant_id' => $facA->id, 'status' => 'pending']);

        SquadMember::factory()->create(['organization_id' => $orgA->id, 'event_participant_id' => $epConfirmed->id, 'role' => 'athlete_male']);
        SquadMember::factory()->create(['organization_id' => $orgA->id, 'event_participant_id' => $epConfirmed->id, 'role' => 'athlete_female']);
        SquadMember::factory()->create(['organization_id' => $orgA->id, 'event_participant_id' => $epConfirmed->id, 'role' => 'coach']);
        SquadMember::factory()->create(['organization_id' => $orgA->id, 'event_participant_id' => $epPending->id, 'role' => 'manager']);

        $staff = $this->createStaffUser($orgA);

        $props = $this->actingAs($staff)->get(route('dashboard'))->viewData('page')['props'] ?? [];
        $this->assertEquals(1, $props['squadStats']['athlete_male'] ?? 0);
        $this->assertEquals(1, $props['squadStats']['athlete_female'] ?? 0);
        $this->assertEquals(1, $props['squadStats']['coach'] ?? 0);
        $this->assertEquals(1, $props['squadStats']['manager'] ?? 0);

        $propsPending = $this->actingAs($staff)->get(route('dashboard', ['status' => 'pending']))->viewData('page')['props'] ?? [];
        $this->assertEquals(1, $propsPending['squadStats']['manager'] ?? 0);
        $this->assertArrayNotHasKey('athlete_male', $propsPending['squadStats'] ?? []);
    }

    public function test_dean_dashboard_route_redirects_to_verification_workspace(): void
    {
        $organization = Organization::factory()->create();
        $participant = Participant::factory()->create(['organization_id' => $organization->id]);
        $dean = $this->createUserInOrganization($organization, ['participant_id' => $participant->id]);
        $dean->assignRole(Role::firstOrCreate(['name' => 'dean', 'guard_name' => 'web']));

        $this->actingAs($dean)
            ->get(route('dashboard'))
            ->assertRedirect(route('dean.dashboard'));
    }
}
