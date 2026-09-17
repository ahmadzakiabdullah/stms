<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Sport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ImportSports2026Seeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()
            ->where('slug', 'utem')
            ->firstOrFail();

        $sports = [
            'Badminton', 'Bola Keranjang', 'Bola Tampar', 'Bola Sepak',
            'Tenis', 'Hoki', 'Sofbol', 'Catur', 'Bola Baling', 'Berbasikal',
            'Bola Jaring', 'Memanah', 'Futsal', 'Petanque',
            'Tenpin Boling', 'Ping Pong', 'Ragbi', 'Sepak Takraw',
            'Indoor Rowing', 'Lawn Bowls', 'E-Sport Mobile Legend',
            'E-Sport Valorant', 'Kayak',
        ];

        foreach ($sports as $name) {
            $sport = Sport::withTrashed()->updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'slug' => Str::slug($name),
                ],
                [
                    'name' => $name,
                    'is_active' => true,
                ],
            );

            $sport->restore();
        }

        $this->command?->info('Imported '.count($sports).' SAF 2026 sports for UTeM.');
    }
}
