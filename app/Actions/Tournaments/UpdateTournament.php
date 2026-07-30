<?php

namespace App\Actions\Tournaments;

use App\Models\Tournament;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UpdateTournament
{
    public function handle(Tournament $tournament, array $data): Tournament
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $data['is_active'] = $data['is_active'] ?? $tournament->is_active;

        try {
            $tournament->update($data);

            if (array_key_exists('sports', $data)) {
                $tournament->sports()->sync($data['sports'] ?? []);
            }

            return $tournament;
        } catch (QueryException $e) {
            if ($e->getCode() == 23000) {
                throw ValidationException::withMessages([
                    'slug' => ['The slug has already been taken.'],
                ]);
            }
            throw $e;
        }
    }
}
