<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Sport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class SportTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    public function test_non_super_admin_only_sees_own_organization_sports_via_global_scope(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $sportA = Sport::factory()->create(['organization_id' => $orgA->id, 'name' => 'Football A']);
        $sportB = Sport::factory()->create(['organization_id' => $orgB->id, 'name' => 'Football B']);

        $userA = $this->createStaffUser($orgA);

        $response = $this->actingAs($userA)->get(route('sports.index'));

        $response->assertOk();
        $sports = $response->viewData('page')['props']['sports']['data'] ?? [];

        // With global scope, should only see orgA's sports
        $names = collect($sports)->pluck('name')->toArray();
        $this->assertContains('Football A', $names);
        $this->assertNotContains('Football B', $names);
    }

    public function test_super_admin_sees_all_sports(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        Sport::factory()->create(['organization_id' => $orgA->id]);
        Sport::factory()->create(['organization_id' => $orgB->id]);

        $super = $this->createSuperAdmin();

        $response = $this->actingAs($super)->get(route('sports.index'));

        $response->assertOk();
        $sports = $response->viewData('page')['props']['sports']['data'] ?? [];
        $this->assertGreaterThanOrEqual(2, count($sports));
    }

    public function test_non_super_admin_cannot_update_or_delete_sport_in_other_org(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $userA = $this->createStaffUser($orgA);

        $sportB = Sport::factory()->create(['organization_id' => $orgB->id]);

        $response = $this->actingAs($userA)->put(route('sports.update', $sportB), [
            'name' => 'Hacked',
            'slug' => 'hacked',
        ]);

        $response->assertNotFound();

        $response = $this->actingAs($userA)->delete(route('sports.destroy', $sportB));
        $response->assertNotFound();
    }

    public function test_org_admin_can_update_and_delete_own_sport(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        $sport = Sport::factory()->create(['organization_id' => $org->id]);

        $response = $this->actingAs($admin)->put(route('sports.update', $sport), [
            'name' => 'Updated Sport',
            'slug' => $sport->slug,
        ]);

        $response->assertRedirect(route('sports.index'));
        $this->assertDatabaseHas('sports', ['name' => 'Updated Sport']);

        $response = $this->actingAs($admin)->delete(route('sports.destroy', $sport));
        $response->assertRedirect(route('sports.index'));
        $this->assertSoftDeleted('sports', ['id' => $sport->id]);
    }

    public function test_duplicate_slug_rejected_within_same_org(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        Sport::factory()->create(['organization_id' => $org->id, 'slug' => 'same-slug']);

        $response = $this->actingAs($admin)->post(route('sports.store'), [
            'name' => 'Another Sport',
            'slug' => 'same-slug',
        ]);
        $response->assertSessionHasErrors('slug');
    }

    public function test_duplicate_name_rejected_within_same_org(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        Sport::factory()->create(['organization_id' => $org->id, 'name' => 'Basketball', 'slug' => 'basketball']);

        $response = $this->actingAs($admin)->post(route('sports.store'), [
            'name' => 'Basketball',
            'slug' => 'basketball-2',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_duplicate_slug_allowed_across_orgs(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        Sport::factory()->create(['organization_id' => $orgA->id, 'slug' => 'same-slug']);
        $adminB = $this->createOrgAdmin($orgB);

        $response = $this->actingAs($adminB)->post(route('sports.store'), [
            'name' => 'Same Slug Sport',
            'slug' => 'same-slug',
        ]);
        $this->assertContains($response->status(), [302, 201]);
    }
}
