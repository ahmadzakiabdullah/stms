<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Participant;
use App\Models\SquadMember;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DummyFutsalMenSeeder extends Seeder
{
    private const FIRST_NAMES = [
        'Aiman', 'Amirul', 'Azlan', 'Faiz', 'Hafiz', 'Iqbal', 'Irfan', 'Kamal',
        'Khairul', 'Luqman', 'Nazrin', 'Ridzuan', 'Shahrul', 'Syafiq', 'Taufik',
    ];

    private const FAMILY_NAMES = [
        'Ahmad', 'Ismail', 'Abdullah', 'Rahman', 'Hashim', 'Yusof', 'Zainal',
        'Omar', 'Ibrahim', 'Rashid', 'Aziz', 'Hamid', 'Karim', 'Nordin',
    ];

    public function run(): void
    {
        $event = Event::query()
            ->whereHas('tournament', fn ($query) => $query->where('name', 'SAF 2026 Fasa 2'))
            ->whereHas('sport', fn ($query) => $query->where('slug', 'futsal'))
            ->whereHas('sportCategory', fn ($query) => $query->where('slug', 'futsal-men-s'))
            ->firstOrFail();

        $participants = Participant::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $registrations = 0;
        $members = 0;

        foreach ($participants as $participantIndex => $participant) {
            $registration = EventParticipant::withTrashed()->updateOrCreate(
                ['event_id' => $event->id, 'participant_id' => $participant->id],
                [
                    'organization_id' => $event->organization_id,
                    'registration_date' => now(),
                    'status' => 'confirmed',
                ],
            );

            if ($registration->trashed()) {
                $registration->restore();
            }

            $registrations++;

            for ($playerIndex = 0; $playerIndex < 12; $playerIndex++) {
                $matrixNo = sprintf('DUMMY-%02d-%02d', $participantIndex + 1, $playerIndex + 1);
                $member = SquadMember::firstOrCreate(
                    ['event_participant_id' => $registration->id, 'matrix_no' => $matrixNo],
                    [
                        'organization_id' => $event->organization_id,
                        'name' => self::FIRST_NAMES[($participantIndex + $playerIndex) % count(self::FIRST_NAMES)].' bin '.self::FAMILY_NAMES[($participantIndex + $playerIndex) % count(self::FAMILY_NAMES)],
                        'role' => 'athlete_male',
                        'identification_no' => sprintf('DUMMY-%04d', ($participantIndex * 100) + $playerIndex + 1),
                        'phone' => null,
                        'is_active' => true,
                    ],
                );
                $members += $member->wasRecentlyCreated ? 1 : 0;
            }

            $managerMatrix = sprintf('DUMMY-%02d-M', $participantIndex + 1);
            $manager = SquadMember::firstOrCreate(
                ['event_participant_id' => $registration->id, 'matrix_no' => $managerMatrix],
                [
                    'organization_id' => $event->organization_id,
                    'name' => 'Manager '.$participant->name,
                    'role' => 'manager',
                    'identification_no' => sprintf('DUMMY-M%03d', $participantIndex + 1),
                    'phone' => '012-000'.str_pad((string) ($participantIndex + 1), 4, '0', STR_PAD_LEFT),
                    'is_active' => true,
                ],
            );
            $members += $manager->wasRecentlyCreated ? 1 : 0;
        }

        $this->command?->info("Dummy Futsal Men's data seeded: {$registrations} registrations, {$members} new squad members.");
    }
}
