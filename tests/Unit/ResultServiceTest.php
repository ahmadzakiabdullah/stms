<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Result;
use App\Services\ResultService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultServiceTest extends TestCase
{
    use RefreshDatabase;

    private ResultService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ResultService;
    }

    public function test_create_result(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $org->id]);
        $match = Fixture::factory()->create(['organization_id' => $org->id, 'event_id' => $event->id]);

        $result = $this->service->create($org, [
            'match_id' => $match->id,
            'score_home' => 3,
            'score_away' => 1,
            'organization_id' => $org->id,
        ]);

        $this->assertNotNull($result);
        $this->assertEquals(3, $result->score_home);
        $this->assertEquals(1, $result->score_away);
    }

    public function test_update_result(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $org->id]);
        $match = Fixture::factory()->create(['organization_id' => $org->id, 'event_id' => $event->id]);
        $result = Result::factory()->create([
            'organization_id' => $org->id,
            'match_id' => $match->id,
        ]);

        $updated = $this->service->update($org, $result->id, [
            'score_home' => 5,
            'score_away' => 2,
        ]);

        $this->assertEquals(5, $updated->score_home);
        $this->assertEquals(2, $updated->score_away);
    }

    public function test_delete_result(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $org->id]);
        $match = Fixture::factory()->create(['organization_id' => $org->id, 'event_id' => $event->id]);
        $result = Result::factory()->create([
            'organization_id' => $org->id,
            'match_id' => $match->id,
        ]);

        $this->service->delete($org, $result->id);

        $this->assertSoftDeleted('results', ['id' => $result->id]);
    }

    public function test_create_result_marks_match_as_completed(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $org->id]);
        $match = Fixture::factory()->create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'status' => 'scheduled',
        ]);

        $this->service->create($org, [
            'match_id' => $match->id,
            'score_home' => 2,
            'score_away' => 1,
            'organization_id' => $org->id,
        ]);

        $this->assertSame('completed', $match->fresh()->status);
    }

    public function test_update_result_marks_match_as_completed(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $org->id]);
        $match = Fixture::factory()->create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'status' => 'in_progress',
        ]);
        $result = Result::factory()->create([
            'organization_id' => $org->id,
            'match_id' => $match->id,
        ]);

        $this->service->update($org, $result->id, [
            'score_home' => 5,
            'score_away' => 2,
        ]);

        $this->assertSame('completed', $match->fresh()->status);
    }
}
