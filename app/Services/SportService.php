<?php

namespace App\Services;

use App\Models\Sport;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SportService
{
    /**
     * Create a new sport.
     * Handles org scoping and slug generation.
     */
    public function createSport(array $data): Sport
    {
        $user = Auth::user();

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $data['is_active'] = $data['is_active'] ?? true;

        if (empty($data['organization_id'])) {
            $data['organization_id'] = $user->organization_id;
        }

        try {
            $sport = Sport::create($data);
            Log::info('Sport created', ['id' => $sport->id, 'name' => $sport->name, 'org_id' => $sport->organization_id]);

            return $sport;
        } catch (QueryException $e) {
            Log::error('Sport creation failed', ['name' => $data['name'], 'error' => $e->getMessage()]);
            if ($e->getCode() == 23000) {
                // Only treat as slug conflict if it looks like a slug unique violation.
                $msg = strtolower($e->getMessage());
                if (str_contains($msg, 'slug') || str_contains($msg, 'sports_slug_unique')) {
                    throw ValidationException::withMessages([
                        'slug' => ['The slug has already been taken.'],
                    ]);
                }
            }
            throw $e;
        }
    }

    /**
     * Update an existing sport.
     */
    public function updateSport(Sport $sport, array $data): Sport
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $data['is_active'] = $data['is_active'] ?? $sport->is_active;

        try {
            $sport->update($data);
            Log::info('Sport updated', ['id' => $sport->id, 'name' => $sport->name]);

            return $sport;
        } catch (QueryException $e) {
            Log::error('Sport update failed', ['id' => $sport->id, 'error' => $e->getMessage()]);
            if ($e->getCode() == 23000) {
                $msg = strtolower($e->getMessage());
                if (str_contains($msg, 'slug') || str_contains($msg, 'sports_slug_unique')) {
                    throw ValidationException::withMessages([
                        'slug' => ['The slug has already been taken.'],
                    ]);
                }
            }
            throw $e;
        }
    }

    /**
     * Delete a sport (soft delete).
     */
    public function deleteSport(Sport $sport): void
    {
        $sport->delete();
        Log::info('Sport deleted', ['id' => $sport->id, 'name' => $sport->name]);
    }
}
