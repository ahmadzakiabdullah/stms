<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\Tournament;
use App\Services\EventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_event_with_auto_org_and_slug(): void
    {
        $org = Organization::factory()->create();
        $tournament = Tournament::factory()->create(['organization_id' => $org->id]);
        $sport = Sport::factory()->create(['organization_id' => $org->id]);
        $category = SportCategory::factory()->create([
            'organization_id' => $org->id,
            'sport_id' => $sport->id,
        ]);

        $service = new EventService;

        $event = $service->createEvent([
            'organization_id' => $org->id,
            'tournament_id' => $tournament->id,
            'sport_id' => $sport->id,
            'sport_category_id' => $category->id,
            'name' => 'Test Event via Service',
            'start_date' => now()->toDateString(),
        ]);

        $this->assertDatabaseHas('events', [
            'name' => 'Test Event via Service',
            'slug' => 'test-event-via-service',
            'organization_id' => $org->id,
        ]);
    }

    public function test_it_updates_event_and_regenerates_slug_if_needed(): void
    {
        $org = Organization::factory()->create();
        $tournament = Tournament::factory()->create(['organization_id' => $org->id]);
        $sport = Sport::factory()->create(['organization_id' => $org->id]);
        $category = SportCategory::factory()->create([
            'organization_id' => $org->id,
            'sport_id' => $sport->id,
        ]);

        $event = Event::factory()->create([
            'organization_id' => $org->id,
            'tournament_id' => $tournament->id,
            'sport_id' => $sport->id,
            'sport_category_id' => $category->id,
            'name' => 'Original Name',
            'slug' => 'original-name',
        ]);

        $service = new EventService;

        $updated = $service->updateEvent($event, [
            'name' => 'Updated Event Name',
            'tournament_id' => $tournament->id,
            'sport_id' => $sport->id,
            'sport_category_id' => $category->id,
            'start_date' => now()->toDateString(),
        ]);

        $this->assertEquals('updated-event-name', $updated->slug);
        $this->assertDatabaseHas('events', ['slug' => 'updated-event-name']);
    }

    public function test_it_deletes_event(): void
    {
        $event = Event::factory()->create();

        $service = new EventService;
        $service->deleteEvent($event);

        $this->assertSoftDeleted('events', ['id' => $event->id]);
    }
}
