<?php

namespace App\Services;

use App\Models\Sport;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class UserService
{
    /**
     * Create a user with optional role assignment.
     * Password is always hashed.
     */
    public function createUser(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'] ?? null,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'organization_id' => $data['organization_id'] ?? null,
            'participant_id' => $data['participant_id'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        if (! empty($data['roles'])) {
            $roles = Role::whereIn('id', $data['roles'])->get();
            $user->syncRoles($roles);
        }

        if (isset($data['sports'])) {
            $this->syncUserSports($user, $data['sports']);
        }

        Log::info('User created', ['id' => $user->getKey(), 'email' => $user->email, 'org_id' => $user->organization_id]);

        return $user;
    }

    /**
     * Update a user.
     * Only hashes password if provided.
     * Only updates organization_id if explicitly set.
     * Syncs roles if provided in data.
     */
    public function updateUser(User $user, array $data): User
    {
        $updateData = [
            'name' => $data['name'],
            'username' => $data['username'] ?? $user->username,
            'email' => $data['email'],
            'is_active' => $data['is_active'] ?? $user->is_active,
        ];

        if (! empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        if (isset($data['organization_id'])) {
            $updateData['organization_id'] = $data['organization_id'];
        }

        if (isset($data['participant_id'])) {
            $updateData['participant_id'] = $data['participant_id'];
        } else {
            $updateData['participant_id'] = null;
        }

        $user->update($updateData);

        if (isset($data['roles'])) {
            $roles = Role::whereIn('id', $data['roles'])->get();
            $user->syncRoles($roles);
        }

        if (isset($data['sports'])) {
            $this->syncUserSports($user, $data['sports']);
        }

        Log::info('User updated', ['id' => $user->getKey(), 'email' => $user->email]);

        return $user;
    }

    /**
     * Sync the sports an admin-sport user may manage.
     * Sports are always scoped to the user's organization.
     */
    protected function syncUserSports(User $user, array $sports): void
    {
        $orgId = $user->organization_id;

        $sportIds = $orgId
            ? Sport::query()->where('organization_id', $orgId)->whereIn('id', $sports)->pluck('id')->all()
            : Sport::query()->whereIn('id', $sports)->pluck('id')->all();

        $user->sports()->detach();
        foreach ($sportIds as $sportId) {
            $user->sports()->attach($sportId, ['organization_id' => $orgId]);
        }
    }

    /**
     * Delete a user (soft delete).
     */
    public function deleteUser(User $user): void
    {
        $user->delete();

        Log::info('User deleted', ['id' => $user->getKey(), 'email' => $user->email]);
    }
}
