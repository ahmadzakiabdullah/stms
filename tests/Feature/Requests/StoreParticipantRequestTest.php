<?php

namespace Tests\Feature\Requests;

use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class StoreParticipantRequestTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    public function test_passes_with_valid_data(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createOrgAdmin($org);
        $response = $this->actingAs($user)->post(route('participants.store'), [
            'name' => 'Test Participant',
            'type' => 'individual',
        ]);
        $this->assertContains($response->status(), [302, 201]);
    }

    public function test_requires_name(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createOrgAdmin($org);
        $response = $this->actingAs($user)->post(route('participants.store'), []);
        $response->assertSessionHasErrors('name');
    }

    public function test_requires_valid_participant_type(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createOrgAdmin($org);
        $response = $this->actingAs($user)->post(route('participants.store'), [
            'name' => 'Bad Type',
            'participant_type' => 'alien',
        ]);
        $response->assertSessionHasErrors('participant_type');
    }
}
