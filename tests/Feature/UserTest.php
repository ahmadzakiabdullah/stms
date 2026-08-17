<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Participant;
use App\Models\Sport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class UserTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    public function test_org_admin_user_index_is_scoped_and_excludes_super_admin_role_assignment(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $admin = $this->createOrgAdmin($orgA);
        $ownUser = $this->createUserInOrganization($orgA, ['name' => 'Own Tenant User']);
        $foreignUser = $this->createUserInOrganization($orgB, ['name' => 'Foreign Tenant User']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertOk();
        $props = $response->viewData('page')['props'];
        $userIds = collect($props['users']['data'])->pluck('uuid');
        $roleNames = collect($props['roles'])->pluck('name');

        $this->assertTrue($userIds->contains($ownUser->uuid));
        $this->assertFalse($userIds->contains($foreignUser->uuid));
        $this->assertFalse($roleNames->contains('super-admin'));
    }

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

    public function test_org_admin_created_user_is_forced_to_own_organization(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Tenant User',
            'username' => 'tenant_user',
            'email' => 'tenant-user@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'tenant-user@example.com',
            'organization_id' => $org->id,
        ]);
    }

    public function test_org_admin_cannot_create_user_in_another_organization(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $admin = $this->createOrgAdmin($orgA);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Foreign Tenant User',
            'username' => 'foreign_tenant_user',
            'email' => 'foreign-tenant@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'organization_id' => $orgB->id,
        ]);

        $response->assertSessionHasErrors('organization_id');
        $this->assertDatabaseMissing('users', ['email' => 'foreign-tenant@example.com']);
    }

    public function test_org_admin_cannot_assign_super_admin_role(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        $superRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Escalated User',
            'username' => 'escalated_user',
            'email' => 'escalated@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'roles' => [$superRole->id],
        ]);

        $response->assertSessionHasErrors('roles.0');
        $this->assertDatabaseMissing('users', ['email' => 'escalated@example.com']);
    }

    public function test_org_admin_cannot_attach_foreign_participant_or_sport(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $admin = $this->createOrgAdmin($orgA);
        $participant = Participant::factory()->create(['organization_id' => $orgB->id]);
        $sport = Sport::factory()->create(['organization_id' => $orgB->id]);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Foreign Relation User',
            'username' => 'foreign_relation_user',
            'email' => 'foreign-relation@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'participant_id' => $participant->id,
            'sports' => [$sport->id],
        ]);

        $response->assertSessionHasErrors(['participant_id', 'sports.0']);
        $this->assertDatabaseMissing('users', ['email' => 'foreign-relation@example.com']);
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
