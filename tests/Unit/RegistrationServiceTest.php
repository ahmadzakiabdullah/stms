<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\Participant;
use App\Models\Registration;
use App\Models\Tournament;
use App\Models\User;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationServiceTest extends TestCase
{
    use RefreshDatabase;

    private RegistrationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RegistrationService;
    }

    public function test_create_registration(): void
    {
        $this->actingAs(User::factory()->create());

        $org = Organization::factory()->create();
        $tournament = Tournament::factory()->create(['organization_id' => $org->id]);
        $participant = Participant::factory()->create(['organization_id' => $org->id]);

        $registration = $this->service->createRegistration([
            'tournament_id' => $tournament->id,
            'participant_id' => $participant->id,
            'organization_id' => $org->id,
            'status' => 'pending',
        ]);

        $this->assertNotNull($registration);
        $this->assertEquals('pending', $registration->status);
    }

    public function test_update_registration(): void
    {
        $registration = Registration::factory()->create(['status' => 'pending']);

        $updated = $this->service->updateRegistration($registration, [
            'status' => 'confirmed',
        ]);

        $this->assertEquals('confirmed', $updated->status);
    }

    public function test_delete_registration(): void
    {
        $registration = Registration::factory()->create();

        $this->service->deleteRegistration($registration);

        $this->assertSoftDeleted('registrations', ['id' => $registration->id]);
    }
}
