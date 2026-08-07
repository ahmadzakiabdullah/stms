<?php

namespace Tests\Feature;

use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class UserTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    public function test_super_admin_can_create_user(): void
    {
        $org = Organization::factory()->create();
        $super = $this->createSuperAdmin();

        $response = $this->actingAs($super)->post(route('users.store'), [
            'name' => 'New User',
            'username' => 'new_user',
            'email' => 'newuser@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'organization_id' => $org->id,
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
    }

    public function test_user_requires_valid_data(): void
    {
        $super = $this->createSuperAdmin();

        $response = $this->actingAs($super)->post(route('users.store'), [
            'name' => '',
            'username' => 'X',
            'email' => 'not-an-email',
            'password' => 'short',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_org_admin_can_update_own_org_user(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        $user = $this->createUserInOrganization($org);

        $response = $this->actingAs($admin)->put(route('users.update', $user), [
            'name' => 'Updated Name',
            'username' => $user->username,
            'email' => $user->email,
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['name' => 'Updated Name']);
    }

    public function test_non_super_admin_cannot_update_user_in_other_org(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $adminA = $this->createOrgAdmin($orgA);
        $userB = $this->createUserInOrganization($orgB);

        $response = $this->actingAs($adminA)->put(route('users.update', $userB), [
            'name' => 'Hacked',
            'username' => $userB->username,
            'email' => $userB->email,
        ]);

        $response->assertNotFound();
    }

    public function test_non_super_admin_cannot_reset_password_for_other_org_user(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $adminA = $this->createOrgAdmin($orgA);
        $userB = $this->createUserInOrganization($orgB);

        $this->actingAs($adminA)->put(route('users.reset-password', $userB), [
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertNotFound();
    }

    public function test_non_super_admin_cannot_delete_other_org_user(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $adminA = $this->createOrgAdmin($orgA);
        $userB = $this->createUserInOrganization($orgB);

        $this->actingAs($adminA)->delete(route('users.destroy', $userB))->assertNotFound();
        $this->assertNotSoftDeleted('users', ['uuid' => $userB->getKey()]);
    }

    public function test_super_admin_can_delete_user(): void
    {
        $org = Organization::factory()->create();
        $super = $this->createSuperAdmin();
        $user = $this->createUserInOrganization($org);

        $response = $this->actingAs($super)->delete(route('users.destroy', $user));
        $response->assertRedirect(route('users.index'));
        $this->assertSoftDeleted('users', ['email' => $user->email]);
    }
}
