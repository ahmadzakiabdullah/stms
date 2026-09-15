<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Participant;
use App\Services\ParticipantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ParticipantServiceTest extends TestCase
{
    use RefreshDatabase;

    private ParticipantService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ParticipantService();
    }

    public function test_create_participant(): void
    {
        $this->actingAs(\App\Models\User::factory()->create());

        $participant = $this->service->createParticipant([
            'name' => 'Test Participant',
            'email' => 'test@example.com',
            'type' => 'individual',
            'organization_id' => \App\Models\Organization::factory()->create()->id,
        ]);

        $this->assertNotNull($participant);
        $this->assertEquals('Test Participant', $participant->name);
        $this->assertEquals('test-participant', $participant->slug);
    }

    public function test_update_participant(): void
    {
        $this->actingAs(\App\Models\User::factory()->create());

        $participant = Participant::factory()->create(['name' => 'Original Name']);

        $updated = $this->service->updateParticipant($participant, [
            'name' => 'Updated Name',
        ]);

        $this->assertEquals('Updated Name', $updated->name);
        $this->assertEquals('updated-name', $updated->slug);
    }

    public function test_delete_participant(): void
    {
        $participant = Participant::factory()->create();

        $this->service->deleteParticipant($participant);

        $this->assertSoftDeleted('participants', ['id' => $participant->id]);
    }

    public function test_register_to_event(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $org->id]);
        $participant = Participant::factory()->create(['organization_id' => $org->id]);

        $registration = $this->service->registerToEvent($participant, $event->id);

        $this->assertDatabaseHas('event_participants', [
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'status' => 'pending',
        ]);
        $this->assertEquals('pending', $registration->status);
    }

    public function test_register_to_event_duplicate_throws_exception(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $org->id]);
        $participant = Participant::factory()->create(['organization_id' => $org->id]);

        $this->service->registerToEvent($participant, $event->id);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('already registered');
        $this->service->registerToEvent($participant, $event->id);
    }

    public function test_withdraw_from_event(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $org->id]);
        $participant = Participant::factory()->create(['organization_id' => $org->id]);

        $this->service->registerToEvent($participant, $event->id);
        $this->service->withdrawFromEvent($participant, $event->id);

        $this->assertDatabaseHas('event_participants', [
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'status' => 'withdrawn',
        ]);
    }

    public function test_withdraw_from_event_not_registered_throws_exception(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $org->id]);
        $participant = Participant::factory()->create(['organization_id' => $org->id]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('not registered');
        $this->service->withdrawFromEvent($participant, $event->id);
    }
}
