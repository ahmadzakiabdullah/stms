<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecuritySentinelTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_logo_path_traversal()
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $participant = Participant::factory()->create(['organization_id' => $organization->id]);

        $response = $this->actingAs($user)->put(route('participants.update', $participant), [
            'organization_id' => $organization->id,
            'name' => 'Test',
            'logo_path_existing' => 'logos/../../test.png',
        ]);

        $response->assertSessionHasErrors(['logo_path_existing']);
    }
}
