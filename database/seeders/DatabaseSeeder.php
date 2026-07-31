<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

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

        app(PermissionRegistrar::class)->forgetCachedPermissions();

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

        // Production seeding stops after tenant/RBAC bootstrap unless demo data
        // has been explicitly enabled. This prevents predictable accounts and
        // event data from being created by a routine production deployment.
        if (! config('app.seed_demo_data')) {
            $this->command?->warn('Demo users and SAF 2026 operational data were skipped.');

            return;
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

        // 8. SAF 2026 data (session, tournaments, sports, categories, events,
        //    faculties, pools, fixtures, users) - mirrors the current system state.
        $this->call(SAF2026DataSeeder::class);
    }
}
