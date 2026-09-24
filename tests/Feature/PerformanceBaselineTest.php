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

    public function test_public_home_and_schedule_stay_within_the_documented_query_budgets(): void
    {
        $organization = Organization::factory()->create(['is_active' => true]);
        $session = Session::factory()->create(['organization_id' => $organization->id, 'is_active' => true]);
        config(['app.public_org_slug' => $organization->slug, 'app.public_session_slug' => $session->slug]);
        $tournament = Tournament::factory()->forSession($session)->create();
        $sport = Sport::factory()->create(['organization_id' => $organization->id]);
        $event = Event::factory()->forTournament($tournament)->create(['sport_id' => $sport->id]);
        $home = Participant::factory()->create(['organization_id' => $organization->id, 'session_id' => $session->id, 'is_active' => true]);
        $away = Participant::factory()->create(['organization_id' => $organization->id, 'session_id' => $session->id, 'is_active' => true]);
        Fixture::factory()->scheduled()->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'home_participant_id' => $home->id,
            'away_participant_id' => $away->id,
        ]);

        Cache::flush();
        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $this->get(route('public.index'))->assertOk();
        $homeQueries = $queries;

        Cache::flush();
        $queries = 0;
        $this->get(route('public.schedule'))->assertOk();
        $scheduleQueries = $queries;

        $this->assertLessThanOrEqual(
            35,
            $homeQueries,
            "Public homepage query budget exceeded: {$homeQueries} queries (budget: 35)."
        );
        $this->assertLessThanOrEqual(
            35,
            $scheduleQueries,
            "Public schedule query budget exceeded: {$scheduleQueries} queries (budget: 35)."
        );
    }

    public function test_events_index_stays_within_the_documented_query_budget(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->createSuperAdmin(['organization_id' => $organization->id]);
        $session = Session::factory()->create(['organization_id' => $organization->id]);
        $sport = Sport::factory()->create(['organization_id' => $organization->id]);
        $tournament = Tournament::factory()->create([
            'organization_id' => $organization->id,
            'session_id' => $session->id,
        ]);
        Event::factory()->create([
            'organization_id' => $organization->id,
            'tournament_id' => $tournament->id,
            'sport_id' => $sport->id,
        ]);

        Cache::flush();
        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $this->actingAs($admin)->get(route('events.index'))->assertOk();

        $this->assertLessThanOrEqual(
            45,
            $queries,
            "Events index query budget exceeded: {$queries} queries (budget: 45)."
        );
    }

    public function test_results_registrations_and_reports_stay_within_the_documented_query_budgets(): void
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
        $home = Participant::factory()->create(['organization_id' => $organization->id, 'session_id' => $session->id]);
        $away = Participant::factory()->create(['organization_id' => $organization->id, 'session_id' => $session->id]);
        $fixture = Fixture::factory()->completed()->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'home_participant_id' => $home->id,
            'away_participant_id' => $away->id,
        ]);
        Result::factory()->forOrganization($organization)->create(['match_id' => $fixture->id]);
        Registration::factory()->create([
            'organization_id' => $organization->id,
            'tournament_id' => $tournament->id,
            'participant_id' => $home->id,
        ]);

        Cache::flush();
        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $this->actingAs($admin)->get(route('results.index'))->assertOk();
        $resultsQueries = $queries;

        Cache::flush();
        $queries = 0;
        $this->actingAs($admin)->get(route('registrations.index'))->assertOk();
        $registrationsQueries = $queries;

        Cache::flush();
        $queries = 0;
        $this->actingAs($admin)->get(route('reports.index'))->assertOk();
        $reportsQueries = $queries;

        $this->assertLessThanOrEqual(
            70,
            $resultsQueries,
            "Results index query budget exceeded: {$resultsQueries} queries (budget: 70)."
        );
        $this->assertLessThanOrEqual(
            35,
            $registrationsQueries,
            "Registrations index query budget exceeded: {$registrationsQueries} queries (budget: 35)."
        );
        $this->assertLessThanOrEqual(
            35,
            $reportsQueries,
            "Reports index query budget exceeded: {$reportsQueries} queries (budget: 35)."
        );
    }
}
