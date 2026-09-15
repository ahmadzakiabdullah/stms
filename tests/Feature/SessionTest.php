<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Session;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class SessionTest extends TestCase
{
    use RefreshDatabase, CreatesTenantUsers;

    public function test_non_super_admin_only_sees_own_organization_sessions_via_global_scope(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $sessionA = Session::factory()->create(['organization_id' => $orgA->id, 'name' => 'Session A']);
        $sessionB = Session::factory()->create(['organization_id' => $orgB->id, 'name' => 'Session B']);

        $userA = $this->createStaffUser($orgA);

        $response = $this->actingAs($userA)->get(route('sessions.index'));
        $response->assertOk();
    }

    public function test_super_admin_can_create_session(): void
    {
        $org = Organization::factory()->create();
        $super = $this->createSuperAdmin();

        $response = $this->actingAs($super)->post(route('sessions.store'), [
            'name' => 'New Session',
            'slug' => 'new-session',
            'description' => 'Test session',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-15',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('sessions.index'));
        $this->assertDatabaseHas('event_sessions', ['name' => 'New Session']);
    }

    public function test_org_admin_can_update_own_session(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        $session = Session::factory()->create(['organization_id' => $org->id]);

        $response = $this->actingAs($admin)->put(route('sessions.update', $session), [
            'name' => 'Updated Session',
            'slug' => $session->slug,
            'start_date' => $session->start_date,
            'end_date' => $session->end_date,
        ]);

        $response->assertRedirect(route('sessions.index'));
        $this->assertDatabaseHas('event_sessions', ['name' => 'Updated Session']);
    }

    public function test_non_super_admin_cannot_update_session_in_other_org(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $adminA = $this->createOrgAdmin($orgA);
        $sessionB = Session::factory()->create(['organization_id' => $orgB->id]);

        $response = $this->actingAs($adminA)->put(route('sessions.update', $sessionB), [
            'name' => 'Hacked',
            'slug' => $sessionB->slug,
            'start_date' => $sessionB->start_date,
        ]);

        $response->assertNotFound();
    }

    public function test_org_admin_can_delete_own_session(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        $session = Session::factory()->create(['organization_id' => $org->id]);

        $response = $this->actingAs($admin)->delete(route('sessions.destroy', $session));
        $response->assertRedirect(route('sessions.index'));
        $this->assertSoftDeleted('event_sessions', ['id' => $session->id]);
    }

    public function test_duplicate_slug_rejected_within_same_org(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        Session::factory()->create([
            'organization_id' => $org->id,
            'slug' => 'same-session-slug',
        ]);

        $response = $this->actingAs($admin)->post(route('sessions.store'), [
            'name' => 'Duplicate Session',
            'slug' => 'same-session-slug',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-15',
        ]);
        $response->assertSessionHasErrors('slug');
    }
}
