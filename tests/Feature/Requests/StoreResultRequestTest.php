<?php

namespace Tests\Feature\Requests;

use App\Models\Event;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class StoreResultRequestTest extends TestCase
{
    use RefreshDatabase, CreatesTenantUsers;

    public function test_passes_with_valid_data(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $org->id]);
        $match = Fixture::factory()->create(['organization_id' => $org->id, 'event_id' => $event->id]);
        $user = $this->createOrgAdmin($org);
        $response = $this->actingAs($user)->post(route('results.store'), [
            'match_id' => $match->id,
            'score_home' => 2,
            'score_away' => 1,
        ]);
        $this->assertContains($response->status(), [302, 201]);
    }

    public function test_requires_match_id(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createOrgAdmin($org);
        $response = $this->actingAs($user)->post(route('results.store'), [
            'score_home' => 1,
            'score_away' => 1,
        ]);
        $response->assertSessionHasErrors('match_id');
    }

    public function test_rejects_negative_score(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $org->id]);
        $match = Fixture::factory()->create(['organization_id' => $org->id, 'event_id' => $event->id]);
        $user = $this->createOrgAdmin($org);
        $response = $this->actingAs($user)->post(route('results.store'), [
            'match_id' => $match->id,
            'score_home' => -1,
            'score_away' => 0,
        ]);
        $response->assertSessionHasErrors('score_home');
    }
}
