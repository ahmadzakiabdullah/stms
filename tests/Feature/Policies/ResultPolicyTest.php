<?php

namespace Tests\Feature\Policies;

use App\Models\Organization;
use App\Models\Result;
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
}
