<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Sport;
use App\Models\SportCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SAF2026SportsCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production') && ! config('app.allow_demo_seeding')) {
            throw new \RuntimeException('SAF sports/category seeding is disabled in production. Set ALLOW_DEMO_SEEDING=true for an approved load.');
        }

        $organization = Organization::where('slug', 'utem')->firstOrFail();
        $catalog = [
            'Badminton' => ['Mix'], 'Basketball' => ["Men's", "Women's"],
            'Volleyball' => ["Men's", "Women's"], 'Football' => ["Men's"],
            'Tennis' => ['Mix'], 'Hockey' => ["Men's", "Women's"],
            'Softball' => ["Men's"], 'Chess' => ['Mix'],
            'Handball' => ["Men's", "Women's"], 'Cycling' => ["Men's", "Women's"],
            'E-Sport (Mobile Legends)' => ['Open'], 'E-Sport (Valorant)' => ['Open'],
            'Netball' => ["Women's"], 'Archery' => ['Mix'], 'Aerobics' => ['Mix'],
            'Futsal' => ["Men's", "Women's"], 'Petanque' => ['Mix'],
            'Bowling' => [], 'Tenpin Bowling' => ['Mix'], 'Table Tennis' => ['Mix'],
            'Rugby' => ["Men's"], 'Sepak Takraw' => ['Team'],
            'Indoor Rowing' => ["Men's", "Women's"], 'Lawn Bowls' => ['Mix'],
        ];

        foreach ($catalog as $sportName => $categories) {
            $sport = Sport::withTrashed()->firstOrCreate(
                ['organization_id' => $organization->id, 'slug' => Str::slug($sportName)],
                ['organization_id' => $organization->id, 'name' => $sportName, 'is_active' => true]
            );
            $sport->restore();

            foreach ($categories as $categoryName) {
                $slug = Str::slug($sportName.'-'.$categoryName);
                $genderBased = in_array($categoryName, ["Men's", "Women's"], true);
                $attributes = [
                    'organization_id' => $organization->id,
                    'sport_id' => $sport->id,
                    'name' => $categoryName,
                    'quota_mode' => $genderBased ? 'gender_based' : 'open_total',
                    'max_athletes_total' => null,
                    'max_male_athletes' => null,
                    'max_female_athletes' => null,
                    'min_male_athletes' => 0,
                    'min_female_athletes' => 0,
                    'max_officials' => 1,
                ];

                $category = SportCategory::query()
                    ->where('sport_id', $sport->id)
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($categoryName)])
                    ->first();

                $category ??= SportCategory::withTrashed()->updateOrCreate(
                    ['sport_id' => $sport->id, 'slug' => $slug],
                    array_merge($attributes, ['slug' => $slug])
                );

                $category->fill($attributes)->save();
                $category->restore();
            }
        }

        $this->command?->info('SAF sports and categories seeded. No events, participants, users, registrations, fixtures, or results were created.');
    }
}
