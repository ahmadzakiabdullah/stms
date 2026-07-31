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

    public function test_recording_last_league_result_auto_generates_knockout_stage(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $org->id, 'pool_size' => 4, 'qualifiers_per_pool' => 2]);

        $poolA = \App\Models\Pool::query()->create(['organization_id' => $org->id, 'event_id' => $event->id, 'name' => 'Group A', 'sort_order' => 0]);
        $poolB = \App\Models\Pool::query()->create(['organization_id' => $org->id, 'event_id' => $event->id, 'name' => 'Group B', 'sort_order' => 1]);

        $teams = [];
        foreach ([['a1', $poolA], ['a2', $poolA], ['b1', $poolB], ['b2', $poolB]] as [$slug, $pool]) {
            $team = \App\Models\Participant::factory()->create(['organization_id' => $org->id, 'slug' => $slug]);
            \App\Models\EventParticipant::query()->create([
                'organization_id' => $org->id,
                'event_id' => $event->id,
                'participant_id' => $team->id,
                'pool_id' => $pool->id,
                'registration_date' => now(),
                'status' => 'confirmed',
            ]);
            $teams[$slug] = $team;
        }

        $fixtures = [];
        $matchNumber = 1;
        foreach ([[$poolA, 'a1', 'a2'], [$poolB, 'b1', 'b2']] as [$pool, $home, $away]) {
            $fixtures[] = Fixture::query()->create([
                'organization_id' => $org->id,
                'event_id' => $event->id,
                'pool_id' => $pool->id,
                'stage' => 'group',
                'round' => 1,
                'match_number' => $matchNumber++,
                'home_participant_id' => $teams[$home]->id,
                'away_participant_id' => $teams[$away]->id,
                'status' => 'scheduled',
            ]);
        }

        // Record the first result — league not complete, no knockout yet.
        $this->service->create($org, [
            'match_id' => $fixtures[0]->id,
            'score_home' => 2,
            'score_away' => 0,
            'organization_id' => $org->id,
        ]);

        $knockoutCount = Fixture::query()->where('event_id', $event->id)->where('stage', '!=', 'group')->count();
        $this->assertSame(0, $knockoutCount);

        // Last league result triggers knockout generation.
        $this->service->create($org, [
            'match_id' => $fixtures[1]->id,
            'score_home' => 1,
            'score_away' => 0,
            'organization_id' => $org->id,
        ]);

        $knockout = Fixture::query()->where('event_id', $event->id)->where('stage', '!=', 'group')->orderBy('round')->get();
        $this->assertCount(4, $knockout);

        $this->assertSame('semi_final', $knockout[0]->stage);
        $this->assertSame('semi_final', $knockout[1]->stage);
        $this->assertSame('bronze', $knockout[2]->stage);
        $this->assertSame('final', $knockout[3]->stage);

        // Cross-pool pairing: A1 vs B2, B1 vs A2.
        $this->assertSame($teams['a1']->id, $knockout[0]->home_participant_id);
        $this->assertSame($teams['b2']->id, $knockout[0]->away_participant_id);
        $this->assertSame($teams['b1']->id, $knockout[1]->home_participant_id);
        $this->assertSame($teams['a2']->id, $knockout[1]->away_participant_id);
    }
}
