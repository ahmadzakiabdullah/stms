<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Session;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database for M1 (Organization + RBAC bootstrap).
     */
    public function run(): void
    {
        // 1. Create the UTeM organization
        $defaultOrg = Organization::withTrashed()->firstOrCreate(
            ['slug' => 'utem'],
            [
                'name' => 'Universiti Teknikal Malaysia Melaka',
                'slug' => 'utem',
                'organization_type' => 'university',
                'is_active' => true,
            ]
        );

        if ($defaultOrg->trashed()) {
            $defaultOrg->restore();
        }

        // 2. Create roles (requires permission tables from migration)
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'org-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'faculty-representative', 'guard_name' => 'web']);

        // Create comprehensive granular permissions for MVP modules
        $permissions = [
            // Organizations
            'view organizations', 'create organizations', 'edit organizations', 'delete organizations',
            // Users
            'view users', 'create users', 'edit users', 'delete users',
            // Sports & Categories
            'view sports', 'create sports', 'edit sports', 'delete sports',
            'view sport categories', 'create sport categories', 'edit sport categories', 'delete sport categories',
            // Sessions
            'view sessions', 'create sessions', 'edit sessions', 'delete sessions',
            // Tournaments & Events
            'view tournaments', 'create tournaments', 'edit tournaments', 'delete tournaments',
            'view events', 'create events', 'edit events', 'delete events',
            // Participants & Registrations (M3)
            'view participants', 'create participants', 'edit participants', 'delete participants',
            'view registrations', 'create registrations', 'edit registrations', 'delete registrations',
            // Matches & Results (M4)
            'manage_matches',
            'manage_results',
            // Event Participants
            'view event participants', 'create event participants', 'edit event participants', 'delete event participants',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $superAdmin->givePermissionTo($permissions);

        // Assign relevant permissions to wakil-fakulti (can view & register their faculty to events)
        $wakilFakulti = Role::where('name', 'faculty-representative')->first();
        if ($wakilFakulti) {
            $wakilFakulti->givePermissionTo([
                'view event participants',
                'create event participants',
                'edit event participants',
                'delete event participants',
            ]);
        }

        // 3. Default super-admin bootstrap (deployment-specific).
        // The email below is promoted to super-admin for the initial org if the account already exists in the DB.
        // Override via .env or edit seeder for different deployments. This keeps the platform generic.
        $defaultAdmin = User::withTrashed()->firstOrCreate(
            ['email' => 'ahmadzaki@utem.edu.my'],
            [
                'name' => 'Ahmad Zaki',
                'email' => 'ahmadzaki@utem.edu.my',
                'password' => bcrypt('password'),
                'organization_id' => $defaultOrg->id,
            ]
        );
        if ($defaultAdmin->trashed()) {
            $defaultAdmin->restore();
        }
        if (empty($defaultAdmin->organization_id)) {
            $defaultAdmin->organization_id = $defaultOrg->id;
            $defaultAdmin->save();
        }
        $defaultAdmin->assignRole($superAdmin);

        // 4. Test / fallback super-admin account (admin@saf.test) - password: password
        // Useful for local/dev testing. Remove or change password in production seeders.
        $testAdmin = User::withTrashed()->firstOrCreate(
            ['email' => 'admin@saf.test'],
            [
                'name' => 'Super Admin SAF',
                'email' => 'admin@saf.test',
                'password' => bcrypt('password'),
                'organization_id' => $defaultOrg->id,
            ]
        );
        if ($testAdmin->trashed()) {
            $testAdmin->restore();
        }
        $testAdmin->assignRole($superAdmin);

        // 5. Basic test user (no admin role)
        $testUser = User::withTrashed()->firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
                'organization_id' => $defaultOrg->id,
            ]
        );
        if ($testUser->trashed()) {
            $testUser->restore();
        }

        // 6. Seed the SAF 2026 sport master list for the default organization (M2)
        // Categories and event-specific quotas are configured by SAF2026DataSeeder below.
        $defaultSports = [
            ['name' => 'Aerobics', 'slug' => 'aerobics'],
            ['name' => 'Archery', 'slug' => 'archery'],
            ['name' => 'Badminton', 'slug' => 'badminton'],
            ['name' => 'Basketball', 'slug' => 'basketball'],
            ['name' => 'Bowling', 'slug' => 'bowling'],
            ['name' => 'Chess', 'slug' => 'chess'],
            ['name' => 'Cycling', 'slug' => 'cycling'],
            ['name' => 'E-Sport (Mobile Legends)', 'slug' => 'e-sport-mobile-legends'],
            ['name' => 'E-Sport (Valorant)', 'slug' => 'e-sport-valorant'],
            ['name' => 'Football', 'slug' => 'football'],
            ['name' => 'Futsal', 'slug' => 'futsal'],
            ['name' => 'Handball', 'slug' => 'handball'],
            ['name' => 'Hockey', 'slug' => 'hockey'],
            ['name' => 'Indoor Rowing', 'slug' => 'indoor-rowing'],
            ['name' => 'Lawn Bowls', 'slug' => 'lawn-bowls'],
            ['name' => 'Netball', 'slug' => 'netball'],
            ['name' => 'Petanque', 'slug' => 'petanque'],
            ['name' => 'Rugby', 'slug' => 'rugby'],
            ['name' => 'Sepak Takraw', 'slug' => 'sepak-takraw'],
            ['name' => 'Softball', 'slug' => 'softball'],
            ['name' => 'Table Tennis', 'slug' => 'table-tennis'],
            ['name' => 'Tenpin Bowling', 'slug' => 'tenpin-bowling'],
            ['name' => 'Tennis', 'slug' => 'tennis'],
            ['name' => 'Volleyball', 'slug' => 'volleyball'],
        ];

        foreach ($defaultSports as $sportData) {
            $sport = Sport::withTrashed()->firstOrCreate(
                ['slug' => $sportData['slug'], 'organization_id' => $defaultOrg->id],
                [
                    'organization_id' => $defaultOrg->id,
                    'name' => $sportData['name'],
                    'slug' => $sportData['slug'],
                    'is_active' => true,
                ]
            );
            if ($sport->trashed()) {
                $sport->restore();
            }
        }

        // 7. Sport categories are seeded by SAF2026DataSeeder below.
        // Keep this empty to avoid mixing old example categories with the production SAF category set.
        $defaultCategories = [];

        foreach ($defaultCategories as $sportSlug => $categoryNames) {
            $sport = Sport::where('slug', $sportSlug)
                ->where('organization_id', $defaultOrg->id)
                ->first();

            if ($sport) {
                foreach ($categoryNames as $catName) {
                    $catSlug = Str::slug($catName);
                    SportCategory::withTrashed()->firstOrCreate(
                        ['sport_id' => $sport->id, 'slug' => $catSlug],
                        [
                            'organization_id' => $defaultOrg->id,
                            'sport_id' => $sport->id,
                            'name' => $catName,
                            'slug' => $catSlug,
                        ]
                    );
                }
            }
        }

        // 8. Seed example sessions for the default organization (M2)
        // Note: Uses withTrashed + firstOrCreate for safety on re-seed
        $defaultSessions = [
            [
                'name' => 'SUKMA 2026',
                'slug' => 'sukma-2026',
                'description' => 'Sukan Malaysia 2026',
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-15',
                'is_active' => true,
            ],
            [
                'name' => 'SUKIPT 2025',
                'slug' => 'sukipt-2025',
                'description' => 'Sukan Universiti 2025',
                'start_date' => '2025-09-01',
                'end_date' => '2025-09-10',
                'is_active' => true,
            ],
            [
                'name' => 'SUKMA 2024 (Contoh)',
                'slug' => 'sukma-2024',
                'description' => 'Contoh session lepas untuk testing',
                'start_date' => '2024-05-01',
                'end_date' => '2024-05-15',
                'is_active' => true,
            ],
        ];

        foreach ($defaultSessions as $sessionData) {
            Session::withTrashed()->updateOrCreate(
                ['slug' => $sessionData['slug'], 'organization_id' => $defaultOrg->id],
                array_merge($sessionData, ['organization_id' => $defaultOrg->id])
            );
        }

        // 9. Seed basic tournaments under the sessions (M2)
        // This shows: one Session can have multiple Tournaments.
        // A Tournament belongs to one Session and one Organization.
        // A Tournament can include multiple Sports (many-to-many).
        $defaultTournaments = [
            'sukma-2026' => [
                ['name' => 'Men\'s Football', 'slug' => 'mens-football', 'description' => 'Football tournament for men'],
                ['name' => 'Women\'s Badminton', 'slug' => 'womens-badminton', 'description' => 'Badminton for women'],
            ],
            'sukipt-2025' => [
                ['name' => 'Futsal Open', 'slug' => 'futsal-open', 'description' => 'Open futsal competition'],
            ],
            'sukma-2024' => [
                ['name' => 'Volleyball', 'slug' => 'volleyball', 'description' => 'Volleyball event'],
            ],
        ];

        foreach ($defaultTournaments as $sessionSlug => $tournaments) {
            $session = Session::where('slug', $sessionSlug)
                ->where('organization_id', $defaultOrg->id)
                ->first();

            if ($session) {
                foreach ($tournaments as $tData) {
                    $tournament = Tournament::withTrashed()->updateOrCreate(
                        ['slug' => $tData['slug'], 'session_id' => $session->id],
                        [
                            'organization_id' => $defaultOrg->id,
                            'session_id' => $session->id,
                            'name' => $tData['name'],
                            'slug' => $tData['slug'],
                            'description' => $tData['description'],
                            'start_date' => $session->start_date,
                            'end_date' => $session->end_date,
                            'ranking_strategy' => 'points',
                            'is_active' => true,
                        ]
                    );

                    // Attach sports to this tournament (many-to-many relation)
                    $sportSlugs = match ($tData['slug']) {
                        'mens-football' => ['football'],
                        'womens-badminton' => ['badminton'],
                        'futsal-open' => ['futsal'],
                        'volleyball' => ['volleyball'],
                        default => [],
                    };
                    $sportIds = Sport::whereIn('slug', $sportSlugs)->pluck('id')->toArray();
                    if ($sportIds) {
                        $tournament->sports()->sync($sportIds);
                    }
                }
            }
        }

        // 10. Seed some events under tournaments (M2.3 + relations)
        // This demonstrates the core hierarchy and relationships:
        // A Tournament contains multiple Events.
        // Each Event is tied to one Sport + one SportCategory within that Tournament.
        // Example data (using real-world Malaysian multi-sport event names like SUKMA/SUKIPT as illustrative only):
        // - Organization: the tenant / organizing body (configurable via env)
        // - Session: a specific edition/cycle (e.g. SUKMA 2026)
        // - Tournament: a competition within the session
        // - Event: a specific sub-competition under a sport + category
        // The same structure supports schools, universities, national games, or international events.
        $defaultEvents = [
            'sukma-2026' => [
                'mens-football' => [
                    ['name' => 'Men\'s Football - Group A', 'slug' => 'mens-football-group-a', 'sport_slug' => 'football', 'category_slug' => 'mens-team'],
                    ['name' => 'Men\'s Football - Final', 'slug' => 'mens-football-final', 'sport_slug' => 'football', 'category_slug' => 'mens-team'],
                ],
                'womens-badminton' => [
                    ['name' => 'Women\'s Badminton - Singles', 'slug' => 'womens-badminton-singles', 'sport_slug' => 'badminton', 'category_slug' => 'womens-singles'],
                ],
            ],
            'sukipt-2025' => [
                'futsal-open' => [
                    ['name' => 'Futsal Open - Semifinal 1', 'slug' => 'futsal-open-semi1', 'sport_slug' => 'futsal', 'category_slug' => 'mens-open'],
                ],
            ],
        ];

        foreach ($defaultEvents as $sessionSlug => $tournamentEvents) {
            $session = Session::where('slug', $sessionSlug)->first();
            if (! $session) {
                continue;
            }

            foreach ($tournamentEvents as $tournamentSlug => $events) {
                $tournament = Tournament::where('slug', $tournamentSlug)->where('session_id', $session->id)->first();
                if (! $tournament) {
                    continue;
                }

                foreach ($events as $eData) {
                    $sport = Sport::where('slug', $eData['sport_slug'])->first();
                    $category = SportCategory::where('slug', $eData['category_slug'])->first();
                    if (! $sport || ! $category) {
                        continue;
                    }

                    Event::withTrashed()->updateOrCreate(
                        ['slug' => $eData['slug'], 'tournament_id' => $tournament->id],
                        [
                            'organization_id' => $defaultOrg->id,
                            'tournament_id' => $tournament->id,
                            'sport_id' => $sport->id,
                            'sport_category_id' => $category->id,
                            'name' => $eData['name'],
                            'slug' => $eData['slug'],
                            'start_date' => $tournament->start_date,
                            'end_date' => $tournament->end_date,
                            'is_active' => true,
                        ]
                    );
                }
            }
        }

        // SAF 2026 data (session, tournaments, sports, categories, events, faculties, users)
        $this->call(SAF2026DataSeeder::class);
    }
}
