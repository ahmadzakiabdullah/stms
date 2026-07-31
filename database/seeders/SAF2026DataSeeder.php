<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Pool;
use App\Models\Registration;
use App\Models\Session;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class SAF2026DataSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production') && ! config('app.allow_demo_seeding')) {
            throw new \RuntimeException(
                'SAF2026DataSeeder is disabled in production. Set ALLOW_DEMO_SEEDING=true only for an approved, controlled data load.'
            );
        }

        $org = Organization::withTrashed()->firstOrCreate(
            ['slug' => 'utem'],
            ['name' => 'Universiti Teknikal Malaysia Melaka', 'slug' => 'utem', 'organization_type' => 'university', 'is_active' => true]
        );
        if ($org->trashed()) {
            $org->restore();
        }

        $orgId = $org->id;

        // Ensure dean role
        $deanRole = Role::firstOrCreate(['name' => 'dean', 'guard_name' => 'web']);
        $facRepRole = Role::where('name', 'faculty-representative')->first();
        $deanPerms = ['view event participants', 'create event participants', 'edit event participants', 'delete event participants'];
        $deanRole->givePermissionTo($deanPerms);

        // Session
        $session = Session::withTrashed()->firstOrCreate(
            ['slug' => 'saf-2026', 'organization_id' => $orgId],
            [
                'organization_id' => $orgId,
                'name' => 'Sukan Antara Fakulti 2026',
                'slug' => 'saf-2026',
                'description' => 'Sukan Antara Fakulti 2026 - Universiti Teknikal Malaysia Melaka',
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-30',
                'is_active' => true,
            ]
        );
        if ($session->trashed()) {
            $session->restore();
        }

        // Tournaments
        $fasa1 = Tournament::withTrashed()->firstOrCreate(
            ['slug' => 'saf-2026-fasa-1', 'session_id' => $session->id],
            [
                'organization_id' => $orgId,
                'session_id' => $session->id,
                'name' => 'SAF 2026 Fasa 1',
                'slug' => 'saf-2026-fasa-1',
                'description' => 'Sukan Antara Fakulti 2026 Fasa 1 - 11-13 September 2026',
                'start_date' => '2026-09-11',
                'end_date' => '2026-09-13',
                'ranking_strategy' => 'points',
                'is_active' => true,
            ]
        );
        if ($fasa1->trashed()) {
            $fasa1->restore();
        }

        $fasa2 = Tournament::withTrashed()->firstOrCreate(
            ['slug' => 'saf-2026-fasa-2', 'session_id' => $session->id],
            [
                'organization_id' => $orgId,
                'session_id' => $session->id,
                'name' => 'SAF 2026 Fasa 2',
                'slug' => 'saf-2026-fasa-2',
                'description' => 'Sukan Antara Fakulti 2026 Fasa 2 - 25-27 September 2026',
                'start_date' => '2026-09-25',
                'end_date' => '2026-09-27',
                'ranking_strategy' => 'points',
                'is_active' => true,
            ]
        );
        if ($fasa2->trashed()) {
            $fasa2->restore();
        }

        // Sports config
        $sportsConfig = [
            'fasa-1' => [
                'tournament' => $fasa1,
                'sports' => [
                    ['name' => 'Badminton', 'cats' => [
                        ['name' => 'Mix', 'slug' => 'badminton-mix', 'quota_mode' => 'gender_based', 'max_male_athletes' => 6, 'max_female_athletes' => 6, 'max_officials' => 1],
                    ]],
                    ['name' => 'Basketball', 'cats' => [
                        ['name' => 'Men\'s', 'slug' => 'basketball-men-s', 'quota_mode' => 'gender_based', 'max_male_athletes' => 12, 'max_female_athletes' => 0, 'max_officials' => 1],
                        ['name' => 'Women\'s', 'slug' => 'basketball-women-s', 'quota_mode' => 'gender_based', 'max_male_athletes' => 0, 'max_female_athletes' => 12, 'max_officials' => 1],
                    ]],
                    ['name' => 'Volleyball', 'cats' => [
                        ['name' => 'Men\'s', 'slug' => 'volleyball-men-s', 'quota_mode' => 'gender_based', 'max_male_athletes' => 12, 'max_female_athletes' => 0, 'max_officials' => 1],
                        ['name' => 'Women\'s', 'slug' => 'volleyball-women-s', 'quota_mode' => 'gender_based', 'max_male_athletes' => 0, 'max_female_athletes' => 12, 'max_officials' => 1],
                    ]],
                    ['name' => 'Football', 'cats' => [
                        ['name' => 'Men\'s', 'slug' => 'football-men-s', 'quota_mode' => 'gender_based', 'max_male_athletes' => 25, 'max_female_athletes' => 0, 'max_officials' => 2],
                    ]],
                    ['name' => 'Tennis', 'cats' => [
                        ['name' => 'Mix', 'slug' => 'tennis-mix', 'quota_mode' => 'gender_based', 'max_male_athletes' => 6, 'max_female_athletes' => 2, 'max_officials' => 1],
                    ]],
                    ['name' => 'Hockey', 'cats' => [
                        ['name' => 'Men\'s', 'slug' => 'hockey-men-s', 'quota_mode' => 'gender_based', 'max_male_athletes' => 15, 'max_female_athletes' => 0, 'max_officials' => 1],
                        ['name' => 'Women\'s', 'slug' => 'hockey-women-s', 'quota_mode' => 'gender_based', 'max_male_athletes' => 0, 'max_female_athletes' => 15, 'max_officials' => 1],
                    ]],
                    ['name' => 'Softball', 'cats' => [
                        ['name' => 'Men\'s', 'slug' => 'softball-men-s', 'quota_mode' => 'gender_based', 'max_male_athletes' => 17, 'max_female_athletes' => 0, 'max_officials' => 1],
                    ]],
                    ['name' => 'Chess', 'cats' => [
                        ['name' => 'Mix', 'slug' => 'chess-mix', 'quota_mode' => 'gender_based', 'max_male_athletes' => 6, 'max_female_athletes' => 6, 'max_officials' => 1],
                    ]],
                    ['name' => 'Handball', 'cats' => [
                        ['name' => 'Men\'s', 'slug' => 'handball-men-s', 'quota_mode' => 'gender_based', 'max_male_athletes' => 12, 'max_female_athletes' => 0, 'max_officials' => 1],
                        ['name' => 'Women\'s', 'slug' => 'handball-women-s', 'quota_mode' => 'gender_based', 'max_male_athletes' => 0, 'max_female_athletes' => 12, 'max_officials' => 1],
                    ]],
                    ['name' => 'Cycling', 'cats' => [
                        ['name' => 'Men\'s', 'slug' => 'cycling-men-s', 'quota_mode' => 'gender_based', 'max_male_athletes' => 5, 'max_female_athletes' => 0, 'max_officials' => 1],
                        ['name' => 'Women\'s', 'slug' => 'cycling-women-s', 'quota_mode' => 'gender_based', 'max_male_athletes' => 0, 'max_female_athletes' => 5, 'max_officials' => 1],
                    ]],
                    ['name' => 'E-Sport (Mobile Legends)', 'cats' => [
                        ['name' => 'Open', 'slug' => 'e-sport-mobile-legends-open', 'quota_mode' => 'open_total', 'max_athletes_total' => 6, 'max_male_athletes' => 6, 'max_female_athletes' => 6, 'min_male_athletes' => 0, 'min_female_athletes' => 0, 'max_officials' => 1],
                    ]],
                    ['name' => 'E-Sport (Valorant)', 'cats' => [
                        ['name' => 'Open', 'slug' => 'e-sport-valorant-open', 'quota_mode' => 'open_total', 'max_athletes_total' => 6, 'max_male_athletes' => 6, 'max_female_athletes' => 6, 'min_male_athletes' => 0, 'min_female_athletes' => 0, 'max_officials' => 1],
                    ]],
                    ['name' => 'Netball', 'cats' => [
                        ['name' => 'Women\'s', 'slug' => 'netball-women-s', 'quota_mode' => 'gender_based', 'max_athletes_total' => 0, 'max_male_athletes' => 0, 'max_female_athletes' => 12, 'min_male_athletes' => 0, 'min_female_athletes' => 0, 'max_officials' => 1],
                    ]],
                    ['name' => 'Archery', 'cats' => [
                        ['name' => 'Mix', 'slug' => 'archery-mix', 'quota_mode' => 'gender_based', 'max_athletes_total' => 0, 'max_male_athletes' => 4, 'max_female_athletes' => 4, 'min_male_athletes' => 0, 'min_female_athletes' => 0, 'max_officials' => 1],
                    ]],
                ],
            ],
            'fasa-2' => [
                'tournament' => $fasa2,
                'sports' => [
                    ['name' => 'Aerobics', 'cats' => [
                        ['name' => 'Mix', 'slug' => 'aerobics-mix', 'quota_mode' => 'gender_based', 'max_athletes_total' => 0, 'max_male_athletes' => 5, 'max_female_athletes' => 5, 'min_male_athletes' => 0, 'min_female_athletes' => 0, 'max_officials' => 1],
                    ]],
                    ['name' => 'Futsal', 'cats' => [
                        ['name' => 'Men\'s', 'slug' => 'futsal-men-s', 'quota_mode' => 'gender_based', 'max_athletes_total' => 0, 'max_male_athletes' => 12, 'max_female_athletes' => 0, 'min_male_athletes' => 0, 'min_female_athletes' => 0, 'max_officials' => 1],
                        ['name' => 'Women\'s', 'slug' => 'futsal-women-s', 'quota_mode' => 'gender_based', 'max_athletes_total' => 0, 'max_male_athletes' => 0, 'max_female_athletes' => 12, 'min_male_athletes' => 0, 'min_female_athletes' => 0, 'max_officials' => 1],
                    ]],
                    ['name' => 'Petanque', 'cats' => [
                        ['name' => 'Mix', 'slug' => 'petanque-mix', 'quota_mode' => 'gender_based', 'max_athletes_total' => 0, 'max_male_athletes' => 4, 'max_female_athletes' => 4, 'min_male_athletes' => 0, 'min_female_athletes' => 0, 'max_officials' => 1],
                    ]],
                    ['name' => 'Bowling', 'cats' => []],
                    ['name' => 'Tenpin Bowling', 'cats' => [
                        ['name' => 'Mix', 'slug' => 'mix', 'quota_mode' => 'gender_based', 'max_athletes_total' => 0, 'max_male_athletes' => 5, 'max_female_athletes' => 5, 'min_male_athletes' => 0, 'min_female_athletes' => 0, 'max_officials' => 1],
                    ]],
                    ['name' => 'Table Tennis', 'cats' => [
                        ['name' => 'Mix', 'slug' => 'table-tennis-mix', 'quota_mode' => 'gender_based', 'max_athletes_total' => 0, 'max_male_athletes' => 6, 'max_female_athletes' => 6, 'min_male_athletes' => 0, 'min_female_athletes' => 0, 'max_officials' => 1],
                    ]],
                    ['name' => 'Rugby', 'cats' => [
                        ['name' => 'Men\'s', 'slug' => 'rugby-men-s', 'quota_mode' => 'gender_based', 'max_athletes_total' => 0, 'max_male_athletes' => 16, 'max_female_athletes' => 0, 'min_male_athletes' => 0, 'min_female_athletes' => 0, 'max_officials' => 1],
                    ]],
                    ['name' => 'Sepak Takraw', 'cats' => [
                        ['name' => 'Team', 'slug' => 'sepak-takraw-team', 'quota_mode' => 'gender_based', 'max_athletes_total' => 0, 'max_male_athletes' => 12, 'max_female_athletes' => 0, 'min_male_athletes' => 0, 'min_female_athletes' => 0, 'max_officials' => 1],
                    ]],
                    ['name' => 'Indoor Rowing', 'cats' => [
                        ['name' => 'Men\'s', 'slug' => 'indoor-rowing-men-s', 'quota_mode' => 'gender_based', 'max_athletes_total' => 0, 'max_male_athletes' => 6, 'max_female_athletes' => 0, 'min_male_athletes' => 0, 'min_female_athletes' => 0, 'max_officials' => 1],
                        ['name' => 'Women\'s', 'slug' => 'indoor-rowing-women-s', 'quota_mode' => 'gender_based', 'max_athletes_total' => 0, 'max_male_athletes' => 0, 'max_female_athletes' => 6, 'min_male_athletes' => 0, 'min_female_athletes' => 0, 'max_officials' => 1],
                    ]],
                    ['name' => 'Lawn Bowls', 'cats' => [
                        ['name' => 'Mix', 'slug' => 'lawn-bowls-mix', 'quota_mode' => 'gender_based', 'max_athletes_total' => 0, 'max_male_athletes' => 3, 'max_female_athletes' => 2, 'min_male_athletes' => 0, 'min_female_athletes' => 0, 'max_officials' => 1],
                    ]],
                ],
            ],
        ];

        $allEvents = [];
        foreach (['fasa-1', 'fasa-2'] as $phase) {
            $cfg = $sportsConfig[$phase];
            $tournament = $cfg['tournament'];
            $sportIds = [];

            foreach ($cfg['sports'] as $sData) {
                $slug = Str::slug($sData['name']);
                $sport = Sport::withTrashed()->firstOrCreate(
                    ['slug' => $slug, 'organization_id' => $orgId],
                    ['organization_id' => $orgId, 'name' => $sData['name'], 'slug' => $slug, 'is_active' => true]
                );
                if ($sport->trashed()) {
                    $sport->restore();
                }
                $sportIds[] = $sport->id;

                foreach ($sData['cats'] as $catData) {
                    $catName = is_array($catData) ? $catData['name'] : $catData;
                    $catSlug = is_array($catData) ? ($catData['slug'] ?? Str::slug($catName)) : Str::slug($catName);
                    $catAttributes = array_merge([
                        'organization_id' => $orgId,
                        'sport_id' => $sport->id,
                        'name' => $catName,
                        'slug' => $catSlug,
                        'quota_mode' => 'gender_based',
                        'max_athletes_total' => null,
                        'max_male_athletes' => null,
                        'max_female_athletes' => null,
                        'min_male_athletes' => null,
                        'min_female_athletes' => null,
                        'max_officials' => null,
                    ], is_array($catData) ? $catData : []);

                    $cat = SportCategory::withTrashed()->updateOrCreate(
                        ['sport_id' => $sport->id, 'slug' => $catSlug],
                        $catAttributes
                    );
                    if ($cat->trashed()) {
                        $cat->restore();
                    }

                    $eventName = $sData['name'].' ('.$catName.')';
                    $eventSlug = Str::startsWith($catSlug, $slug.'-') ? $catSlug : $slug.'-'.$catSlug;
                    $event = Event::withTrashed()->firstOrCreate(
                        ['slug' => $eventSlug, 'tournament_id' => $tournament->id],
                        [
                            'organization_id' => $orgId,
                            'tournament_id' => $tournament->id,
                            'sport_id' => $sport->id,
                            'sport_category_id' => $cat->id,
                            'name' => $eventName,
                            'slug' => $eventSlug,
                            'start_date' => $tournament->start_date,
                            'end_date' => $tournament->end_date,
                            'is_active' => true,
                        ]
                    );
                    if ($event->trashed()) {
                        $event->restore();
                    }
                    $allEvents[] = $event;
                }
            }
            $tournament->sports()->syncWithoutDetaching($sportIds);
        }

        // Faculties
        $faculties = [
            ['short' => 'FTKEK', 'full' => 'Faculty of Electronics and Computer Technology and Engineering'],
            ['short' => 'FTKE',  'full' => 'Faculty of Electrical Technology and Engineering'],
            ['short' => 'FTKM',  'full' => 'Faculty of Mechanical Technology and Engineering'],
            ['short' => 'FTKIP', 'full' => 'Faculty of Industrial and Manufacturing Technology and Engineering'],
            ['short' => 'FTMK',  'full' => 'Faculty of Information And Communications Technology'],
            ['short' => 'FPTT',  'full' => 'Faculty of Technology Management And Technopreneurship'],
            ['short' => 'FAIX',  'full' => 'Faculty of Artificial Intelligence and Cyber Security'],
            ['short' => 'STEP',  'full' => 'School of Technical Foundation and Diploma Studies'],
        ];

        $allEps = [];
        foreach ($faculties as $f) {
            $slug = Str::slug($f['short']);
            $participant = Participant::withTrashed()->firstOrCreate(
                ['slug' => $slug, 'organization_id' => $orgId],
                [
                    'organization_id' => $orgId,
                    'session_id' => $session->id,
                    'name' => $f['short'],
                    'slug' => $slug,
                    'participant_type' => 'team',
                    'team_name' => $f['full'],
                    'status' => 'confirmed',
                    'is_active' => true,
                ]
            );
            if ($participant->trashed()) {
                $participant->restore();
            }

            // Register to both tournaments
            foreach ([$fasa1, $fasa2] as $t) {
                Registration::firstOrCreate(
                    ['tournament_id' => $t->id, 'participant_id' => $participant->id],
                    [
                        'organization_id' => $orgId,
                        'tournament_id' => $t->id,
                        'participant_id' => $participant->id,
                        'status' => 'confirmed',
                        'registered_at' => now(),
                    ]
                );
            }

            // Faculty rep user
            $repEmail = Str::lower($f['short']).'@utem.edu.my';
            $repUser = User::firstOrCreate(
                ['email' => $repEmail],
                [
                    'name' => $f['short'],
                    'email' => $repEmail,
                    'password' => bcrypt('password'),
                    'organization_id' => $orgId,
                    'participant_id' => $participant->id,
                ]
            );
            $repUser->assignRole($facRepRole);

            // Dean user
            $deanEmail = 'dean@'.Str::lower($f['short']).'.utem.edu.my';
            $deanUser = User::firstOrCreate(
                ['email' => $deanEmail],
                [
                    'name' => 'Dean '.$f['short'],
                    'email' => $deanEmail,
                    'password' => bcrypt('password'),
                    'organization_id' => $orgId,
                    'participant_id' => $participant->id,
                ]
            );
            $deanUser->assignRole($deanRole);

            // Register to all events
            foreach ($allEvents as $event) {
                EventParticipant::firstOrCreate(
                    ['event_id' => $event->id, 'participant_id' => $participant->id],
                    [
                        'organization_id' => $orgId,
                        'event_id' => $event->id,
                        'participant_id' => $participant->id,
                        'registration_date' => now(),
                        'status' => 'confirmed',
                    ]
                );
            }
        }

        // Hockey (Men's) pools + round-robin fixtures (mirrors current system state)
        $this->createHockeyPoolsAndFixtures($orgId, $fasa1, $faculties);
    }

    private function createHockeyPoolsAndFixtures(string $orgId, Tournament $tournament, array $faculties): void
    {
        $hockeyEvent = Event::where('slug', 'hockey-men-s')
            ->where('tournament_id', $tournament->id)
            ->first();

        if (! $hockeyEvent) {
            return;
        }

        $shortToSlug = fn (string $short) => Str::slug($short);

        $groupA = ['FTKEK', 'FTKM', 'FTKIP', 'FTMK'];
        $groupB = ['FTKE', 'FPTT', 'FAIX', 'STEP'];

        $pools = [];
        foreach ([$groupA, $groupB] as $index => $group) {
            $pool = Pool::withTrashed()->firstOrCreate(
                ['event_id' => $hockeyEvent->id, 'name' => $index === 0 ? 'Group A' : 'Group B'],
                [
                    'organization_id' => $orgId,
                    'event_id' => $hockeyEvent->id,
                    'name' => $index === 0 ? 'Group A' : 'Group B',
                    'sort_order' => $index,
                ]
            );
            if ($pool->trashed()) {
                $pool->restore();
            }
            $pools[] = $pool;

            foreach ($group as $short) {
                $participant = Participant::where('organization_id', $orgId)
                    ->where('slug', $shortToSlug($short))
                    ->first();

                if (! $participant) {
                    continue;
                }

                EventParticipant::where('event_id', $hockeyEvent->id)
                    ->where('participant_id', $participant->id)
                    ->update(['pool_id' => $pool->id]);
            }
        }

        $bySlug = fn (string $short) => Participant::where('organization_id', $orgId)
            ->where('slug', $shortToSlug($short))
            ->value('id');

        $fixtures = [
            ['match_number' => 1, 'pool' => 0, 'home' => 'FTKEK', 'away' => 'FTMK'],
            ['match_number' => 2, 'pool' => 0, 'home' => 'FTKM', 'away' => 'FTKIP'],
            ['match_number' => 3, 'pool' => 0, 'home' => 'FTKEK', 'away' => 'FTKIP'],
            ['match_number' => 4, 'pool' => 0, 'home' => 'FTMK', 'away' => 'FTKM'],
            ['match_number' => 5, 'pool' => 0, 'home' => 'FTKEK', 'away' => 'FTKM'],
            ['match_number' => 6, 'pool' => 0, 'home' => 'FTKIP', 'away' => 'FTMK'],
            ['match_number' => 7, 'pool' => 1, 'home' => 'FTKE', 'away' => 'STEP'],
            ['match_number' => 8, 'pool' => 1, 'home' => 'FPTT', 'away' => 'FAIX'],
            ['match_number' => 9, 'pool' => 1, 'home' => 'FTKE', 'away' => 'FAIX'],
            ['match_number' => 10, 'pool' => 1, 'home' => 'STEP', 'away' => 'FPTT'],
            ['match_number' => 11, 'pool' => 1, 'home' => 'FTKE', 'away' => 'FPTT'],
            ['match_number' => 12, 'pool' => 1, 'home' => 'FAIX', 'away' => 'STEP'],
        ];

        foreach ($fixtures as $data) {
            Fixture::withTrashed()->firstOrCreate(
                ['event_id' => $hockeyEvent->id, 'match_number' => $data['match_number']],
                [
                    'organization_id' => $orgId,
                    'event_id' => $hockeyEvent->id,
                    'match_number' => $data['match_number'],
                    'pool_id' => $pools[$data['pool']]->id,
                    'home_participant_id' => $bySlug($data['home']),
                    'away_participant_id' => $bySlug($data['away']),
                    'status' => 'scheduled',
                ]
            );
        }
    }
}
