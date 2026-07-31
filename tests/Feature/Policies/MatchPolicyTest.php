<?php

namespace Tests\Feature\Policies;

use App\Models\Event;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Sport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class MatchPolicyTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    public function test_super_admin_can_manage_matches(): void
    {
        $user = $this->createSuperAdmin();
        $match = Fixture::factory()->create();

        $this->assertTrue($user->can('viewAny', Fixture::class));
        $this->assertTrue($user->can('create', Fixture::class));
        $this->assertTrue($user->can('view', $match));
        $this->assertTrue($user->can('update', $match));
        $this->assertTrue($user->can('delete', $match));
    }

    public function test_org_admin_can_manage_matches(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createOrgAdmin($org);
        $match = Fixture::factory()->create(['organization_id' => $org->id]);

        $this->assertTrue($user->can('viewAny', Fixture::class));
        $this->assertTrue($user->can('create', Fixture::class));
        $this->assertTrue($user->can('view', $match));
        $this->assertTrue($user->can('update', $match));
        $this->assertTrue($user->can('delete', $match));
    }

    public function test_staff_user_without_permission_cannot_manage_matches(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createStaffUser($org);
        $match = Fixture::factory()->create(['organization_id' => $org->id]);

        $this->assertFalse($user->can('viewAny', Fixture::class));
        $this->assertFalse($user->can('create', Fixture::class));
        $this->assertTrue($user->can('view', $match));
        $this->assertFalse($user->can('update', $match));
        $this->assertFalse($user->can('delete', $match));
    }

    public function test_staff_user_with_permission_can_manage_matches(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createStaffUser($org);
        $user->givePermissionTo('manage_matches');
        $match = Fixture::factory()->create(['organization_id' => $org->id]);

        $this->assertTrue($user->can('viewAny', Fixture::class));
        $this->assertTrue($user->can('create', Fixture::class));
        $this->assertTrue($user->can('view', $match));
        $this->assertTrue($user->can('update', $match));
        $this->assertTrue($user->can('delete', $match));
    }

    public function test_org_admin_cannot_manage_matches_in_other_organization(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $user = $this->createOrgAdmin($orgA);
        $match = Fixture::factory()->create(['organization_id' => $orgB->id]);

        $this->assertFalse($user->can('view', $match));
        $this->assertFalse($user->can('update', $match));
        $this->assertFalse($user->can('delete', $match));
    }

    public function test_admin_sport_can_manage_matches_for_assigned_sport_only(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createAdminSport($org);

        $hockey = Sport::factory()->create(['organization_id' => $org->id, 'slug' => 'hockey']);
        $football = Sport::factory()->create(['organization_id' => $org->id, 'slug' => 'football']);
        $user->sports()->attach($hockey->id, ['organization_id' => $org->id]);

        $hockeyEvent = Event::factory()->create(['organization_id' => $org->id, 'sport_id' => $hockey->id]);
        $footballEvent = Event::factory()->create(['organization_id' => $org->id, 'sport_id' => $football->id]);

        $hockeyMatch = Fixture::factory()->create(['organization_id' => $org->id, 'event_id' => $hockeyEvent->id]);
        $footballMatch = Fixture::factory()->create(['organization_id' => $org->id, 'event_id' => $footballEvent->id]);

        $this->assertTrue($user->can('viewAny', Fixture::class));
        $this->assertTrue($user->can('create', [Fixture::class, $hockey->id]));
        $this->assertFalse($user->can('create', [Fixture::class, $football->id]));
        $this->assertTrue($user->can('update', $hockeyMatch));
        $this->assertFalse($user->can('update', $footballMatch));
        $this->assertTrue($user->can('delete', $hockeyMatch));
        $this->assertFalse($user->can('delete', $footballMatch));
    }

    public function test_admin_sport_without_sports_assignment_cannot_manage_matches(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createAdminSport($org);
        $match = Fixture::factory()->create(['organization_id' => $org->id]);

        $this->assertTrue($user->can('viewAny', Fixture::class));
        $this->assertFalse($user->can('create', [Fixture::class, '00000000-0000-0000-0000-000000000000']));
        $this->assertFalse($user->can('update', $match));
        $this->assertFalse($user->can('delete', $match));
    }
}
