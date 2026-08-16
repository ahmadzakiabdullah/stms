<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Participant;
use App\Models\Session;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class SAF2026ParticipantsUsersSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production') && ! config('app.allow_demo_seeding')) {
            throw new \RuntimeException(
                'This selective SAF data load is disabled in production. Set ALLOW_DEMO_SEEDING=true only for an approved load.'
            );
        }

        $organization = Organization::withTrashed()->firstOrCreate(
            ['slug' => 'utem'],
            ['name' => 'Universiti Teknikal Malaysia Melaka', 'organization_type' => 'university', 'is_active' => true]
        );
        $organization->restore();

        $session = Session::withTrashed()->firstOrCreate(
            ['organization_id' => $organization->id, 'slug' => 'saf-2026'],
            [
                'organization_id' => $organization->id,
                'name' => 'Sukan Antara Fakulti 2026',
                'description' => 'Sukan Antara Fakulti 2026 - Universiti Teknikal Malaysia Melaka',
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-30',
                'is_active' => true,
            ]
        );
        $session->restore();

        $facultyRole = Role::firstOrCreate(['name' => 'faculty-representative', 'guard_name' => 'web']);
        $deanRole = Role::firstOrCreate(['name' => 'dean', 'guard_name' => 'web']);
        $faculties = [
            ['short' => 'FTKEK', 'full' => 'Faculty of Electronics and Computer Technology and Engineering'],
            ['short' => 'FTKE', 'full' => 'Faculty of Electrical Technology and Engineering'],
            ['short' => 'FTKM', 'full' => 'Faculty of Mechanical Technology and Engineering'],
            ['short' => 'FTKIP', 'full' => 'Faculty of Industrial and Manufacturing Technology and Engineering'],
            ['short' => 'FTMK', 'full' => 'Faculty of Information And Communications Technology'],
            ['short' => 'FPTT', 'full' => 'Faculty of Technology Management And Technopreneurship'],
            ['short' => 'FAIX', 'full' => 'Faculty of Artificial Intelligence and Cyber Security'],
            ['short' => 'STEP', 'full' => 'School of Technical Foundation and Diploma Studies'],
        ];

        foreach ($faculties as $faculty) {
            $slug = Str::slug($faculty['short']);
            $participant = Participant::withTrashed()->firstOrCreate(
                ['organization_id' => $organization->id, 'slug' => $slug],
                [
                    'organization_id' => $organization->id,
                    'session_id' => $session->id,
                    'name' => $faculty['short'],
                    'slug' => $slug,
                    'participant_type' => 'team',
                    'team_name' => $faculty['full'],
                    'status' => 'confirmed',
                    'is_active' => true,
                ]
            );
            $participant->restore();

            $this->createUser($faculty['short'], $facultyRole, $organization, $participant);
            $this->createUser('Dean '.$faculty['short'], $deanRole, $organization, $participant, true);
        }

        $this->command?->info('Selective SAF participants and faculty/dean users seeded. No events, registrations, squads, fixtures, or results were created.');
    }

    private function createUser(string $name, Role $role, Organization $organization, Participant $participant, bool $dean = false): void
    {
        $short = $participant->name;
        $email = $dean ? 'dean@'.Str::lower($short).'.utem.edu.my' : Str::lower($short).'@utem.edu.my';
        $user = User::withTrashed()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'email' => $email,
                'password' => bcrypt('password'),
                'organization_id' => $organization->id,
                'participant_id' => $participant->id,
            ]
        );
        $user->restore();
        $user->assignRole($role);
    }
}
