<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Session;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class DashboardTest extends TestCase
{
    use RefreshDatabase, CreatesTenantUsers;

    public function test_dashboard_shows_scoped_real_stats_and_recent_data(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        // Sessions for orgA and orgB
        $sessionA = Session::factory()->create(['organization_id' => $orgA->id, 'name' => 'SUKMA 2026', 'is_active' => true]);
        Session::factory()->create(['organization_id' => $orgB->id, 'name' => 'Other Session']);

        $staffA = $this->createStaffUser($orgA);

        $response = $this->actingAs($staffA)->get(route('dashboard'));

        $response->assertOk();
        $props = $response->viewData('page')['props'] ?? [];

        // Stats should be scoped (only orgA's sessions count as 1 active)
        $this->assertEquals(1, $props['stats']['activeSessions'] ?? 0);

        // Recent sessions should only include orgA's
        $recent = $props['recentSessions'] ?? [];
        $this->assertCount(1, $recent);
        $this->assertEquals('SUKMA 2026', $recent[0]['name'] ?? null);
    }

    public function test_super_admin_sees_all_in_dashboard(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        Session::factory()->create(['organization_id' => $orgA->id]);
        Session::factory()->create(['organization_id' => $orgB->id]);

        $super = $this->createSuperAdmin();

        $response = $this->actingAs($super)->get(route('dashboard'));

        $response->assertOk();
        $props = $response->viewData('page')['props'] ?? [];

        $this->assertGreaterThanOrEqual(2, $props['stats']['activeSessions'] ?? 0);
    }
}