<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Sport;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class TenantIsolationTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    public function test_tenant_context_returns_organization_for_authenticated_user(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createStaffUser($org);

        $this->actingAs($user)->get(route('dashboard'));

        $this->assertEquals($org->id, TenantContext::getOrganizationId());
    }

    public function test_tenant_context_returns_null_for_super_admin(): void
    {
        $user = $this->createSuperAdmin();

        $this->actingAs($user)->get(route('dashboard'));

        $this->assertNull(TenantContext::getOrganizationId());
    }

    public function test_tenant_context_can_be_set_manually(): void
    {
        $org = Organization::factory()->create();

        TenantContext::setOrganizationId($org->id);

        $this->assertEquals($org->id, TenantContext::getOrganizationId());
    }

    public function test_tenant_context_is_super_admin_works(): void
    {
        $user = $this->createSuperAdmin();

        $this->actingAs($user)->get(route('dashboard'));

        $this->assertTrue(TenantContext::isSuperAdmin());
    }

    public function test_tenant_context_is_not_super_admin_for_regular_user(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createStaffUser($org);

        $this->actingAs($user)->get(route('dashboard'));

        $this->assertFalse(TenantContext::isSuperAdmin());
    }

    public function test_tenant_context_has_organization_works(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createStaffUser($org);

        $this->actingAs($user)->get(route('dashboard'));

        $this->assertTrue(TenantContext::hasOrganization());
    }

    public function test_tenant_context_reset_clears_state(): void
    {
        $org = Organization::factory()->create();
        TenantContext::setOrganizationId($org->id);
        TenantContext::setContext('http');

        TenantContext::reset();

        $this->assertNull(TenantContext::getOrganizationId());
        $this->assertNull(TenantContext::getContext());
    }

    public function test_tenant_context_get_organization_returns_model(): void
    {
        $org = Organization::factory()->create();
        TenantContext::setOrganizationId($org->id);

        $result = TenantContext::getOrganization();

        $this->assertInstanceOf(Organization::class, $result);
        $this->assertEquals($org->id, $result->id);
    }

    public function test_sport_model_scoped_by_tenant_context(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        Sport::factory()->create(['organization_id' => $orgA->id, 'name' => 'Sport A']);
        Sport::factory()->create(['organization_id' => $orgB->id, 'name' => 'Sport B']);

        $userA = $this->createStaffUser($orgA);

        $this->actingAs($userA)->get(route('dashboard'));

        $sports = Sport::all();

        $this->assertCount(1, $sports);
        $this->assertEquals('Sport A', $sports->first()->name);
    }

    public function test_super_admin_sees_all_organizations_data(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        Sport::factory()->create(['organization_id' => $orgA->id, 'name' => 'Sport A']);
        Sport::factory()->create(['organization_id' => $orgB->id, 'name' => 'Sport B']);

        $superAdmin = $this->createSuperAdmin();

        $this->actingAs($superAdmin)->get(route('dashboard'));

        $sports = Sport::all();

        $this->assertCount(2, $sports);
    }

    public function test_tenant_context_set_context_works(): void
    {
        TenantContext::setContext('console');

        $this->assertEquals('console', TenantContext::getContext());
        $this->assertTrue(TenantContext::isConsole());
        $this->assertFalse(TenantContext::isQueue());

        TenantContext::setContext('queue');

        $this->assertEquals('queue', TenantContext::getContext());
        $this->assertFalse(TenantContext::isConsole());
        $this->assertTrue(TenantContext::isQueue());
    }
}
