<?php

namespace Database\Seeders;

use App\Models\EventParticipant;
use App\Models\SquadMember;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DummySquadSeeder extends Seeder
{
    private const OFFICIAL_ROLES = ['manager', 'coach', 'physio', 'assistant_manager'];

    private const MALE_NAMES = [
        'Aiman', 'Amirul', 'Azlan', 'Faiz', 'Hafiz', 'Iqbal', 'Irfan', 'Kamal', 'Khairul', 'Luqman',
        'Muhammad', 'Nazrin', 'Ridzuan', 'Shahrul', 'Syafiq', 'Syamil', 'Wan', 'Zul', 'Danish', 'Farhan',
        'Harith', 'Iman', 'Nabil', 'Rafiq', 'Syahmi', 'Taufik', 'Umar', 'Zaim', 'Akmal', 'Firdaus',
    ];

    private const FEMALE_NAMES = [
        'Aisyah', 'Amira', 'Anis', 'Ayu', 'Balqis', 'Diana', 'Farah', 'Fatin', 'Hana', 'Izzati',
        'Khadijah', 'Maisarah', 'Nadia', 'Nurul', 'Qistina', 'Sarah', 'Siti', 'Sofea', 'Syahirah', 'Umi',
        'Wan Nur', 'Yasmin', 'Zahirah', 'Aina', 'Dania', 'Erin', 'Husna', 'Insyirah', 'Jannah', 'Kartini',
    ];

    private const FAMILY_NAMES = [
        'Ahmad', 'Ismail', 'Abdullah', 'Rahman', 'Hashim', 'Yusof', 'Zainal', 'Omar', 'Ibrahim', 'Rashid',
        'Aziz', 'Hamid', 'Karim', 'Malik', 'Nordin', 'Osman', 'Ramli', 'Shamsudin', 'Tahir', 'Wahab',
    ];

    public function run(): void
    {
        $eventParticipants = EventParticipant::with(['event.sportCategory', 'squadMembers'])
            ->where('status', 'confirmed')
            ->get();

        $rows = [];
        $icSeq = 1;
        $processed = 0;
        $skipped = 0;

        foreach ($eventParticipants as $ep) {
            if (! $ep->event || ! $ep->event->sportCategory) {
                $skipped++;

                continue;
            }

            if ($ep->squadMembers->isNotEmpty()) {
                $skipped++;

                continue;
            }

            $category = $ep->event->sportCategory;
            $facultyIdx = $this->facultyIndex($ep->participant_id);
            $eventIdx = $this->eventIndex($ep->event_id);

            if ($category->usesTotalAthleteQuota()) {
                $total = $category->max_athletes_total ?? 0;
                $male = (int) floor($total / 2);
                $female = $total - $male;
                $allowed = $category->allowedAthleteRoles();
                if (! in_array('athlete_female', $allowed, true)) {
                    $male = $total;
                    $female = 0;
                }
                if (! in_array('athlete_male', $allowed, true)) {
                    $male = 0;
                    $female = $total;
                }

                if (($category->min_male_athletes ?? 0) > $male) {
                    $diff = $category->min_male_athletes - $male;
                    $male += $diff;
                    $female -= $diff;
                }
                if (($category->min_female_athletes ?? 0) > $female) {
                    $diff = $category->min_female_athletes - $female;
                    $female += $diff;
                    $male -= $diff;
                }
            } else {
                $male = max(0, (int) ($category->max_male_athletes ?? 0));
                $female = max(0, (int) ($category->max_female_athletes ?? 0));
            }

            $seq = 0;
            foreach ([['athlete_male', $male], ['athlete_female', $female]] as [$role, $count]) {
                for ($i = 0; $i < $count; $i++) {
                    $rows[] = $this->buildRow($ep, $role, $facultyIdx, $eventIdx, $seq++, $icSeq);
                    $icSeq++;
                }
            }

            $maxOfficials = max(1, (int) ($category->max_officials ?? 1));
            $officialRoles = array_slice(self::OFFICIAL_ROLES, 0, min($maxOfficials, 4));
            foreach ($officialRoles as $role) {
                $rows[] = $this->buildRow($ep, $role, $facultyIdx, $eventIdx, $seq++, $icSeq);
                $icSeq++;
            }

            $processed++;

            if (count($rows) >= 500) {
                SquadMember::insert($rows);
                $rows = [];
            }
        }

        if ($rows !== []) {
            SquadMember::insert($rows);
        }

        $this->command?->info("Dummy squads seeded: {$processed} registrations populated, {$skipped} skipped.");
    }

    private function buildRow(EventParticipant $ep, string $role, int $facultyIdx, int $eventIdx, int $seq, int $icSeq): array
    {
        $isMale = in_array($role, ['athlete_male', 'manager', 'coach', 'assistant_manager'], true);
        $firstNames = $isMale ? self::MALE_NAMES : self::FEMALE_NAMES;
        $family = self::FAMILY_NAMES[($facultyIdx + $seq) % count(self::FAMILY_NAMES)];
        $firstName = $firstNames[($eventIdx + $seq) % count($firstNames)];

        return [
            'id' => (string) Str::uuid(),
            'event_participant_id' => $ep->id,
            'organization_id' => $ep->event->organization_id ?? $ep->organization_id,
            'name' => $firstName.' '.($isMale ? 'bin' : 'binti').' '.$family,
            'role' => $role,
            'identification_no' => sprintf('01%02d%02d-%02d-%04d', 1 + ($icSeq % 12), 1 + ($icSeq % 28), 10, $icSeq % 10000),
            'phone' => '01'.(2 + ($icSeq % 8)).'-'.str_pad((string) ($icSeq % 10000000), 7, '0', STR_PAD_LEFT),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function facultyIndex(string $participantId): int
    {
        return crc32($participantId);
    }

    private function eventIndex(string $eventId): int
    {
        return crc32($eventId);
    }
}
