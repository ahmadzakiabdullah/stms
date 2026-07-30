<?php

namespace App\Services;

use App\Models\Session;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SessionService
{
    /**
     * Create a new session.
     * Handles org scoping and slug generation.
     */
    public function createSession(array $data): Session
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
            $session = Session::create($data);
            Log::info('Session created', ['id' => $session->id, 'name' => $session->name, 'org_id' => $session->organization_id]);

            return $session;
        } catch (QueryException $e) {
            Log::error('Session creation failed', ['name' => $data['name'], 'error' => $e->getMessage()]);
            if ($e->getCode() == 23000) {
                throw ValidationException::withMessages([
                    'slug' => ['The slug has already been taken.'],
                ]);
            }
            throw $e;
        }
    }

    /**
     * Update an existing session.
     */
    public function updateSession(Session $session, array $data): Session
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $data['is_active'] = $data['is_active'] ?? $session->is_active;

        // Do not allow changing organization via update
        unset($data['organization_id']);

        try {
            $session->update($data);
            Log::info('Session updated', ['id' => $session->id, 'name' => $session->name]);

            return $session;
        } catch (QueryException $e) {
            Log::error('Session update failed', ['id' => $session->id, 'error' => $e->getMessage()]);
            if ($e->getCode() == 23000) {
                throw ValidationException::withMessages([
                    'slug' => ['The slug has already been taken.'],
                ]);
            }
            throw $e;
        }
    }

    /**
     * Delete a session (soft delete).
     */
    public function deleteSession(Session $session): void
    {
        $session->delete();
        Log::info('Session deleted', ['id' => $session->id, 'name' => $session->name]);
    }
}
