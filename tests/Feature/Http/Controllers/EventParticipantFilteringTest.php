<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Session;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EventParticipantFilteringTest extends TestCase
{
    use RefreshDatabase;

    private function seedSuperAdmin(): User
    {
        Role::firstOrCreate(['name' => 'super-admin']);
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }

    private function seedData(): array
    {
        $org = Organization::factory()->create();
        $session = Session::factory()->create(['organization_id' => $org->id]);
        $tournament = Tournament::factory()->create(['organization_id' => $org->id, 'session_id' => $session->id]);

        $badminton = Sport::factory()->create(['organization_id' => $org->id, 'name' => 'Badminton']);
        $football = Sport::factory()->create(['organization_id' => $org->id, 'name' => 'Football']);

        $catBadminton = SportCategory::factory()->create(['organization_id' => $org->id, 'sport_id' => $badminton->id, 'name' => 'Singles']);
        $catFootball = SportCategory::factory()->create(['organization_id' => $org->id, 'sport_id' => $football->id, 'name' => 'Team']);

        $eventBadminton = Event::factory()->create([
            'organization_id' => $org->id, 'tournament_id' => $tournament->id,
            'sport_id' => $badminton->id, 'sport_category_id' => $catBadminton->id,
            'name' => 'Badminton - Singles',
        ]);
        $eventFootball = Event::factory()->create([
            'organization_id' => $org->id, 'tournament_id' => $tournament->id,
            'sport_id' => $football->id, 'sport_category_id' => $catFootball->id,
            'name' => 'Football - Team',
        ]);

        $facA = Participant::factory()->create(['organization_id' => $org->id, 'session_id' => $session->id, 'name' => 'Fakulti Kejuruteraan']);
        $facB = Participant::factory()->create(['organization_id' => $org->id, 'session_id' => $session->id, 'name' => 'Fakulti Teknologi']);

        EventParticipant::create(['organization_id' => $org->id, 'event_id' => $eventBadminton->id, 'participant_id' => $facA->id, 'status' => 'confirmed']);
        EventParticipant::create(['organization_id' => $org->id, 'event_id' => $eventFootball->id, 'participant_id' => $facA->id, 'status' => 'pending']);
        EventParticipant::create(['organization_id' => $org->id, 'event_id' => $eventFootball->id, 'participant_id' => $facB->id, 'status' => 'confirmed']);

        return compact('eventBadminton', 'eventFootball', 'facA', 'facB', 'badminton', 'football', 'catBadminton', 'catFootball');
    }

    public function test_sport_filter_returns_only_matching_registrations(): void
    {
        $user = $this->seedSuperAdmin();
        $d = $this->seedData();

        $response = $this->actingAs($user)->get(route('event-participants.index', ['sport_id' => $d['badminton']->id]));

        $response->assertOk();
        $response->assertInertia(function ($page) use ($d) {
            $data = $page->toArray()['props']['participants']['data'];
            $this->assertCount(1, $data, 'only facA has badminton registration');
            $this->assertEquals($d['facA']->id, $data[0]['id']);
            $this->assertCount(1, $data[0]['event_participants'], 'facA must show only the badminton registration');
            $this->assertEquals($d['eventBadminton']->id, $data[0]['event_participants'][0]['event_id']);
        });
    }

    public function test_category_filter_returns_only_matching_registrations(): void
    {
        $user = $this->seedSuperAdmin();
        $d = $this->seedData();

        $response = $this->actingAs($user)->get(route('event-participants.index', ['category_id' => $d['catFootball']->id]));

        $response->assertOk();
        $response->assertInertia(function ($page) use ($d) {
            $data = $page->toArray()['props']['participants']['data'];
            $this->assertCount(2, $data, 'facA and facB have football registrations');
            foreach ($data as $p) {
                $this->assertCount(1, $p['event_participants']);
                $this->assertEquals($d['eventFootball']->id, $p['event_participants'][0]['event_id']);
            }
        });
    }

    public function test_events_tab_respects_sport_filter(): void
    {
        $user = $this->seedSuperAdmin();
        $d = $this->seedData();

        $response = $this->actingAs($user)->get(route('event-participants.index', ['sport_id' => $d['football']->id]));

        $response->assertOk();
        $response->assertInertia(function ($page) use ($d) {
            $events = $page->toArray()['props']['events'];
            $this->assertCount(1, $events);
            $this->assertEquals($d['eventFootball']->id, $events[0]['id']);
        });
    }

    public function test_search_matches_event_and_faculty_names(): void
    {
        $user = $this->seedSuperAdmin();
        $d = $this->seedData();

        $response = $this->actingAs($user)->get(route('event-participants.index', ['search' => 'Badminton']));

        $response->assertOk();
        $response->assertInertia(function ($page) use ($d) {
            $data = $page->toArray()['props']['participants']['data'];
            $this->assertCount(1, $data, 'badminton search');
            $this->assertEquals($d['facA']->id, $data[0]['id']);
            $this->assertCount(1, $data[0]['event_participants'], 'badminton search regs');
            $this->assertEquals($d['eventBadminton']->id, $data[0]['event_participants'][0]['event_id']);
        });

        $response2 = $this->actingAs($user)->get(route('event-participants.index', ['search' => 'Teknologi']));
        $response2->assertInertia(function ($page) {
            $data = $page->toArray()['props']['participants']['data'];
            $this->assertCount(1, $data);
        });
    }
}
