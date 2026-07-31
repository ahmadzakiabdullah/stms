<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateSuperAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_securely_creates_the_initial_super_admin(): void
    {
        $organization = Organization::factory()->create(['slug' => 'secure-org']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->artisan('stms:create-super-admin', [
            'email' => 'ADMIN@example.com',
            'organization' => $organization->slug,
        ])
            ->expectsQuestion('Administrator name', 'Secure Admin')
            ->expectsQuestion('Password (minimum 12 characters)', 'long-random-password')
            ->expectsQuestion('Confirm password', 'long-random-password')
            ->expectsOutputToContain('Super-admin [admin@example.com] created')
            ->assertSuccessful();

        $user = User::where('email', 'admin@example.com')->firstOrFail();

        $this->assertSame($organization->id, $user->organization_id);
        $this->assertTrue($user->hasRole('super-admin'));
        $this->assertNotSame('long-random-password', $user->password);
    }

    public function test_it_rejects_a_missing_organization(): void
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->artisan('stms:create-super-admin', [
            'email' => 'admin@example.com',
            'organization' => 'missing',
        ])
            ->expectsQuestion('Administrator name', 'Secure Admin')
            ->expectsQuestion('Password (minimum 12 characters)', 'long-random-password')
            ->expectsQuestion('Confirm password', 'long-random-password')
            ->expectsOutputToContain('Organization [missing] does not exist.')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }
}
