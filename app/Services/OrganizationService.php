<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrganizationService
{
    /**
     * Create a new organization.
     */
    public function createOrganization(array $data): Organization
    {
        $this->validateParent(null, $data['parent_id'] ?? null);
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $data['is_active'] = $data['is_active'] ?? true;

        try {
            $org = Organization::create($data);
            Log::info('Organization created', ['id' => $org->id, 'name' => $org->name]);

            return $org;
        } catch (QueryException $e) {
            Log::error('Organization creation failed', ['name' => $data['name'], 'error' => $e->getMessage()]);
            if ($e->getCode() == 23000) { // Integrity constraint violation (duplicate key)
                throw ValidationException::withMessages([
                    'slug' => ['The slug has already been taken.'],
                ]);
            }
            throw $e;
        }
    }

    /**
     * Update an existing organization.
     */
    public function updateOrganization(Organization $organization, array $data): Organization
    {
        $this->validateParent($organization, $data['parent_id'] ?? null);
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $data['is_active'] = $data['is_active'] ?? $organization->is_active;

        try {
            $organization->update($data);
            Log::info('Organization updated', ['id' => $organization->id, 'name' => $organization->name]);

            return $organization;
        } catch (QueryException $e) {
            Log::error('Organization update failed', ['id' => $organization->id, 'error' => $e->getMessage()]);
            if ($e->getCode() == 23000) { // Integrity constraint violation (duplicate key)
                throw ValidationException::withMessages([
                    'slug' => ['The slug has already been taken.'],
                ]);
            }
            throw $e;
        }
    }

    /**
     * Delete an organization (soft delete).
     */
    public function deleteOrganization(Organization $organization): void
    {
        $organization->delete();
        Log::info('Organization deleted', ['id' => $organization->id, 'name' => $organization->name]);
    }

    private function validateParent(?Organization $organization, ?string $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        $parent = Organization::query()->find($parentId);
        if (! $parent) {
            throw ValidationException::withMessages(['parent_id' => ['The selected parent organization is invalid.']]);
        }

        if ($organization && ($parent->id === $organization->id || $this->isDescendant($parent, $organization->id))) {
            throw ValidationException::withMessages(['parent_id' => ['An organization cannot be parented to itself or one of its descendants.']]);
        }
    }

    private function isDescendant(Organization $candidate, string $organizationId): bool
    {
        $visited = [];

        while ($candidate->parent_id !== null && ! isset($visited[$candidate->id])) {
            $visited[$candidate->id] = true;
            if ($candidate->parent_id === $organizationId) {
                return true;
            }
            $candidate = Organization::query()->find($candidate->parent_id);
            if (! $candidate) {
                return false;
            }
        }

        return false;
    }
}
