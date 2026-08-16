<?php

namespace Tests\Feature;

use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class SecurityHeadersTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    public function test_security_headers_are_present_on_responses(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createStaffUser($org);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy');
    }

    public function test_security_headers_present_on_public_pages(): void
    {
        $response = $this->get(route('public.index'));

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_security_headers_present_on_login_page(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_hsts_header_present_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $org = Organization::factory()->create();
        $user = $this->createStaffUser($org);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertHeader('Strict-Transport-Security');
        $this->assertStringContainsString('max-age=31536000', $response->headers->get('Strict-Transport-Security'));
    }

    public function test_hsts_header_absent_in_non_production(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $org = Organization::factory()->create();
        $user = $this->createStaffUser($org);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_csp_header_present_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['app.csp_report_only' => true]);

        $org = Organization::factory()->create();
        $user = $this->createStaffUser($org);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertHeader('Content-Security-Policy-Report-Only');
        $policy = $response->headers->get('Content-Security-Policy-Report-Only');
        $this->assertStringContainsString("default-src 'self'", $policy);
        $this->assertStringContainsString("object-src 'none'", $policy);
        $this->assertStringNotContainsString("'unsafe-eval'", $policy);
        // During report-only mode (the default), 'unsafe-inline' is included to catch violations without breaking the app.
        // It's only stripped when CSP_REPORT_ONLY is false. The SecurityHeaders middleware implements this logic.
        if (config('app.csp_report_only') === false) {
            $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $policy);
        }
    }

    public function test_csp_enforcing_mode_uses_enforcing_header_and_removes_inline_scripts(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['app.csp_report_only' => false]);

        $org = Organization::factory()->create();
        $user = $this->createStaffUser($org);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertHeader('Content-Security-Policy');
        $response->assertHeaderMissing('Content-Security-Policy-Report-Only');

        $policy = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("script-src 'self';", $policy);
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $policy);
        $this->assertStringContainsString('upgrade-insecure-requests', $policy);
    }
}
