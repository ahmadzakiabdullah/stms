<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Registration;
use App\Models\Result;
use App\Models\Session;
use App\Models\Sport;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class PerformanceBaselineTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    public function test_dashboard_stays_within_the_documented_query_budget(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->createSuperAdmin(['organization_id' => $organization->id]);
        $session = Session::factory()->create(['organization_id' => $organization->id]);
        $sport = Sport::factory()->create(['organization_id' => $organization->id]);
        $tournament = Tournament::factory()->create([
            'organization_id' => $organization->id,
            'session_id' => $session->id,
        ]);
        $event = Event::factory()->create([
            'organization_id' => $organization->id,
            'tournament_id' => $tournament->id,
            'sport_id' => $sport->id,
        ]);
        $participants = Participant::factory()->count(4)->create(['organization_id' => $organization->id]);

        foreach ($participants as $participant) {
            Registration::factory()->create([
                'organization_id' => $organization->id,
                'tournament_id' => $tournament->id,
                'participant_id' => $participant->id,
            ]);
        }

        $fixture = Fixture::factory()->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'home_participant_id' => $participants[0]->id,
            'away_participant_id' => $participants[1]->id,
        ]);
        Result::factory()->create([
            'organization_id' => $organization->id,
            'match_id' => $fixture->id,
        ]);

        Cache::flush();
        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $this->actingAs($admin)->get('/dashboard')->assertOk();

        // Budget: dashboard core + registration overview (faculty/event breakdowns, squad composition).
        $this->assertLessThanOrEqual(
            42,
            $queries,
            "Dashboard query budget exceeded: {$queries} queries (budget: 42)."
        );
    }
}
