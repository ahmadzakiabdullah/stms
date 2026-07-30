<?php

namespace Tests\Feature\Requests;

use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class StoreSportRequestTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    public function test_passes_with_valid_data(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createOrgAdmin($org);
        $response = $this->actingAs($user)->post(route('sports.store'), [
            'name' => 'Test Sport',
            'slug' => 'test-sport',
        ]);
        $this->assertContains($response->status(), [302, 201]);
    }

    public function test_requires_name(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createOrgAdmin($org);
        $response = $this->actingAs($user)->post(route('sports.store'), [
            'slug' => 'test-sport',
        ]);
        $response->assertSessionHasErrors('name');
    }

    public function test_requires_unique_slug_per_org(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createOrgAdmin($org);
        $this->actingAs($user)->post(route('sports.store'), ['name' => 'Sport', 'slug' => 'same-slug']);
        $response = $this->actingAs($user)->post(route('sports.store'), ['name' => 'Sport 2', 'slug' => 'same-slug']);
        $response->assertSessionHasErrors('slug');
    }

    public function test_rejects_invalid_slug(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createOrgAdmin($org);
        $response = $this->actingAs($user)->post(route('sports.store'), [
            'name' => 'Bad Sport',
            'slug' => 'bad slug with spaces',
        ]);
        $response->assertSessionHasErrors('slug');
    }
}
