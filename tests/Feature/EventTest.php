<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class EventTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    public function test_staff_user_can_only_see_events_in_their_organization(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $tournamentA = Tournament::factory()->create(['organization_id' => $orgA->id]);
        $sportA = Sport::factory()->create(['organization_id' => $orgA->id]);
        $catA = SportCategory::factory()->forSport($sportA)->create();

        Event::factory()->create([
            'organization_id' => $orgA->id,
            'tournament_id' => $tournamentA->id,
            'sport_id' => $sportA->id,
            'sport_category_id' => $catA->id,
        ]);

        // Event in other org
        Event::factory()->create(['organization_id' => $orgB->id]);

        $staff = $this->createOrgAdmin($orgA);

        $response = $this->actingAs($staff)->get(route('events.index'));

        $response->assertOk();
        $events = $response->viewData('page')['props']['events']['data'] ?? [];
        $this->assertCount(1, $events); // only sees own org due to global scope
    }

    public function test_events_index_searches_event_tournament_and_sport_names(): void
    {
        $org = Organization::factory()->create();
        $tournament = Tournament::factory()->create(['organization_id' => $org->id, 'name' => 'Inter Faculty Cup']);
        $sport = Sport::factory()->create(['organization_id' => $org->id, 'name' => 'Badminton']);
        $category = SportCategory::factory()->forSport($sport)->create();
        Event::factory()->create([
            'organization_id' => $org->id,
            'tournament_id' => $tournament->id,
            'sport_id' => $sport->id,
            'sport_category_id' => $category->id,
            'name' => 'Men Singles',
        ]);

        $otherSport = Sport::factory()->create(['organization_id' => $org->id, 'name' => 'Tennis']);
        $otherCategory = SportCategory::factory()->forSport($otherSport)->create();
        Event::factory()->create([
            'organization_id' => $org->id,
            'tournament_id' => $tournament->id,
            'sport_id' => $otherSport->id,
            'sport_category_id' => $otherCategory->id,
            'name' => 'Women Singles',
        ]);
        $admin = $this->createOrgAdmin($org);

        $response = $this->actingAs($admin)->get(route('events.index', ['search' => 'Badminton']));

        $response->assertOk();
        $events = $response->viewData('page')['props']['events']['data'] ?? [];
        $this->assertCount(1, $events);
        $this->assertSame('Men Singles', $events[0]['name']);
    }

    public function test_non_authorized_user_cannot_create_event(): void
    {
        $org = Organization::factory()->create();
        $staff = $this->createStaffUser($org);

        $tournament = Tournament::factory()->create(['organization_id' => $org->id]);
        $sport = Sport::factory()->create(['organization_id' => $org->id]);
        $cat = SportCategory::factory()->forSport($sport)->create();

        $response = $this->actingAs($staff)->post(route('events.store'), [
            'tournament_id' => $tournament->id,
            'sport_id' => $sport->id,
            'sport_category_id' => $cat->id,
            'name' => 'Test Event',
            'start_date' => now()->toDateString(),
        ]);

        $response->assertForbidden();
    }

    public function test_authorized_user_can_create_event_in_own_org(): void
    {
        $org = Organization::factory()->create();
        $manager = $this->createStaffUser($org);
        $manager->assignRole('tournament-manager');

        $tournament = Tournament::factory()->create(['organization_id' => $org->id]);
        $sport = Sport::factory()->create(['organization_id' => $org->id]);
        $cat = SportCategory::factory()->forSport($sport)->create();

        $response = $this->actingAs($manager)->post(route('events.store'), [
            'tournament_id' => $tournament->id,
            'sport_id' => $sport->id,
            'sport_category_id' => $cat->id,
            'name' => 'Test Event',
            'start_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('events.index'));
        $this->assertDatabaseHas('events', ['name' => 'Test Event']);
    }

    public function test_authorized_user_can_set_venues_on_an_event(): void
    {
        $org = Organization::factory()->create();
        $manager = $this->createStaffUser($org);
        $manager->assignRole('tournament-manager');

        $tournament = Tournament::factory()->create(['organization_id' => $org->id]);
        $sport = Sport::factory()->create(['organization_id' => $org->id]);
        $cat = SportCategory::factory()->forSport($sport)->create();

        $response = $this->actingAs($manager)->post(route('events.store'), [
            'tournament_id' => $tournament->id,
            'sport_id' => $sport->id,
            'sport_category_id' => $cat->id,
            'name' => 'Test Event',
            'venues' => ['Stadium Mini UTeM', 'Padang B'],
            'start_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('events.index'));
        $event = Event::where('name', 'Test Event')->firstOrFail();
        $this->assertSame(['Stadium Mini UTeM', 'Padang B'], $event->venues);
    }

    public function test_blank_venue_entries_are_discarded_when_storing_an_event(): void
    {
        $org = Organization::factory()->create();
        $manager = $this->createStaffUser($org);
        $manager->assignRole('tournament-manager');

        $tournament = Tournament::factory()->create(['organization_id' => $org->id]);
        $sport = Sport::factory()->create(['organization_id' => $org->id]);
        $cat = SportCategory::factory()->forSport($sport)->create();

        $response = $this->actingAs($manager)->post(route('events.store'), [
            'tournament_id' => $tournament->id,
            'sport_id' => $sport->id,
            'sport_category_id' => $cat->id,
            'name' => 'Test Event',
            'venues' => ['Stadium Mini UTeM', '   ', ''],
            'start_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('events.index'));
        $event = Event::where('name', 'Test Event')->firstOrFail();
        $this->assertSame(['Stadium Mini UTeM'], $event->venues);
    }

    public function test_updating_event_venues_backfills_existing_matches_without_a_venue(): void
    {
        $org = Organization::factory()->create();
        $manager = $this->createOrgAdmin($org);

        $tournament = Tournament::factory()->create(['organization_id' => $org->id]);
        $sport = Sport::factory()->create(['organization_id' => $org->id]);
        $cat = SportCategory::factory()->forSport($sport)->create();
        $event = Event::factory()->forTournament($tournament)->create(['sport_id' => $sport->id, 'sport_category_id' => $cat->id, 'venues' => []]);
        $noVenue = Fixture::factory()->scheduled()->create(['organization_id' => $org->id, 'event_id' => $event->id, 'venue' => null]);
        $blankVenue = Fixture::factory()->scheduled()->create(['organization_id' => $org->id, 'event_id' => $event->id, 'venue' => '']);
        $alreadySet = Fixture::factory()->scheduled()->create(['organization_id' => $org->id, 'event_id' => $event->id, 'venue' => 'Existing Stadium']);

        $response = $this->actingAs($manager)->put(route('events.update', $event->slug), [
            'tournament_id' => $tournament->id,
            'sport_id' => $sport->id,
            'sport_category_id' => $cat->id,
            'name' => $event->name,
            'venues' => ['Stadium Mini UTeM', 'Padang B'],
            'start_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('events.index'));
        $this->assertDatabaseHas('matches', ['id' => $noVenue->id, 'venue' => 'Stadium Mini UTeM']);
        $this->assertDatabaseHas('matches', ['id' => $blankVenue->id, 'venue' => 'Stadium Mini UTeM']);
        $this->assertDatabaseHas('matches', ['id' => $alreadySet->id, 'venue' => 'Existing Stadium']);
    }
}
