<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class EventTest extends TestCase
{
    use RefreshDatabase, CreatesTenantUsers;

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

        $staff = $this->createStaffUser($orgA);

        $response = $this->actingAs($staff)->get(route('events.index'));

        $response->assertOk();
        $events = $response->viewData('page')['props']['events']['data'] ?? [];
        $this->assertCount(1, $events); // only sees own org due to global scope
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
}
