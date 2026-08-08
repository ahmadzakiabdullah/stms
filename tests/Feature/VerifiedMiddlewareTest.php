<?php

namespace Tests\Feature;

use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class VerifiedMiddlewareTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    public function test_unverified_user_can_access_dashboard(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createStaffUser($org);
        $user->email_verified_at = null;
        $user->save();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
    }

    public function test_unverified_user_can_access_authorized_system_routes(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createStaffUser($org);
        $user->email_verified_at = null;
        $user->save();

        $response = $this->actingAs($user)->get(route('events.index'));

        $response->assertOk();
    }

    public function test_unverified_user_can_access_matches(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createStaffUser($org);
        $user->email_verified_at = null;
        $user->save();

        $response = $this->actingAs($user)->get(route('matches.index'));

        $response->assertOk();
    }

    public function test_unverified_user_can_access_results(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createStaffUser($org);
        $user->email_verified_at = null;
        $user->save();

        $response = $this->actingAs($user)->get(route('results.index'));

        $response->assertOk();
    }

    public function test_unverified_user_can_access_rankings(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createStaffUser($org);
        $user->email_verified_at = null;
        $user->save();

        $response = $this->actingAs($user)->get(route('rankings.index'));

        $response->assertOk();
    }

    public function test_unverified_user_can_access_profile(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createStaffUser($org);
        $user->email_verified_at = null;
        $user->save();

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertOk();
    }

    public function test_verified_user_can_access_sensitive_routes(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createStaffUser($org);
        $user->email_verified_at = now();
        $user->save();

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();

        $response = $this->actingAs($user)->get(route('events.index'));
        $response->assertOk();

        $response = $this->actingAs($user)->get(route('notifications.index'));
        $response->assertOk();
    }
}
