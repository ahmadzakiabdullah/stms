<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Participant;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class ManualUrlAccessMatrixTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    public function test_active_roles_cannot_bypass_index_authorization_with_manual_urls(): void
    {
        $organization = Organization::factory()->create();
        $participant = Participant::factory()->create(['organization_id' => $organization->id]);
        $sport = Sport::factory()->create(['organization_id' => $organization->id]);

        $users = [
            'super-admin' => $this->createSuperAdmin(['organization_id' => $organization->id]),
            'org-admin' => $this->createOrgAdmin($organization),
            'admin-sport' => $this->createAdminSport($organization),
            'staff' => $this->createStaffUser($organization),
            'faculty-representative' => $this->userWithRole($organization, 'faculty-representative', $participant),
            'dean' => $this->userWithRole($organization, 'dean', $participant),
        ];

        $users['admin-sport']->sports()->attach($sport->id, ['organization_id' => $organization->id]);
        Permission::firstOrCreate(['name' => 'view event participants', 'guard_name' => 'web']);
        $users['faculty-representative']->givePermissionTo('view event participants');

        $routes = [
            'organizations.index' => ['super-admin'],
            'users.index' => ['super-admin', 'org-admin'],
            'roles.index' => ['super-admin'],
            'sessions.index' => ['super-admin', 'org-admin'],
            'sports.index' => ['super-admin', 'org-admin'],
            'sport-categories.index' => ['super-admin', 'org-admin'],
            'tournaments.index' => ['super-admin', 'org-admin'],
            'events.index' => ['super-admin', 'org-admin', 'admin-sport'],
            'participants.index' => ['super-admin', 'org-admin'],
            'registrations.index' => ['super-admin', 'org-admin'],
            'event-participants.index' => ['super-admin', 'org-admin', 'faculty-representative'],
            'matches.index' => ['super-admin', 'org-admin', 'admin-sport'],
            'results.index' => ['super-admin', 'org-admin', 'admin-sport'],
            'rankings.index' => ['super-admin', 'org-admin', 'admin-sport'],
            'reports.index' => ['super-admin', 'org-admin', 'staff'],
            'settings.index' => ['super-admin', 'org-admin'],
            'activity-logs.index' => ['super-admin', 'org-admin'],
            'participation-confirmations.index' => ['super-admin', 'org-admin', 'faculty-representative', 'dean'],
            'dean.dashboard' => ['dean'],
        ];

        foreach ($users as $role => $user) {
            foreach ($routes as $routeName => $allowedRoles) {
                $response = $this->actingAs($user)->get(route($routeName));

                if (in_array($role, $allowedRoles, true)) {
                    $response->assertOk("{$role} should be allowed to access {$routeName}");
                } else {
                    $response->assertForbidden("{$role} should be forbidden from {$routeName}");
                }
            }
        }
    }

    private function userWithRole(Organization $organization, string $role, Participant $participant): User
    {
        $user = $this->createUserInOrganization($organization, ['participant_id' => $participant->id]);
        $user->assignRole(Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));

        return $user;
    }
}
