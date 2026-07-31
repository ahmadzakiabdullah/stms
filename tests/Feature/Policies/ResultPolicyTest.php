<?php

namespace Tests\Feature\Policies;

use App\Models\Event;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Result;
use App\Models\Sport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class ResultPolicyTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    public function test_super_admin_can_manage_results(): void
    {
        $user = $this->createSuperAdmin();
        $result = Result::factory()->create();

        $this->assertTrue($user->can('viewAny', Result::class));
        $this->assertTrue($user->can('create', Result::class));
        $this->assertTrue($user->can('view', $result));
        $this->assertTrue($user->can('update', $result));
        $this->assertTrue($user->can('delete', $result));
    }

    public function test_org_admin_can_manage_results(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createOrgAdmin($org);
        $result = Result::factory()->create(['organization_id' => $org->id]);

        $this->assertTrue($user->can('viewAny', Result::class));
        $this->assertTrue($user->can('create', Result::class));
        $this->assertTrue($user->can('view', $result));
        $this->assertTrue($user->can('update', $result));
        $this->assertTrue($user->can('delete', $result));
    }

    public function test_staff_user_without_permission_cannot_manage_results(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createStaffUser($org);
        $result = Result::factory()->create(['organization_id' => $org->id]);

        $this->assertFalse($user->can('viewAny', Result::class));
        $this->assertFalse($user->can('create', Result::class));
        $this->assertTrue($user->can('view', $result));
        $this->assertFalse($user->can('update', $result));
        $this->assertFalse($user->can('delete', $result));
    }

    public function test_staff_user_with_permission_can_manage_results(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createStaffUser($org);
        $user->givePermissionTo('manage_results');
        $result = Result::factory()->create(['organization_id' => $org->id]);

        $this->assertTrue($user->can('viewAny', Result::class));
        $this->assertTrue($user->can('create', Result::class));
        $this->assertTrue($user->can('view', $result));
        $this->assertTrue($user->can('update', $result));
        $this->assertTrue($user->can('delete', $result));
    }

    public function test_org_admin_cannot_manage_results_in_other_organization(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $user = $this->createOrgAdmin($orgA);
        $result = Result::factory()->create(['organization_id' => $orgB->id]);

        $this->assertFalse($user->can('view', $result));
        $this->assertFalse($user->can('update', $result));
        $this->assertFalse($user->can('delete', $result));
    }

    public function test_admin_sport_can_manage_results_for_assigned_sport_only(): void
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

        $hockeyResult = Result::factory()->create(['organization_id' => $org->id, 'match_id' => $hockeyMatch->id]);
        $footballResult = Result::factory()->create(['organization_id' => $org->id, 'match_id' => $footballMatch->id]);

        $this->assertTrue($user->can('viewAny', Result::class));
        $this->assertTrue($user->can('create', [Result::class, $hockey->id]));
        $this->assertFalse($user->can('create', [Result::class, $football->id]));
        $this->assertTrue($user->can('update', $hockeyResult));
        $this->assertFalse($user->can('update', $footballResult));
        $this->assertTrue($user->can('delete', $hockeyResult));
        $this->assertFalse($user->can('delete', $footballResult));
    }

    public function test_admin_sport_without_sports_assignment_cannot_manage_results(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createAdminSport($org);
        $result = Result::factory()->create(['organization_id' => $org->id]);

        $this->assertTrue($user->can('viewAny', Result::class));
        $this->assertFalse($user->can('create', [Result::class, '00000000-0000-0000-0000-000000000000']));
        $this->assertFalse($user->can('update', $result));
        $this->assertFalse($user->can('delete', $result));
    }
}
