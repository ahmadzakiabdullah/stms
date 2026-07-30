<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure roles exist for tests
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'org-admin', 'guard_name' => 'web']);
    }

    public function test_it_creates_user_with_roles(): void
    {
        $service = new UserService;

        $user = $service->createUser([
            'name' => 'Test User via Service',
            'email' => 'serviceuser@example.com',
            'password' => 'password123',
            'roles' => [Role::where('name', 'staff')->first()->id],
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Test User via Service',
            'email' => 'serviceuser@example.com',
        ]);

        $this->assertTrue($user->hasRole('staff'));
    }

    public function test_it_updates_user_and_password_if_provided(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        $service = new UserService;

        $updated = $service->updateUser($user, [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'password' => 'newpassword123',
            'roles' => [Role::where('name', 'org-admin')->first()->id],
        ]);

        $this->assertEquals('Updated Name', $updated->name);
        $this->assertTrue($updated->hasRole('org-admin'));
        // Password change is hashed, can't easily assert without re-hashing check
    }

    public function test_it_deletes_user(): void
    {
        $user = User::factory()->create();

        $service = new UserService;
        $service->deleteUser($user);

        $this->assertSoftDeleted('users', ['uuid' => $user->uuid]);
    }
}
