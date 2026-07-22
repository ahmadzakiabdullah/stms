<?php

namespace Tests\Feature\Requests;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class StoreMatchRequestTest extends TestCase
{
    use RefreshDatabase, CreatesTenantUsers;

    public function test_passes_with_valid_data(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $org->id]);
        $user = $this->createOrgAdmin($org);
        $response = $this->actingAs($user)->post(route('matches.store'), [
            'event_id' => $event->id,
            'match_number' => 1,
        ]);
        $this->assertContains($response->status(), [302, 201]);
    }

    public function test_requires_event_id(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createOrgAdmin($org);
        $response = $this->actingAs($user)->post(route('matches.store'), [
            'match_number' => 1,
        ]);
        $response->assertSessionHasErrors('event_id');
    }

    public function test_requires_positive_match_number(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $org->id]);
        $user = $this->createOrgAdmin($org);
        $response = $this->actingAs($user)->post(route('matches.store'), [
            'event_id' => $event->id,
            'match_number' => 0,
        ]);
        $response->assertSessionHasErrors('match_number');
    }

    public function test_rejects_invalid_status(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $org->id]);
        $user = $this->createOrgAdmin($org);
        $response = $this->actingAs($user)->post(route('matches.store'), [
            'event_id' => $event->id,
            'match_number' => 1,
            'status' => 'invalid_status',
        ]);
        $response->assertSessionHasErrors('status');
    }
}
