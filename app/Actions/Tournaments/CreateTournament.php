<?php

namespace App\Actions\Tournaments;

use App\Models\Tournament;
use App\Services\TournamentService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateTournament
{
    public function handle(array $data): Tournament
    {
        $user = Auth::user();

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $data['is_active'] = $data['is_active'] ?? true;

        if (empty($data['organization_id'])) {
            $data['organization_id'] = $user->organization_id;
        }

        $data['slug'] = $this->ensureUniqueSlug($data['slug'], $data['organization_id']);

        try {
            $service = new TournamentService;

            return $service->createWithSports($data);
        } catch (QueryException $e) {
            if ($e->getCode() == 23000) {
                throw ValidationException::withMessages([
                    'slug' => ['The slug has already been taken.'],
                ]);
            }
            throw $e;
        }
    }

    private function ensureUniqueSlug(string $slug, string $organizationId): string
    {
        $existing = Tournament::withTrashed()
            ->where('slug', $slug)
            ->where('organization_id', $organizationId)
            ->first();

        if ($existing && $existing->trashed()) {
            $existing->updateQuietly([
                'slug' => $slug.'-removed-'.now()->timestamp,
            ]);
        }

        return $slug;
    }
}
