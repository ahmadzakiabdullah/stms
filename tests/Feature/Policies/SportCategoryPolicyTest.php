<?php

namespace Tests\Feature\Policies;

use App\Models\Organization;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Policies\SportCategoryPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class SportCategoryPolicyTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    private SportCategoryPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new SportCategoryPolicy;
    }

    public function test_super_admin_can_perform_all_actions(): void
    {
        $super = $this->createSuperAdmin();
        $org = Organization::factory()->create();
        $sport = Sport::factory()->create(['organization_id' => $org->id]);
        $category = SportCategory::factory()->create(['organization_id' => $org->id, 'sport_id' => $sport->id]);

        $this->assertTrue($this->policy->viewAny($super));
        $this->assertTrue($this->policy->view($super, $category));
        $this->assertTrue($this->policy->create($super));
        $this->assertTrue($this->policy->update($super, $category));
        $this->assertTrue($this->policy->delete($super, $category));
    }

    public function test_org_admin_can_manage_own_org_categories(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        $sport = Sport::factory()->create(['organization_id' => $org->id]);
        $category = SportCategory::factory()->create(['organization_id' => $org->id, 'sport_id' => $sport->id]);

        $this->assertTrue($this->policy->viewAny($admin));
        $this->assertTrue($this->policy->create($admin));
        $this->assertTrue($this->policy->view($admin, $category));
        $this->assertTrue($this->policy->update($admin, $category));
        $this->assertTrue($this->policy->delete($admin, $category));
    }

    public function test_org_admin_cannot_manage_other_org_categories(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $adminA = $this->createOrgAdmin($orgA);

        $sportB = Sport::factory()->create(['organization_id' => $orgB->id]);
        $categoryB = SportCategory::factory()->create(['organization_id' => $orgB->id, 'sport_id' => $sportB->id]);

        $this->assertFalse($this->policy->view($adminA, $categoryB));
        $this->assertFalse($this->policy->update($adminA, $categoryB));
        $this->assertFalse($this->policy->delete($adminA, $categoryB));
    }

    public function test_staff_without_perms_has_limited_access(): void
    {
        $org = Organization::factory()->create();
        $staff = $this->createStaffUser($org);
        $sport = Sport::factory()->create(['organization_id' => $org->id]);
        $category = SportCategory::factory()->create(['organization_id' => $org->id, 'sport_id' => $sport->id]);

        $this->assertFalse($this->policy->viewAny($staff));
        $this->assertTrue($this->policy->view($staff, $category));
        $this->assertFalse($this->policy->create($staff));
        $this->assertFalse($this->policy->update($staff, $category));
        $this->assertFalse($this->policy->delete($staff, $category));
    }
}
