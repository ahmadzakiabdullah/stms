<?php

namespace Tests\Feature\Requests;

use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class StoreSessionRequestTest extends TestCase
{
    use RefreshDatabase, CreatesTenantUsers;

    public function test_passes_with_valid_data(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createOrgAdmin($org);
        $response = $this->actingAs($user)->post(route('sessions.store'), [
            'name' => 'Test Session',
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-15',
        ]);
        $this->assertContains($response->status(), [302, 201]);
    }

    public function test_requires_name(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createOrgAdmin($org);
        $response = $this->actingAs($user)->post(route('sessions.store'), [
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-15',
        ]);
        $response->assertSessionHasErrors('name');
    }

    public function test_requires_end_date_after_start(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createOrgAdmin($org);
        $response = $this->actingAs($user)->post(route('sessions.store'), [
            'name' => 'Bad Session',
            'start_date' => '2026-01-15',
            'end_date' => '2026-01-01',
        ]);
        $response->assertSessionHasErrors('end_date');
    }
}
