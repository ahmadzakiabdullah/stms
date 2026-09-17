<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Session;
use App\Models\SessionSport;
use App\Models\SessionSportCategory;
use App\Models\Sport;
use Illuminate\Database\Seeder;

class ConfigureSAF2026SessionSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->where('slug', 'utem')->firstOrFail();
        $session = Session::query()
            ->where('organization_id', $organization->id)
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->where('name', 'like', '%SAF%2026%')
                    ->orWhere('slug', 'like', '%saf%2026%');
            })
            ->firstOrFail();

        $sports = Sport::query()
            ->where('organization_id', $organization->id)
            ->where('is_active', true)
            ->with('categories')
            ->get();

        $categoryCount = 0;
        foreach ($sports as $sport) {
            $sessionSport = SessionSport::updateOrCreate(
                ['session_id' => $session->id, 'sport_id' => $sport->id],
                ['organization_id' => $organization->id, 'is_active' => true],
            );

            foreach ($sport->categories as $category) {
                SessionSportCategory::updateOrCreate(
                    ['session_sport_id' => $sessionSport->id, 'sport_category_id' => $category->id],
                    [
                        'organization_id' => $organization->id,
                        'name' => $category->name,
                        'quota_mode' => $category->quota_mode,
                        'max_athletes_total' => $category->max_athletes_total,
                        'max_male_athletes' => $category->max_male_athletes,
                        'max_female_athletes' => $category->max_female_athletes,
                        'max_officials' => $category->max_officials,
                    ],
                );
                $categoryCount++;
            }
        }

        $this->command?->info("Configured {$sports->count()} sports and {$categoryCount} categories for {$session->name}.");
    }
}
