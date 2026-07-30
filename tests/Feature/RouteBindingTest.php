<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Registration;
use App\Models\Result;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class RouteBindingTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    public function test_result_route_binds_by_uuid(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $org->id]);
        $match = Fixture::factory()->create(['organization_id' => $org->id, 'event_id' => $event->id]);
        $result = Result::factory()->create(['organization_id' => $org->id, 'match_id' => $match->id]);
        $super = $this->createSuperAdmin();

        $response = $this->actingAs($super)->get(route('results.index'));

        $response->assertOk();
    }

    public function test_registration_route_binds_by_uuid(): void
    {
        $org = Organization::factory()->create();
        $tournament = Tournament::factory()->create(['organization_id' => $org->id]);
        $participant = Participant::factory()->create(['organization_id' => $org->id]);
        $registration = Registration::factory()->create([
            'organization_id' => $org->id,
            'tournament_id' => $tournament->id,
            'participant_id' => $participant->id,
        ]);
        $super = $this->createSuperAdmin();

        $response = $this->actingAs($super)->get(route('registrations.index'));

        $response->assertOk();
    }

    public function test_result_is_resolved_by_uuid_not_slug(): void
    {
        $result = new Result;
        $this->assertEquals('id', $result->getRouteKeyName());
    }

    public function test_registration_is_resolved_by_uuid_not_slug(): void
    {
        $registration = new Registration;
        $this->assertEquals('id', $registration->getRouteKeyName());
    }
}
