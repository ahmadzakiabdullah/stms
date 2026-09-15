<?php

namespace Tests\Feature\Requests;

use App\Models\Organization;
use App\Models\Participant;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class StoreRegistrationRequestTest extends TestCase
{
    use RefreshDatabase, CreatesTenantUsers;

    public function test_passes_with_valid_data(): void
    {
        $org = Organization::factory()->create();
        $tournament = Tournament::factory()->create(['organization_id' => $org->id]);
        $participant = Participant::factory()->create(['organization_id' => $org->id]);
        $user = $this->createOrgAdmin($org);
        $response = $this->actingAs($user)->post(route('registrations.store'), [
            'tournament_id' => $tournament->id,
            'participant_id' => $participant->id,
        ]);
        $this->assertContains($response->status(), [302, 201]);
    }

    public function test_requires_tournament_id(): void
    {
        $org = Organization::factory()->create();
        $participant = Participant::factory()->create(['organization_id' => $org->id]);
        $user = $this->createOrgAdmin($org);
        $response = $this->actingAs($user)->post(route('registrations.store'), [
            'participant_id' => $participant->id,
        ]);
        $response->assertSessionHasErrors('tournament_id');
    }

    public function test_rejects_cross_org_participant(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $tournamentA = Tournament::factory()->create(['organization_id' => $orgA->id]);
        $participantB = Participant::factory()->create(['organization_id' => $orgB->id]);
        $user = $this->createOrgAdmin($orgA);
        $response = $this->actingAs($user)->post(route('registrations.store'), [
            'tournament_id' => $tournamentA->id,
            'participant_id' => $participantB->id,
        ]);
        $response->assertSessionHasErrors('participant_id');
    }
}
