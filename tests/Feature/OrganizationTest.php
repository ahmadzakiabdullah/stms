<?php

namespace Tests\Feature;

use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class OrganizationTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    public function test_super_admin_can_create_organization(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->actingAs($user)->post(route('organizations.store'), [
            'name' => 'Test University',
            'slug' => 'test-university',
            'organization_type' => 'university',
        ]);

        $response->assertRedirect(route('organizations.index'));

        $this->assertDatabaseHas('organizations', [
            'name' => 'Test University',
            'slug' => 'test-university',
            'organization_type' => 'university',
        ]);
    }

    public function test_organization_requires_valid_type(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->actingAs($user)->post(route('organizations.store'), [
            'name' => 'Bad Org',
            'slug' => 'bad-org',
            'organization_type' => 'invalid-type',
        ]);

        $response->assertSessionHasErrors('organization_type');
    }

    public function test_non_super_admin_cannot_enumerate_organizations_through_the_index_route(): void
    {
        $orgA = Organization::factory()->create(['slug' => 'org-a-'.uniqid()]);
        $orgB = Organization::factory()->create(['slug' => 'org-b-'.uniqid()]);

        $userA = $this->createStaffUser($orgA);

        $response = $this->actingAs($userA)->get(route('organizations.index'));

        $response->assertForbidden();
        $response->assertDontSee($orgA->name);
        $response->assertDontSee($orgB->name);
    }

    public function test_super_admin_can_update_and_delete_any_organization(): void
    {
        $org = Organization::factory()->create();
        $super = $this->createSuperAdmin();

        $response = $this->actingAs($super)->put(route('organizations.update', $org), [
            'name' => 'Updated by Super',
            'slug' => $org->slug,
            'organization_type' => $org->organization_type,
        ]);

        $response->assertRedirect(route('organizations.index'));

        $response = $this->actingAs($super)->delete(route('organizations.destroy', $org));
        $response->assertRedirect(route('organizations.index'));
    }
}
