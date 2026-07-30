<?php

namespace Tests\Feature\Requests;

use App\Models\Organization;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class StoreEventRequestTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    public function test_passes_with_valid_data(): void
    {
        $org = Organization::factory()->create();
        $tournament = Tournament::factory()->create(['organization_id' => $org->id]);
        $sport = Sport::factory()->create(['organization_id' => $org->id]);
        $category = SportCategory::factory()->create(['organization_id' => $org->id, 'sport_id' => $sport->id]);
        $user = $this->createOrgAdmin($org);
        $response = $this->actingAs($user)->post(route('events.store'), [
            'tournament_id' => $tournament->id,
            'sport_id' => $sport->id,
            'sport_category_id' => $category->id,
            'name' => 'Test Event',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-15',
        ]);
        $this->assertContains($response->status(), [302, 201]);
    }

    public function test_requires_name(): void
    {
        $org = Organization::factory()->create();
        $tournament = Tournament::factory()->create(['organization_id' => $org->id]);
        $sport = Sport::factory()->create(['organization_id' => $org->id]);
        $category = SportCategory::factory()->create(['organization_id' => $org->id, 'sport_id' => $sport->id]);
        $user = $this->createOrgAdmin($org);
        $response = $this->actingAs($user)->post(route('events.store'), [
            'tournament_id' => $tournament->id,
            'sport_id' => $sport->id,
            'sport_category_id' => $category->id,
            'start_date' => '2026-01-01',
        ]);
        $response->assertSessionHasErrors('name');
    }

    public function test_rejects_cross_org_tournament(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $tournamentB = Tournament::factory()->create(['organization_id' => $orgB->id]);
        $sport = Sport::factory()->create(['organization_id' => $orgA->id]);
        $category = SportCategory::factory()->create(['organization_id' => $orgA->id, 'sport_id' => $sport->id]);
        $user = $this->createOrgAdmin($orgA);
        $response = $this->actingAs($user)->post(route('events.store'), [
            'tournament_id' => $tournamentB->id,
            'sport_id' => $sport->id,
            'sport_category_id' => $category->id,
            'name' => 'Cross Org Event',
            'start_date' => '2026-01-01',
        ]);
        $response->assertSessionHasErrors('tournament_id');
    }
}
