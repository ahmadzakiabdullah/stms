<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Registration;
use App\Models\Result;
use App\Models\Session;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\Tournament;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SAF2026Seeder extends Seeder
{
    private Organization $org;

    private Session $session;

    private Tournament $tournament;

    private array $faculties = [];

    private array $sports = [];

    public function run(): void
    {
        $this->org = Organization::withTrashed()->firstOrCreate(
            ['slug' => 'default-org'],
            [
                'name' => 'Default Organization',
                'slug' => 'default-org',
                'organization_type' => 'university',
                'is_active' => true,
            ]
        );
        if ($this->org->trashed()) {
            $this->org->restore();
        }

        $this->createSession();
        $this->createTournament();
        $this->createSports();
        $this->createFacultyParticipants();
        $this->createEvents();
        $this->registerFaculties();
        $this->createMatchesAndResults();
    }

    private function createSession(): void
    {
        $this->session = Session::withTrashed()->updateOrCreate(
            ['slug' => 'saf-2026', 'organization_id' => $this->org->id],
            [
                'organization_id' => $this->org->id,
                'name' => 'SAF 2026',
                'slug' => 'saf-2026',
                'description' => 'Sukan Antara Fakulti 2026 - Universiti Teknikal Malaysia Melaka',
                'start_date' => '2026-07-01',
                'end_date' => '2026-07-14',
                'is_active' => true,
            ]
        );
        if ($this->session->trashed()) {
            $this->session->restore();
        }
    }

    private function createTournament(): void
    {
        $this->tournament = Tournament::withTrashed()->updateOrCreate(
            ['slug' => 'kejohanan-saf-2026', 'session_id' => $this->session->id],
            [
                'organization_id' => $this->org->id,
                'session_id' => $this->session->id,
                'name' => 'Kejohanan SAF 2026',
                'slug' => 'kejohanan-saf-2026',
                'description' => 'Kejohanan Sukan Antara Fakulti 2026',
                'start_date' => $this->session->start_date,
                'end_date' => $this->session->end_date,
                'ranking_strategy' => 'points',
                'is_active' => true,
            ]
        );
        if ($this->tournament->trashed()) {
            $this->tournament->restore();
        }
    }

    private function createSports(): void
    {
        $sportsData = [
            ['name' => 'Futsal', 'slug' => 'futsal', 'categories' => ['Men\'s', 'Women\'s']],
            ['name' => 'Badminton', 'slug' => 'badminton', 'categories' => ['Men\'s Team', 'Women\'s Team', 'Mixed Doubles']],
            ['name' => 'Sepak Takraw', 'slug' => 'sepak-takraw', 'categories' => ['Men\'s Regu']],
            ['name' => 'Bola Jaring', 'slug' => 'bola-jaring', 'categories' => ['Women\'s']],
            ['name' => 'Bola Keranjang', 'slug' => 'bola-keranjang', 'categories' => ['Men\'s', 'Women\'s']],
            ['name' => 'Olahraga (Trek)', 'slug' => 'olahraga-trek', 'categories' => ['Men\'s', 'Women\'s']],
            ['name' => 'Tenis Meja', 'slug' => 'tenis-meja', 'categories' => ['Men\'s Team', 'Women\'s Team']],
            ['name' => 'Bola Tampar', 'slug' => 'bola-tampar', 'categories' => ['Men\'s', 'Women\'s']],
            ['name' => 'Tenis', 'slug' => 'tenis', 'categories' => ['Men\'s Singles', 'Women\'s Singles']],
            ['name' => 'Ragbi 7\'s', 'slug' => 'ragbi', 'categories' => ['Men\'s']],
            ['name' => 'Memanah', 'slug' => 'memanah', 'categories' => ['Men\'s Individual', 'Women\'s Individual']],
            ['name' => 'Catur', 'slug' => 'catur', 'categories' => ['Open Team']],
            ['name' => 'Boling', 'slug' => 'boling', 'categories' => ['Men\'s Individual', 'Women\'s Individual']],
            ['name' => 'Hoki', 'slug' => 'hoki', 'categories' => ['Men\'s', 'Women\'s']],
            ['name' => 'Silat', 'slug' => 'silat', 'categories' => ['Men\'s', 'Women\'s']],
            ['name' => 'Skuasy', 'slug' => 'skuasy', 'categories' => ['Men\'s Singles', 'Women\'s Singles']],
            ['name' => 'Olahraga (Padang)', 'slug' => 'olahraga-padang', 'categories' => ['Men\'s', 'Women\'s']],
            ['name' => 'Merentas Desa', 'slug' => 'merentas-desa', 'categories' => ['Men\'s', 'Women\'s']],
            ['name' => 'Bola Baling', 'slug' => 'bola-baling', 'categories' => ['Men\'s', 'Women\'s']],
            ['name' => 'Kriket', 'slug' => 'kriket', 'categories' => ['Men\'s']],
        ];

        $sportIds = [];
        foreach ($sportsData as $data) {
            $sport = Sport::withTrashed()->updateOrCreate(
                ['slug' => $data['slug'], 'organization_id' => $this->org->id],
                [
                    'organization_id' => $this->org->id,
                    'name' => $data['name'],
                    'slug' => $data['slug'],
                    'is_active' => true,
                ]
            );
            if ($sport->trashed()) {
                $sport->restore();
            }

            foreach ($data['categories'] as $catName) {
                $catSlug = Str::slug($catName);
                SportCategory::withTrashed()->updateOrCreate(
                    ['sport_id' => $sport->id, 'slug' => $catSlug],
                    [
                        'organization_id' => $this->org->id,
                        'sport_id' => $sport->id,
                        'name' => $catName,
                        'slug' => $catSlug,
                    ]
                );
            }

            $sportIds[] = $sport->id;
            $this->sports[$data['slug']] = $sport;
        }

        $this->tournament->sports()->sync($sportIds);
    }

    private function createFacultyParticipants(): void
    {
        $facultyData = [
            ['name' => 'FKEKK', 'full' => 'Fakulti Kejuruteraan Elektronik dan Kejuruteraan Komputer'],
            ['name' => 'FKM', 'full' => 'Fakulti Kejuruteraan Mekanikal'],
            ['name' => 'FKEE', 'full' => 'Fakulti Kejuruteraan Elektrik'],
            ['name' => 'FKP', 'full' => 'Fakulti Kejuruteraan Pembuatan'],
            ['name' => 'FTMK', 'full' => 'Fakulti Teknologi Maklumat dan Komunikasi'],
            ['name' => 'FPTT', 'full' => 'Fakulti Pengurusan Teknologi dan Teknousahawanan'],
            ['name' => 'FTKEE', 'full' => 'Fakulti Teknologi Kejuruteraan Elektrik dan Elektronik'],
            ['name' => 'PBPI', 'full' => 'Pusat Bahasa dan Pengajian Islam'],
        ];

        foreach ($facultyData as $i => $f) {
            $faculty = Participant::withTrashed()->updateOrCreate(
                ['slug' => Str::slug($f['name']), 'organization_id' => $this->org->id],
                [
                    'organization_id' => $this->org->id,
                    'session_id' => $this->session->id,
                    'name' => $f['name'],
                    'slug' => Str::slug($f['name']),
                    'participant_type' => 'team',
                    'team_name' => $f['full'],
                    'status' => 'confirmed',
                    'is_active' => true,
                ]
            );
            if ($faculty->trashed()) {
                $faculty->restore();
            }
            $this->faculties[] = $faculty;
        }
    }

    private function createEvents(): void
    {
        $eventsConfig = [
            'futsal' => ['Men\'s', 'Women\'s'],
            'badminton' => ['Men\'s Team', 'Women\'s Team', 'Mixed Doubles'],
            'sepak-takraw' => ['Men\'s Regu'],
            'bola-jaring' => ['Women\'s'],
            'bola-keranjang' => ['Men\'s', 'Women\'s'],
            'olahraga-trek' => ['Men\'s', 'Women\'s'],
            'tenis-meja' => ['Men\'s Team', 'Women\'s Team'],
            'bola-tampar' => ['Men\'s', 'Women\'s'],
            'tenis' => ['Men\'s Singles', 'Women\'s Singles'],
            'ragbi' => ['Men\'s'],
            'memanah' => ['Men\'s Individual', 'Women\'s Individual'],
            'catur' => ['Open Team'],
            'boling' => ['Men\'s Individual', 'Women\'s Individual'],
            'hoki' => ['Men\'s', 'Women\'s'],
            'silat' => ['Men\'s', 'Women\'s'],
            'skuasy' => ['Men\'s Singles', 'Women\'s Singles'],
            'olahraga-padang' => ['Men\'s', 'Women\'s'],
            'merentas-desa' => ['Men\'s', 'Women\'s'],
            'bola-baling' => ['Men\'s', 'Women\'s'],
            'kriket' => ['Men\'s'],
        ];

        foreach ($eventsConfig as $sportSlug => $categories) {
            $sport = $this->sports[$sportSlug] ?? null;
            if (! $sport) {
                continue;
            }

            foreach ($categories as $catName) {
                $catSlug = Str::slug($catName);
                $category = SportCategory::where('sport_id', $sport->id)
                    ->where('slug', $catSlug)->first();
                if (! $category) {
                    continue;
                }

                $eventName = $sport->name.' ('.$catName.')';
                $eventSlug = $sportSlug.'-'.$catSlug;

                Event::withTrashed()->updateOrCreate(
                    ['slug' => $eventSlug, 'tournament_id' => $this->tournament->id],
                    [
                        'organization_id' => $this->org->id,
                        'tournament_id' => $this->tournament->id,
                        'sport_id' => $sport->id,
                        'sport_category_id' => $category->id,
                        'name' => $eventName,
                        'slug' => $eventSlug,
                        'description' => $eventName.' - SAF 2026',
                        'start_date' => $this->session->start_date,
                        'end_date' => $this->session->end_date,
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    private function registerFaculties(): void
    {
        foreach ($this->faculties as $faculty) {
            Registration::withTrashed()->updateOrCreate(
                ['tournament_id' => $this->tournament->id, 'participant_id' => $faculty->id],
                [
                    'organization_id' => $this->org->id,
                    'tournament_id' => $this->tournament->id,
                    'participant_id' => $faculty->id,
                    'status' => 'confirmed',
                    'registered_at' => now(),
                ]
            );

            $events = Event::where('tournament_id', $this->tournament->id)->get();
            foreach ($events as $event) {
                EventParticipant::updateOrCreate(
                    ['event_id' => $event->id, 'participant_id' => $faculty->id],
                    [
                        'event_id' => $event->id,
                        'participant_id' => $faculty->id,
                        'registration_date' => now(),
                        'status' => 'confirmed',
                    ]
                );
            }
        }
    }

    private function createMatchesAndResults(): void
    {
        $teamEvents = Event::where('tournament_id', $this->tournament->id)->get();

        $venues = [
            'Stadium UTeM', 'Dewan Utama', 'Padang A', 'Padang B',
            'Gelanggang Futsal 1', 'Gelanggang Futsal 2', 'Dewan FKEKK', 'Dewan FKM',
            'Dewan FTMK', 'Gelanggang Tenis', 'Gelanggang Skuasy', 'Padang Hoki',
            'Dewan Serbaguna', 'Tapak Memanah', 'Boling Centre', 'Litar Merentas Desa',
        ];

        foreach ($teamEvents as $event) {
            $this->generateEventMatches($event, $venues);
        }
    }

    private function generateEventMatches(Event $event, array $venues): void
    {
        $facultyIds = array_map(fn ($f) => $f->id, $this->faculties);
        $matchNumber = 0;

        for ($i = 0; $i < count($facultyIds); $i++) {
            for ($j = $i + 1; $j < count($facultyIds); $j++) {
                $matchNumber++;
                $dayOffset = rand(-7, 13);
                $scheduledAt = now()->addDays($dayOffset)->addHours(rand(8, 18));
                $isCompleted = $scheduledAt->isPast();

                $home = $facultyIds[$i];
                $away = $facultyIds[$j];

                $homeScore = $isCompleted ? rand(0, 5) : null;
                $awayScore = $isCompleted ? rand(0, 5) : null;
                $winnerId = null;

                if ($isCompleted && $homeScore !== null && $awayScore !== null) {
                    if ($homeScore > $awayScore) {
                        $winnerId = $home;
                    } elseif ($awayScore > $homeScore) {
                        $winnerId = $away;
                    }
                }

                $match = Fixture::withTrashed()->updateOrCreate(
                    ['event_id' => $event->id, 'match_number' => $matchNumber],
                    [
                        'organization_id' => $this->org->id,
                        'event_id' => $event->id,
                        'match_number' => $matchNumber,
                        'home_participant_id' => $home,
                        'away_participant_id' => $away,
                        'venue' => $venues[array_rand($venues)],
                        'scheduled_at' => $scheduledAt,
                        'status' => $isCompleted ? 'completed' : 'scheduled',
                    ]
                );

                if ($match->trashed()) {
                    $match->restore();
                }

                if ($isCompleted && $winnerId !== null) {
                    Result::withTrashed()->updateOrCreate(
                        ['match_id' => $match->id],
                        [
                            'organization_id' => $this->org->id,
                            'match_id' => $match->id,
                            'score_home' => $homeScore,
                            'score_away' => $awayScore,
                            'winner_participant_id' => $winnerId,
                        ]
                    );
                }
            }
        }
    }
}
