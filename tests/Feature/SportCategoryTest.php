<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Sport;
use App\Models\SportCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class SportCategoryTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    public function test_non_super_admin_only_sees_own_organization_categories_via_global_scope(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $sportA = Sport::factory()->create(['organization_id' => $orgA->id]);
        $sportB = Sport::factory()->create(['organization_id' => $orgB->id]);

        $catA = SportCategory::factory()->create(['organization_id' => $orgA->id, 'sport_id' => $sportA->id, 'name' => 'Category A']);
        $catB = SportCategory::factory()->create(['organization_id' => $orgB->id, 'sport_id' => $sportB->id, 'name' => 'Category B']);

        $userA = $this->createOrgAdmin($orgA);

        $response = $this->actingAs($userA)->get(route('sport-categories.index'));
        $response->assertOk();
        $categories = $response->viewData('page')['props']['categories']['data'] ?? [];
        $names = collect($categories)->pluck('name');

        $this->assertTrue($names->contains($catA->name));
        $this->assertFalse($names->contains($catB->name));
    }

    public function test_super_admin_can_create_sport_category(): void
    {
        $org = Organization::factory()->create();
        $sport = Sport::factory()->create(['organization_id' => $org->id]);
        $super = $this->createSuperAdmin();

        $response = $this->actingAs($super)->post(route('sport-categories.store'), [
            'sport_id' => $sport->id,
            'name' => 'New Category',
            'slug' => 'new-category',
        ]);

        $response->assertRedirect(route('sport-categories.index'));
        $this->assertDatabaseHas('sport_categories', ['name' => 'New Category']);
    }

    public function test_org_admin_can_update_own_category(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        $sport = Sport::factory()->create(['organization_id' => $org->id]);
        $category = SportCategory::factory()->create(['organization_id' => $org->id, 'sport_id' => $sport->id]);

        $response = $this->actingAs($admin)->put(route('sport-categories.update', $category), [
            'sport_id' => $sport->id,
            'name' => 'Updated Category',
            'slug' => $category->slug,
        ]);

        $response->assertRedirect(route('sport-categories.index'));
        $this->assertDatabaseHas('sport_categories', ['name' => 'Updated Category']);
    }

    public function test_non_super_admin_cannot_update_category_in_other_org(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $adminA = $this->createOrgAdmin($orgA);

        $sportB = Sport::factory()->create(['organization_id' => $orgB->id]);
        $catB = SportCategory::factory()->create(['organization_id' => $orgB->id, 'sport_id' => $sportB->id]);

        $response = $this->actingAs($adminA)->put(route('sport-categories.update', $catB), [
            'name' => 'Hacked',
            'slug' => 'hacked',
        ]);

        $response->assertNotFound();
    }

    public function test_org_admin_can_delete_own_category(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        $sport = Sport::factory()->create(['organization_id' => $org->id]);
        $category = SportCategory::factory()->create(['organization_id' => $org->id, 'sport_id' => $sport->id]);

        $response = $this->actingAs($admin)->delete(route('sport-categories.destroy', $category));
        $response->assertRedirect(route('sport-categories.index'));
        $this->assertSoftDeleted('sport_categories', ['id' => $category->id]);
    }
}
