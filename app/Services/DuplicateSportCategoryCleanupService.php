<?php

namespace App\Services;

use App\Models\Event;
use App\Models\SportCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DuplicateSportCategoryCleanupService
{
    /**
     * Soft-delete duplicate categories only when another category with the same
     * tenant, sport and name exists and the duplicate has never been referenced.
     *
     * @return array{duplicate_groups: int, removable: Collection<int, SportCategory>, blocked_groups: Collection<int, Collection<int, SportCategory>>}
     */
    public function inspect(): array
    {
        $categories = SportCategory::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->orderBy('created_at')
            ->get();

        $eventCounts = Event::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->selectRaw('sport_category_id, COUNT(*) as aggregate')
            ->groupBy('sport_category_id')
            ->pluck('aggregate', 'sport_category_id');

        $groups = $categories
            ->groupBy(fn (SportCategory $category): string => implode('|', [
                $category->organization_id,
                $category->sport_id,
                mb_strtolower(trim($category->name)),
            ]))
            ->filter(fn (Collection $group): bool => $group->count() > 1);

        $removable = collect();
        $blockedGroups = collect();

        foreach ($groups as $group) {
            $referenced = $group->filter(
                fn (SportCategory $category): bool => (int) ($eventCounts[$category->id] ?? 0) > 0
            );

            if ($referenced->count() !== 1) {
                $blockedGroups->push($group);

                continue;
            }

            $removable->push(...$group->reject(
                fn (SportCategory $category): bool => $category->is($referenced->first())
            ));
        }

        return [
            'duplicate_groups' => $groups->count(),
            'removable' => $removable,
            'blocked_groups' => $blockedGroups,
        ];
    }

    /** @param Collection<int, SportCategory> $categories */
    public function cleanup(Collection $categories): int
    {
        return DB::transaction(function () use ($categories): int {
            $deleted = 0;

            foreach ($categories as $category) {
                $stillReferenced = Event::withoutGlobalScopes()
                    ->whereNull('deleted_at')
                    ->where('sport_category_id', $category->id)
                    ->exists();

                if ($stillReferenced) {
                    continue;
                }

                $category->delete();
                $deleted++;
            }

            return $deleted;
        });
    }
}
