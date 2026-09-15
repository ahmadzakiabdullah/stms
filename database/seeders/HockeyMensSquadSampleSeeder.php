<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\SquadMember;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

class HockeyMensSquadSampleSeeder extends Seeder
{
    private const ATHLETE_NAMES = ['Aiman', 'Amirul', 'Azlan', 'Faiz', 'Hafiz', 'Iqbal', 'Irfan', 'Kamal', 'Khairul', 'Luqman', 'Nazrin', 'Ridzuan', 'Syafiq', 'Taufik', 'Zaim'];
    private const FAMILY_NAMES = ['Ahmad', 'Ismail', 'Abdullah', 'Rahman', 'Hashim', 'Yusof', 'Zainal', 'Omar'];

    public function run(): void
    {
        if (app()->environment('production') && ! config('app.allow_demo_seeding')) {
            throw new RuntimeException('HockeyMensSquadSampleSeeder requires ALLOW_DEMO_SEEDING=true in production.');
        }

        $event = Event::query()
            ->where(function ($query) {
                $query->where('name', "Sukan Antara Fakulti 2026 - Hockey - Men's")
                    ->orWhere('name', "Hockey (Men's)")
                    ->orWhere('slug', 'hockey-men-s');
            })
            ->firstOrFail();
        $registrations = EventParticipant::query()->where('event_id', $event->id)->where('status', 'confirmed')->get();
        $created = 0;

        foreach ($registrations as $teamIndex => $registration) {
            $rows = [[
                'matrix_no' => "HOC-".str_pad((string) ($teamIndex + 1), 2, '0', STR_PAD_LEFT).'-O01',
                'name' => 'Manager '.($registration->participant?->name ?? 'Team '.($teamIndex + 1)),
                'role' => 'manager',
                'phone' => '012-'.str_pad((string) ($teamIndex + 1), 7, '0', STR_PAD_LEFT),
            ]];

            for ($player = 0; $player < 15; $player++) {
                $rows[] = [
                    'matrix_no' => sprintf('HOC-%02d-%02d', $teamIndex + 1, $player + 1),
                    'name' => self::ATHLETE_NAMES[$player].' bin '.self::FAMILY_NAMES[($teamIndex + $player) % count(self::FAMILY_NAMES)],
                    'role' => 'athlete_male',
                    'phone' => null,
                ];
            }

            foreach ($rows as $row) {
                $member = SquadMember::firstOrCreate(
                    ['event_participant_id' => $registration->id, 'matrix_no' => $row['matrix_no']],
                    array_merge($row, [
                        'organization_id' => $event->organization_id,
                        'identification_no' => 'HOC-'.str_replace('-', '', $row['matrix_no']),
                        'is_active' => true,
                    ]),
                );
                $created += $member->wasRecentlyCreated ? 1 : 0;
            }
        }

        $this->command?->info("Hockey Men's squads seeded: {$registrations->count()} teams, {$created} new members.");
    }
}
