<?php

namespace Tests\Feature\Requests;

use App\Models\Organization;
use App\Models\Session;
use App\Models\Sport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class StoreTournamentRequestTest extends TestCase
{
    use RefreshDatabase, CreatesTenantUsers;

    public function test_passes_with_valid_data(): void
    {
        $org = Organization::factory()->create();
        $session = Session::factory()->create(['organization_id' => $org->id]);
        $user = $this->createOrgAdmin($org);
        $response = $this->actingAs($user)->post(route('tournaments.store'), [
            'name' => 'Test Tournament',
            'session_id' => $session->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-15',
        ]);
        $this->assertContains($response->status(), [302, 201]);
    }

    public function test_requires_name(): void
    {
        $org = Organization::factory()->create();
        $session = Session::factory()->create(['organization_id' => $org->id]);
        $user = $this->createOrgAdmin($org);
        $response = $this->actingAs($user)->post(route('tournaments.store'), [
            'session_id' => $session->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-15',
        ]);
        $response->assertSessionHasErrors('name');
    }

    public function test_rejects_cross_org_session(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $sessionB = Session::factory()->create(['organization_id' => $orgB->id]);
        $user = $this->createOrgAdmin($orgA);
        $response = $this->actingAs($user)->post(route('tournaments.store'), [
            'name' => 'Cross Org Tournament',
            'session_id' => $sessionB->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-15',
        ]);
        $response->assertSessionHasErrors('session_id');
    }
}
