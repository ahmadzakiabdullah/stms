<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Session;
use App\Models\Sport;
use App\Models\Tournament;
use App\Services\TournamentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_tournament_with_sports(): void
    {
        $org = Organization::factory()->create();
        $session = Session::factory()->create(['organization_id' => $org->id]);
        $sport = Sport::factory()->create(['organization_id' => $org->id]);

        $service = new TournamentService();

        $tournament = $service->createWithSports([
            'organization_id' => $org->id,
            'session_id' => $session->id,
            'name' => 'Test Tournament Service',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addWeek()->toDateString(),
            'sports' => [$sport->id],
        ]);

        $this->assertDatabaseHas('tournaments', ['name' => 'Test Tournament Service']);
        $this->assertTrue($tournament->sports->contains($sport));
    }

    public function test_it_updates_tournament_with_sports(): void
    {
        $org = Organization::factory()->create();
        $session = Session::factory()->create(['organization_id' => $org->id]);
        $sportA = Sport::factory()->create(['organization_id' => $org->id]);
        $sportB = Sport::factory()->create(['organization_id' => $org->id]);
        $tournament = Tournament::factory()->create([
            'organization_id' => $org->id,
            'session_id' => $session->id,
        ]);

        $service = new TournamentService();
        $updated = $service->updateWithSports($tournament, [
            'organization_id' => $org->id,
            'session_id' => $session->id,
            'name' => 'Updated Tournament',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addWeek()->toDateString(),
            'sports' => [$sportA->id, $sportB->id],
        ]);

        $this->assertEquals('Updated Tournament', $updated->name);
        $this->assertCount(2, $updated->sports);
    }

    public function test_it_deletes_tournament(): void
    {
        $org = Organization::factory()->create();
        $session = Session::factory()->create(['organization_id' => $org->id]);
        $tournament = Tournament::factory()->create([
            'organization_id' => $org->id,
            'session_id' => $session->id,
        ]);

        $service = new TournamentService();
        $service->deleteWithSports($tournament);

        $this->assertSoftDeleted('tournaments', ['id' => $tournament->id]);
    }
}
