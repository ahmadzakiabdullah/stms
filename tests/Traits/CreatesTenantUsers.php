<?php

namespace Tests\Traits;

use App\Models\Organization;
use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Helper trait for creating users in specific organizations with roles.
 * Use this in tests that need to verify multi-tenancy scoping.
 */
trait CreatesTenantUsers
{
    protected function createUserInOrganization(Organization $organization, array $attributes = []): User
    {
        return User::factory()
            ->forOrganization($organization)
            ->create($attributes);
    }

    protected function createSuperAdmin(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);

        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $user->assignRole($superAdmin);

        return $user;
    }

    protected function createOrgAdmin(Organization $organization, array $attributes = []): User
    {
        $user = $this->createUserInOrganization($organization, $attributes);

        $orgAdmin = Role::firstOrCreate(['name' => 'org-admin', 'guard_name' => 'web']);
        $user->assignRole($orgAdmin);

        return $user;
    }

    protected function createStaffUser(Organization $organization, array $attributes = []): User
    {
        $user = $this->createUserInOrganization($organization, $attributes);

        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $user->assignRole($staff);

        return $user;
    }
}
