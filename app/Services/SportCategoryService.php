<?php

namespace App\Services;

use App\Models\Sport;
use App\Models\SportCategory;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SportCategoryService
{
    /**
     * Create a new sport category.
     * Organization is derived from the parent sport.
     */
    public function createSportCategory(array $data): SportCategory
    {
        $user = Auth::user();

        $sport = Sport::findOrFail($data['sport_id']);

        $data['quota_mode'] = $data['quota_mode'] ?? 'gender_based';

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($sport->name.' '.$data['name']);
        }

        // Ensure slug is unique per sport
        $data['slug'] = $this->makeSlugUnique($data['slug'], $data['sport_id']);

        // Set organization_id from the sport (ensures consistency)
        $data['organization_id'] = $sport->organization_id;

        try {
            $category = SportCategory::create($data);
            Log::info('Sport category created', ['id' => $category->id, 'name' => $category->name, 'sport_id' => $category->sport_id]);

            return $category;
        } catch (QueryException $e) {
            Log::error('Sport category creation failed', ['name' => $data['name'], 'error' => $e->getMessage()]);
            if ($e->getCode() == 23000) {
                throw ValidationException::withMessages([
                    'slug' => ['The slug has already been taken for this sport.'],
                ]);
            }
            throw $e;
        }
    }

    /**
     * Update an existing sport category.
     */
    public function updateSportCategory(SportCategory $sportCategory, array $data): SportCategory
    {
        if (empty($data['slug'])) {
            $sportName = $sportCategory->sport?->name ?? '';
            $data['slug'] = Str::slug($sportName.' '.$data['name']);
        }

        $data['slug'] = $this->makeSlugUnique($data['slug'], $sportCategory->sport_id, $sportCategory->id);

        try {
            $sportCategory->update($data);
            Log::info('Sport category updated', ['id' => $sportCategory->id, 'name' => $sportCategory->name]);

            return $sportCategory;
        } catch (QueryException $e) {
            Log::error('Sport category update failed', ['id' => $sportCategory->id, 'error' => $e->getMessage()]);
            if ($e->getCode() == 23000) {
                throw ValidationException::withMessages([
                    'slug' => ['The slug has already been taken for this sport.'],
                ]);
            }
            throw $e;
        }
    }

    private function makeSlugUnique(string $slug, string $sportId, ?string $excludeId = null): string
    {
        $base = $slug;
        $counter = 1;

        while (true) {
            $query = SportCategory::where('slug', $slug)
                ->where('sport_id', $sportId);

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if (! $query->exists()) {
                return $slug;
            }

            $slug = $base.'-'.$counter;
            $counter++;
        }
    }

    /**
     * Delete a sport category (soft delete).
     */
    public function deleteSportCategory(SportCategory $sportCategory): void
    {
        $sportCategory->delete();
        Log::info('Sport category deleted', ['id' => $sportCategory->id, 'name' => $sportCategory->name]);
    }
}
