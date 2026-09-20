<?php

namespace App\Actions\Tournaments;

use App\Models\Tournament;
use App\Services\TournamentService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UpdateTournament
{
    public function handle(Tournament $tournament, array $data, ?TournamentService $service = null): Tournament
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $data['is_active'] = $data['is_active'] ?? $tournament->is_active;

        try {
            return ($service ?? app(TournamentService::class))->updateWithSports($tournament, $data);
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
