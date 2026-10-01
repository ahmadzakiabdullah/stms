<?php

namespace Tests\Feature;

use App\Models\Activity;
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
            'slug' => 'men-singles',
        ]);

        // A second event in the same organization whose tournament/sport/slug
        // must NOT match the search term. Values are set explicitly because the
        // factory derives name/slug from a random sport (occasionally "Badminton").
        $otherTournament = Tournament::factory()->create(['organization_id' => $org->id, 'name' => 'Friendly Series']);
        $otherSport = Sport::factory()->create(['organization_id' => $org->id, 'name' => 'Football']);
        $otherCategory = SportCategory::factory()->forSport($otherSport)->create();
        Event::factory()->create([
            'organization_id' => $org->id,
            'tournament_id' => $otherTournament->id,
            'sport_id' => $otherSport->id,
            'sport_category_id' => $otherCategory->id,
            'name' => 'Women Singles',
            'slug' => 'women-singles',
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

    public function test_authorized_user_can_store_event_draw_configuration(): void
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
            'name' => 'Configured Event',
            'start_date' => now()->toDateString(),
            'format' => 'group_knockout',
            'pool_size' => 4,
            'qualifiers_per_pool' => 2,
        ]);

        $response->assertRedirect(route('events.index'));
        $this->assertDatabaseHas('events', [
            'name' => 'Configured Event',
            'format' => 'group_knockout',
            'pool_size' => 4,
            'qualifiers_per_pool' => 2,
        ]);
    }

    public function test_event_draw_configuration_rejects_qualifiers_greater_than_pool_size(): void
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
            'name' => 'Invalid Configured Event',
            'start_date' => now()->toDateString(),
            'format' => 'group_knockout',
            'pool_size' => 3,
            'qualifiers_per_pool' => 4,
        ]);

        $response->assertSessionHasErrors('qualifiers_per_pool');
        $this->assertDatabaseMissing('events', ['name' => 'Invalid Configured Event']);
    }

    public function test_event_creation_rejects_parent_relations_from_another_organization(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $tournament = Tournament::factory()->create(['organization_id' => $orgA->id]);
        $sport = Sport::factory()->create(['organization_id' => $orgB->id]);
        $category = SportCategory::factory()->forSport($sport)->create();
        $super = $this->createSuperAdmin();

        $response = $this->actingAs($super)->post(route('events.store'), [
            'organization_id' => $orgA->id,
            'tournament_id' => $tournament->id,
            'sport_id' => $sport->id,
            'sport_category_id' => $category->id,
            'name' => 'Cross Tenant Event',
            'start_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('sport_id');
        $this->assertDatabaseMissing('events', ['name' => 'Cross Tenant Event']);
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

    public function test_batch_delete_requires_a_reason(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        $tournament = Tournament::factory()->create(['organization_id' => $org->id]);
        $sport = Sport::factory()->create(['organization_id' => $org->id]);
        $category = SportCategory::factory()->forSport($sport)->create();
        $event = Event::factory()->create([
            'organization_id' => $org->id,
            'tournament_id' => $tournament->id,
            'sport_id' => $sport->id,
            'sport_category_id' => $category->id,
        ]);

        $this->actingAs($admin)
            ->post(route('events.batch-destroy'), ['ids' => [$event->id]])
            ->assertSessionHasErrors('reason');

        $this->assertNotSoftDeleted('events', ['id' => $event->id]);
    }

    public function test_batch_delete_records_reason_and_summary_activity(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        $tournament = Tournament::factory()->create(['organization_id' => $org->id]);
        $sport = Sport::factory()->create(['organization_id' => $org->id]);
        $category = SportCategory::factory()->forSport($sport)->create();
        $eventA = Event::factory()->create([
            'organization_id' => $org->id,
            'tournament_id' => $tournament->id,
            'sport_id' => $sport->id,
            'sport_category_id' => $category->id,
        ]);
        $eventB = Event::factory()->create([
            'organization_id' => $org->id,
            'tournament_id' => $tournament->id,
            'sport_id' => $sport->id,
            'sport_category_id' => $category->id,
        ]);

        $this->actingAs($admin)
            ->post(route('events.batch-destroy'), [
                'ids' => [$eventA->id, $eventB->id],
                'reason' => 'Duplicate events created during setup review.',
            ])
            ->assertRedirect(route('events.index'))
            ->assertSessionHas('success', '2 events deleted successfully.');

        $this->assertSoftDeleted('events', ['id' => $eventA->id]);
        $this->assertSoftDeleted('events', ['id' => $eventB->id]);

        $summary = Activity::query()
            ->where('event', 'bulk_deleted')
            ->where('subject_type', Organization::class)
            ->where('subject_id', $org->id)
            ->firstOrFail();

        $this->assertSame('events.batch_destroy', $summary->properties['bulk_action']);
        $this->assertSame('Duplicate events created during setup review.', $summary->properties['reason']);
        $this->assertSame(2, $summary->properties['deleted_count']);
        $this->assertNotEmpty($summary->properties['bulk_action_id']);
    }

    public function test_batch_delete_rejects_events_from_multiple_organizations(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $superAdmin = $this->createSuperAdmin();
        $eventA = Event::factory()->create(['organization_id' => $orgA->id]);
        $eventB = Event::factory()->create(['organization_id' => $orgB->id]);

        $this->actingAs($superAdmin)
            ->post(route('events.batch-destroy'), [
                'ids' => [$eventA->id, $eventB->id],
                'reason' => 'Cross-organization cleanup attempt.',
            ])
            ->assertRedirect(route('events.index'))
            ->assertSessionHas('error', 'Bulk delete can only be applied to events from one organization at a time.');

        $this->assertNotSoftDeleted('events', ['id' => $eventA->id]);
        $this->assertNotSoftDeleted('events', ['id' => $eventB->id]);
    }
}
