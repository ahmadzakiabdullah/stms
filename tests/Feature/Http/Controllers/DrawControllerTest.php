<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Pool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class DrawControllerTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    public function test_unauthorized_user_cannot_perform_draw(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $org->id]);

        $response = $this->post(route('events.draw', $event));

        $response->assertRedirect(route('login'));

        $staff = $this->createStaffUser($org);

        $response = $this->actingAs($staff)->post(route('events.draw', $event));

        $response->assertForbidden();
    }

    public function test_authorized_user_can_perform_draw_successfully(): void
    {
        $org = Organization::factory()->create();
        $manager = $this->createOrgAdmin($org);

        $event = Event::factory()->create(['organization_id' => $org->id, 'pool_size' => 2]);

        // Need at least 2 confirmed participants
        EventParticipant::factory()->count(4)->create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($manager)->post(route('events.draw', $event));

        $response->assertRedirect(route('events.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseCount('pools', 2);
        $this->assertDatabaseHas('pools', ['event_id' => $event->id, 'name' => 'Group A']);
        $this->assertDatabaseHas('pools', ['event_id' => $event->id, 'name' => 'Group B']);

        $this->assertDatabaseCount('matches', 2); // 4 participants in 2 pools of 2 -> 1 match per pool -> 2 matches
    }

    public function test_draw_fails_when_less_than_two_confirmed_participants(): void
    {
        $org = Organization::factory()->create();
        $manager = $this->createOrgAdmin($org);

        $event = Event::factory()->create(['organization_id' => $org->id]);

        EventParticipant::factory()->create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($manager)->post(route('events.draw', $event));

        $response->assertRedirect(route('events.index'));
        $response->assertSessionHas('error', 'Need at least 2 confirmed participants to draw.');
    }

    public function test_unauthorized_user_cannot_view_draw_result(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $org->id]);

        $response = $this->get(route('events.draw-result', $event));

        $response->assertRedirect(route('login'));

        $otherOrg = Organization::factory()->create();
        $staffOtherOrg = $this->createStaffUser($otherOrg);

        $response = $this->actingAs($staffOtherOrg)->get(route('events.draw-result', $event));

        $response->assertNotFound(); // Due to global tenant scope
    }

    public function test_authorized_user_can_view_draw_result(): void
    {
        $org = Organization::factory()->create();
        $staff = $this->createStaffUser($org);

        $event = Event::factory()->create(['organization_id' => $org->id]);

        $response = $this->actingAs($staff)->get(route('events.draw-result', $event));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('DrawResult/Index')
            ->has('event')
            ->has('pools')
            ->has('canEdit')
        );
    }

    public function test_can_edit_is_true_when_no_matches_have_started(): void
    {
        $org = Organization::factory()->create();
        $staff = $this->createStaffUser($org);

        $event = Event::factory()->create(['organization_id' => $org->id]);

        Fixture::factory()->create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($staff)->get(route('events.draw-result', $event));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('canEdit', true)
        );
    }

    public function test_can_edit_is_false_when_matches_have_started(): void
    {
        $org = Organization::factory()->create();
        $staff = $this->createStaffUser($org);

        $event = Event::factory()->create(['organization_id' => $org->id]);

        Fixture::factory()->create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($staff)->get(route('events.draw-result', $event));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('canEdit', false)
        );
    }

    public function test_unauthorized_user_cannot_move_participant(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $org->id]);

        $response = $this->post(route('events.draw.move-participant', $event), []);

        $response->assertRedirect(route('login'));

        $otherOrg = Organization::factory()->create();
        $staffOtherOrg = $this->createStaffUser($otherOrg);

        $response = $this->actingAs($staffOtherOrg)->post(route('events.draw.move-participant', $event), []);

        $response->assertNotFound(); // Due to global tenant scope
    }

    public function test_authorized_user_can_move_participant(): void
    {
        $org = Organization::factory()->create();
        $manager = $this->createOrgAdmin($org);

        $event = Event::factory()->create(['organization_id' => $org->id]);
        $poolA = Pool::factory()->create(['organization_id' => $org->id, 'event_id' => $event->id]);
        $poolB = Pool::factory()->create(['organization_id' => $org->id, 'event_id' => $event->id]);

        $participant = EventParticipant::factory()->create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'pool_id' => $poolA->id,
        ]);

        $response = $this->actingAs($manager)->post(route('events.draw.move-participant', $event), [
            'event_participant_id' => $participant->id,
            'target_pool_id' => $poolB->id,
        ]);

        $response->assertRedirect(route('events.draw-result', $event));
        $response->assertSessionHas('success', 'Participant moved and fixtures regenerated.');

        $this->assertDatabaseHas('event_participants', [
            'id' => $participant->id,
            'pool_id' => $poolB->id,
        ]);
    }

    public function test_cannot_move_participant_if_matches_have_started(): void
    {
        $org = Organization::factory()->create();
        $manager = $this->createOrgAdmin($org);

        $event = Event::factory()->create(['organization_id' => $org->id]);
        $poolA = Pool::factory()->create(['organization_id' => $org->id, 'event_id' => $event->id]);
        $poolB = Pool::factory()->create(['organization_id' => $org->id, 'event_id' => $event->id]);

        $participant = EventParticipant::factory()->create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'pool_id' => $poolA->id,
        ]);

        Fixture::factory()->create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($manager)->post(route('events.draw.move-participant', $event), [
            'event_participant_id' => $participant->id,
            'target_pool_id' => $poolB->id,
        ]);

        $response->assertRedirect(route('events.draw-result', $event));
        $response->assertSessionHas('error', 'Cannot modify pools after a match has started.');

        $this->assertDatabaseHas('event_participants', [
            'id' => $participant->id,
            'pool_id' => $poolA->id, // Should not have moved
        ]);
    }
}
