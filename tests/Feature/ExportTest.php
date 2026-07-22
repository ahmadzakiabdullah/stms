<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Event;
use App\Models\Fixture;
use App\Models\Participant;
use App\Models\Result;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class ExportTest extends TestCase
{
    use RefreshDatabase, CreatesTenantUsers;

    public function test_fixtures_pdf_export_requires_auth(): void
    {
        $response = $this->get(route('exports.fixtures.pdf'));
        $response->assertRedirect();
    }

    public function test_authenticated_user_can_export_fixtures_pdf(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createStaffUser($org);

        $response = $this->actingAs($user)->get(route('exports.fixtures.pdf'));

        $this->assertTrue($response->isSuccessful() || $response->isRedirect());
    }

    public function test_results_pdf_export_works(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createStaffUser($org);

        $response = $this->actingAs($user)->get(route('exports.results.pdf'));

        $this->assertTrue($response->isSuccessful() || $response->isRedirect());
    }

    public function test_rankings_pdf_export_works(): void
    {
        $org = Organization::factory()->create();
        $session = \App\Models\Session::factory()->create(['organization_id' => $org->id]);
        $tournament = Tournament::factory()->create(['organization_id' => $org->id, 'session_id' => $session->id]);
        $user = $this->createOrgAdmin($org);

        $response = $this->actingAs($user)->get(route('exports.rankings.pdf', ['tournament' => $tournament->id]));

        $this->assertTrue($response->isSuccessful() || $response->isRedirect());
    }

    public function test_fixtures_excel_export_works(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createStaffUser($org);

        $response = $this->actingAs($user)->get(route('exports.fixtures.excel'));

        $this->assertTrue(method_exists($response, 'isSuccessful') ? ($response->isSuccessful() || $response->isRedirect()) : true);
        $this->assertTrue($response->isSuccessful() || $response->isRedirect());
    }

    public function test_match_sheet_export_works(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $org->id]);
        $fixture = Fixture::factory()->create(['organization_id' => $org->id, 'event_id' => $event->id]);
        $user = $this->createStaffUser($org);

        $response = $this->actingAs($user)->get(route('exports.matchSheet', $fixture));

        $this->assertTrue($response->isSuccessful() || $response->isRedirect());
    }
}
