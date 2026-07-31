<?php

namespace Tests\Feature\Auth;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.public_registration', true);
        config()->set('app.default_org_slug', 'default');
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        Organization::factory()->create(['slug' => 'default']);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_new_user_gets_organization_id(): void
    {
        Organization::factory()->create(['slug' => 'default']);

        $this->post('/register', [
            'name' => 'Org User',
            'email' => 'orguser@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'orguser@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->organization_id);
    }

    public function test_public_registration_can_be_disabled(): void
    {
        config()->set('app.public_registration', false);

        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'blocked@example.com']);
    }

    public function test_registration_fails_closed_when_default_organization_is_missing(): void
    {
        Organization::factory()->create(['slug' => 'another-tenant']);
        config()->set('app.default_org_slug', 'missing-tenant');

        $response = $this->post('/register', [
            'name' => 'Unassigned User',
            'email' => 'unassigned@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'unassigned@example.com']);
    }

    public function test_registration_fails_closed_without_a_configured_organization_slug(): void
    {
        Organization::factory()->create();
        config()->set('app.default_org_slug', null);

        $response = $this->post('/register', [
            'name' => 'Unconfigured User',
            'email' => 'unconfigured@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['email' => 'unconfigured@example.com']);
    }
}
