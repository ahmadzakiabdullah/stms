<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Result;
use App\Models\Session;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class ReportingDashboardTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    public function test_reports_include_operations_and_clean_data_quality_summary(): void
    {
        $organization = Organization::factory()->create();
        Session::factory()->create(['organization_id' => $organization->id, 'is_active' => true]);
        $user = $this->createStaffUser($organization);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->component('Reports/Index')
            ->where('operations.status', 'ok')
            ->where('operations.queue.pending', 0)
            ->where('operations.queue.failed', 0)
            ->where('operations.active_sessions.domain', 1)
            ->where('dataQuality.status', 'ok')
            ->where('dataQuality.total_issues', 0)
            ->has('dataQuality.checks', 4));
    }

    public function test_reports_surface_data_quality_attention_for_current_tenant_only(): void
    {
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $session = Session::factory()->create([
            'organization_id' => $organization->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-10',
        ]);
        $tournament = Tournament::factory()->forSession($session)->create([
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-10',
        ]);
        $event = Event::factory()->forTournament($tournament)->create([
            'start_date' => '2026-10-03',
            'end_date' => '2026-10-05',
            'registration_deadline' => '2026-10-04 12:00:00',
        ]);

        Participant::factory()->create([
            'organization_id' => $organization->id,
            'session_id' => $session->id,
            'name' => 'Faculty Alpha',
            'slug' => 'faculty-alpha-a',
        ]);
        Participant::factory()->create([
            'organization_id' => $organization->id,
            'session_id' => $session->id,
            'name' => 'Faculty Alpha',
            'slug' => 'faculty-alpha-b',
        ]);
        Participant::factory()->create([
            'organization_id' => $otherOrganization->id,
            'name' => 'Faculty Alpha',
            'slug' => 'faculty-alpha-other',
        ]);

        $fixture = Fixture::factory()->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'scheduled_at' => '2026-10-09 09:00:00',
            'status' => 'completed',
        ]);
        Result::factory()->forOrganization($organization)->create([
            'match_id' => $fixture->id,
        ]);
        $fixture->delete();

        $user = $this->createStaffUser($organization);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();
        $checks = collect($response->viewData('page')['props']['dataQuality']['checks'] ?? [])->keyBy('key');

        $this->assertSame('attention', $response->viewData('page')['props']['dataQuality']['status'] ?? null);
        $this->assertSame(1, $checks['duplicate_participants']['count'] ?? null);
        $this->assertSame(1, $checks['orphan_results']['count'] ?? null);
        $this->assertSame(1, $checks['invalid_timeline']['count'] ?? null);
        $this->assertSame(0, $checks['missing_parent_relations']['count'] ?? null);
    }

    public function test_reports_include_queue_and_application_session_counts(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->createStaffUser($organization);

        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => '{}',
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);
        DB::table('failed_jobs')->insert([
            'uuid' => (string) str()->uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'Example failure',
            'failed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->where('operations.status', 'degraded')
            ->where('operations.queue.pending', 1)
            ->where('operations.queue.failed', 1));
    }
}
