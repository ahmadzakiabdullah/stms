<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Session;
use App\Models\Sport;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class TournamentTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    public function test_non_super_admin_cannot_see_tournaments_from_other_organizations(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $sessionA = Session::factory()->create(['organization_id' => $orgA->id]);
        $sessionB = Session::factory()->create(['organization_id' => $orgB->id]);

        Tournament::factory()->create(['organization_id' => $orgA->id, 'session_id' => $sessionA->id]);
        Tournament::factory()->create(['organization_id' => $orgB->id, 'session_id' => $sessionB->id]);

        $staffA = $this->createOrgAdmin($orgA);

        $response = $this->actingAs($staffA)->get(route('tournaments.index'));

        $response->assertOk();
        $tournaments = $response->viewData('page')['props']['tournaments']['data'] ?? [];

        $this->assertCount(1, $tournaments);
    }

    public function test_super_admin_can_see_all_tournaments(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $sessionA = Session::factory()->create(['organization_id' => $orgA->id]);
        $sessionB = Session::factory()->create(['organization_id' => $orgB->id]);

        Tournament::factory()->create(['organization_id' => $orgA->id, 'session_id' => $sessionA->id]);
        Tournament::factory()->create(['organization_id' => $orgB->id, 'session_id' => $sessionB->id]);

        $super = $this->createSuperAdmin();

        $response = $this->actingAs($super)->get(route('tournaments.index'));

        $response->assertOk();
        $tournaments = $response->viewData('page')['props']['tournaments'] ?? [];
        $this->assertGreaterThanOrEqual(2, count($tournaments));
    }

    public function test_non_super_admin_cannot_create_tournament(): void
    {
        $org = Organization::factory()->create();
        $staff = $this->createStaffUser($org);
        $session = Session::factory()->create(['organization_id' => $org->id]);

        $response = $this->actingAs($staff)->post(route('tournaments.store'), [
            'session_id' => $session->id,
            'name' => 'Test Tournament',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
        ]);

        $response->assertForbidden();
    }

    public function test_org_admin_can_create_tournament_in_own_org(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        $session = Session::factory()->create(['organization_id' => $org->id]);

        $response = $this->actingAs($admin)->post(route('tournaments.store'), [
            'session_id' => $session->id,
            'name' => 'Test Tournament',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
        ]);

        $response->assertRedirect(route('tournaments.index'));
        $this->assertDatabaseHas('tournaments', ['name' => 'Test Tournament']);
    }

    public function test_duplicate_slug_rejected_within_same_org(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        $session = Session::factory()->create(['organization_id' => $org->id]);
        Tournament::factory()->create([
            'organization_id' => $org->id,
            'session_id' => $session->id,
            'slug' => 'same-tournament-slug',
        ]);

        $response = $this->actingAs($admin)->post(route('tournaments.store'), [
            'session_id' => $session->id,
            'name' => 'Duplicate Tournament',
            'slug' => 'same-tournament-slug',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
        ]);
        $response->assertSessionHasErrors('slug');
    }

    public function test_org_admin_can_update_tournament_with_sports(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        $session = Session::factory()->create(['organization_id' => $org->id]);
        $sportA = Sport::factory()->create(['organization_id' => $org->id]);
        $sportB = Sport::factory()->create(['organization_id' => $org->id]);
        $tournament = Tournament::factory()->create([
            'organization_id' => $org->id,
            'session_id' => $session->id,
        ]);

        $response = $this->actingAs($admin)->put(route('tournaments.update', $tournament), [
            'name' => 'Updated Tournament',
            'session_id' => $session->id,
            'slug' => $tournament->slug,
            'start_date' => $tournament->start_date->toDateString(),
            'end_date' => $tournament->end_date->toDateString(),
            'sports' => [$sportA->id, $sportB->id],
        ]);

        $response->assertRedirect(route('tournaments.index'));
        $tournament->refresh();
        $this->assertCount(2, $tournament->sports);
        $this->assertTrue($tournament->sports->contains($sportA));
        $this->assertTrue($tournament->sports->contains($sportB));
    }
}
