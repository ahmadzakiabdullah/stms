<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Sport;
use App\Models\SportCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ImportSportsCategories2026Seeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->where('slug', 'utem')->firstOrFail();

        $catalog = [
            'Badminton' => [['Badminton Campuran', 6, 6, 1]],
            'Kayak' => [['Kayak Campuran', 5, 5, 1]],
            'Lawn Bowls' => [['Lawn Bowls Campuran', 3, 2, 1]],
            'Tenis' => [['Tenis Campuran', 6, 2, 1]],
            'Ping Pong' => [['Ping Pong Campuran', 6, 6, 1]],
            'Tenpin Boling' => [['Tenpin Boling Campuran', 5, 5, 1]],
            'Catur' => [['Catur Campuran', 6, 6, 1]],
            'Petanque' => [['Petanque Campuran', 4, 4, 1]],
            'Memanah' => [['Memanah Campuran', 4, 4, 1]],
            'Bola Keranjang' => [['Bola Keranjang Lelaki', 12, 0, 1], ['Bola Keranjang Wanita', 0, 12, 1]],
            'Indoor Rowing' => [['Indoor Rowing Lelaki', 6, 0, 1], ['Indoor Rowing Wanita', 0, 6, 1]],
            'Sepak Takraw' => [['Sepak Takraw Lelaki', 12, 0, 1]],
            'Ragbi' => [['Ragbi Lelaki', 16, 0, 1]],
            'Futsal' => [['Futsal Lelaki', 12, 0, 1], ['Futsal Wanita', 0, 12, 1]],
            'Berbasikal' => [['Berbasikal Lelaki', 5, 0, 1], ['Berbasikal Wanita', 0, 5, 1]],
            'Bola Baling' => [['Bola Baling Lelaki', 12, 0, 1], ['Bola Baling Wanita', 0, 12, 1]],
            'Sofbol' => [['Sofbol Lelaki', 17, 0, 1]],
            'Hoki' => [['Hoki Lelaki', 15, 0, 1], ['Hoki Wanita', 0, 15, 1]],
            'Bola Sepak' => [['Bola Sepak Lelaki', 25, 0, 2]],
            'Bola Tampar' => [['Bola Tampar Lelaki', 12, 0, 1], ['Bola Tampar Wanita', 0, 12, 1]],
            'E-Sport Mobile Legend' => [['E-Sport Mobile Legend Terbuka', 6, 0, 1]],
            'E-Sport Valorant' => [['E-Sport Valorant Terbuka', 6, 0, 1]],
            'Bola Jaring' => [['Bola Jaring Wanita', 0, 12, 1]],
        ];

        $count = 0;
        foreach ($catalog as $sportName => $categories) {
            $sport = Sport::query()
                ->where('organization_id', $organization->id)
                ->where('slug', Str::slug($sportName))
                ->firstOrFail();

            foreach ($categories as [$name, $male, $female, $officials]) {
                $openCategory = str_ends_with($name, 'Terbuka');
                SportCategory::withTrashed()->updateOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'sport_id' => $sport->id,
                        'name' => $name,
                    ],
                    [
                        'slug' => Str::slug($sportName.'-'.$name),
                        'quota_mode' => $openCategory ? 'open_total' : 'gender_based',
                        'max_athletes_total' => $openCategory ? $male : null,
                        'max_male_athletes' => $openCategory ? null : $male,
                        'max_female_athletes' => $openCategory ? null : $female,
                        'min_male_athletes' => 0,
                        'min_female_athletes' => 0,
                        'max_officials' => $officials,
                    ],
                )->restore();
                $count++;
            }
        }

        $this->command?->info("Imported {$count} SAF 2026 sport categories for UTeM.");
    }
}
