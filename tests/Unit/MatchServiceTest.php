<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\Fixture;
use App\Models\Organization;
use App\Services\MatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchServiceTest extends TestCase
{
    use RefreshDatabase;

    private MatchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MatchService;
    }

    public function test_create_match(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $org->id]);

        $match = $this->service->create($org, [
            'event_id' => $event->id,
            'match_number' => 1,
            'status' => 'scheduled',
        ]);

        $this->assertNotNull($match);
        $this->assertEquals(1, $match->match_number);
        $this->assertEquals('scheduled', $match->status);
    }

    public function test_update_match(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $org->id]);
        $match = Fixture::factory()->create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
        ]);

        $updated = $this->service->update($org, $match->id, [
            'venue' => 'Stadium A',
            'status' => 'in_progress',
        ]);

        $this->assertEquals('Stadium A', $updated->venue);
        $this->assertEquals('in_progress', $updated->status);
    }

    public function test_delete_match(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $org->id]);
        $match = Fixture::factory()->create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
        ]);

        $this->service->delete($org, $match->id);

        $this->assertSoftDeleted('matches', ['id' => $match->id]);
    }
}
