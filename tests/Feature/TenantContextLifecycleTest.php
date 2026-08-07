<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Sport;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use LogicException;
use RuntimeException;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

/**
 * P0 hardening: TenantContext lifecycle.
 *
 * Proves the context is reset before each request, never inherits a previous
 * request's tenant, records super-admin/guest bypass explicitly, cleans up in a
 * finally block (including on exceptions), and fails closed when a
 * tenant-required operation has no organization.
 */
class TenantContextLifecycleTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->get('/_test/ctx', fn () => response()->json([
            'org' => TenantContext::getOrganizationId(),
            'initialized' => TenantContext::isInitialized(),
            'bypass' => TenantContext::isBypassing(),
            'reason' => TenantContext::bypassReason(),
            'context' => TenantContext::getContext(),
        ]));

        Route::middleware('web')->get('/_test/boom', function (): never {
            throw new RuntimeException('boom');
        });
    }

    public function test_request_pipeline_resets_and_binds_context(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $userA = $this->createStaffUser($orgA);
        $userB = $this->createStaffUser($orgB);

        $a = $this->actingAs($userA)->get('/_test/ctx')->json();
        $this->assertSame($orgA->id, $a['org']);
        $this->assertTrue($a['initialized']);
        $this->assertFalse($a['bypass']);

        // Sequential request as a different organization must not inherit org A.
        $b = $this->actingAs($userB)->get('/_test/ctx')->json();
        $this->assertSame($orgB->id, $b['org']);
        $this->assertTrue($b['initialized']);
        $this->assertFalse($b['bypass']);

        // Context is cleaned up after the request has finished.
        $this->assertFalse(TenantContext::isInitialized());
        $this->assertFalse(TenantContext::isBypassing());
    }

    public function test_super_admin_request_does_not_inherit_previous_tenant(): void
    {
        $orgA = Organization::factory()->create();
        $userA = $this->createStaffUser($orgA);
        $superAdmin = $this->createSuperAdmin();

        $this->actingAs($userA)->get('/_test/ctx');

        $super = $this->actingAs($superAdmin)->get('/_test/ctx')->json();

        $this->assertNull($super['org']);
        $this->assertTrue($super['initialized']);
        $this->assertTrue($super['bypass']);
        $this->assertSame('super-admin', $super['reason']);
    }

    public function test_guest_request_does_not_inherit_previous_tenant(): void
    {
        $orgA = Organization::factory()->create();
        $userA = $this->createStaffUser($orgA);

        $this->actingAs($userA)->get('/_test/ctx');

        $this->app->get('auth')->forgetGuards();

        $guest = $this->get('/_test/ctx')->json();

        $this->assertNull($guest['org']);
        $this->assertTrue($guest['initialized']);
        $this->assertTrue($guest['bypass']);
        $this->assertSame('guest', $guest['reason']);
    }

    public function test_exception_mid_request_cleans_up_context(): void
    {
        $orgA = Organization::factory()->create();
        $userA = $this->createStaffUser($orgA);

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($userA)->get('/_test/boom');
            $this->fail('Expected RuntimeException to be thrown.');
        } catch (RuntimeException $e) {
            // The finally block must still have reset the context.
            $this->assertFalse(TenantContext::isInitialized());
            $this->assertFalse(TenantContext::isBypassing());
        }
    }

    public function test_queue_context_isolates_organizations(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        Sport::factory()->create(['organization_id' => $orgA->id, 'name' => 'Sport A']);
        Sport::factory()->create(['organization_id' => $orgB->id, 'name' => 'Sport B']);

        TenantContext::reset();
        TenantContext::setContext('queue');
        TenantContext::setOrganizationId($orgA->id);

        $this->assertTrue(TenantContext::isQueue());
        $this->assertEquals(['Sport A'], Sport::pluck('name')->all());

        TenantContext::setOrganizationId($orgB->id);

        $this->assertEquals(['Sport B'], Sport::pluck('name')->all());
    }

    public function test_require_organization_fails_closed_when_unbound(): void
    {
        TenantContext::reset();

        $this->expectException(LogicException::class);
        TenantContext::requireOrganization();
    }

    public function test_require_organization_fails_closed_when_bypassed(): void
    {
        TenantContext::reset();
        TenantContext::setBypass('super-admin');

        $this->expectException(LogicException::class);
        TenantContext::requireOrganization();
    }

    public function test_require_organization_returns_id_when_bound(): void
    {
        $org = Organization::factory()->create();

        TenantContext::reset();
        TenantContext::setOrganizationId($org->id);

        $this->assertSame($org->id, TenantContext::requireOrganization());
    }

    public function test_setting_organization_clears_bypass(): void
    {
        TenantContext::reset();
        TenantContext::setBypass('guest');

        $this->assertTrue(TenantContext::isBypassing());

        $org = Organization::factory()->create();
        TenantContext::setOrganizationId($org->id);

        $this->assertFalse(TenantContext::isBypassing());
        $this->assertNull(TenantContext::bypassReason());
    }
}
