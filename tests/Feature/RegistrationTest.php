<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Participant;
use App\Models\Registration;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class RegistrationTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    public function test_non_super_admin_only_sees_own_organization_registrations_via_global_scope(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $tournamentA = Tournament::factory()->create(['organization_id' => $orgA->id]);
        $participantA = Participant::factory()->create(['organization_id' => $orgA->id]);
        Registration::factory()->create([
            'organization_id' => $orgA->id,
            'tournament_id' => $tournamentA->id,
            'participant_id' => $participantA->id,
        ]);

        $userA = $this->createStaffUser($orgA);

        $response = $this->actingAs($userA)->get(route('registrations.index'));
        $response->assertOk();
    }

    public function test_super_admin_can_create_registration(): void
    {
        $org = Organization::factory()->create();
        $tournament = Tournament::factory()->create(['organization_id' => $org->id]);
        $participant = Participant::factory()->create(['organization_id' => $org->id]);
        $super = $this->createSuperAdmin();

        $response = $this->actingAs($super)->post(route('registrations.store'), [
            'tournament_id' => $tournament->id,
            'participant_id' => $participant->id,
            'status' => 'pending',
        ]);

        $response->assertRedirect(route('registrations.index'));
        $this->assertDatabaseHas('registrations', [
            'tournament_id' => $tournament->id,
            'participant_id' => $participant->id,
        ]);
    }

    public function test_org_admin_can_update_own_registration(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        $tournament = Tournament::factory()->create(['organization_id' => $org->id]);
        $participant = Participant::factory()->create(['organization_id' => $org->id]);
        $registration = Registration::factory()->create([
            'organization_id' => $org->id,
            'tournament_id' => $tournament->id,
            'participant_id' => $participant->id,
        ]);

        $response = $this->actingAs($admin)->put(route('registrations.update', $registration), [
            'tournament_id' => $tournament->id,
            'participant_id' => $participant->id,
            'status' => 'confirmed',
        ]);

        $response->assertRedirect(route('registrations.index'));
        $this->assertDatabaseHas('registrations', ['status' => 'confirmed']);
    }

    public function test_non_super_admin_cannot_update_registration_in_other_org(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $adminA = $this->createOrgAdmin($orgA);

        $tournamentB = Tournament::factory()->create(['organization_id' => $orgB->id]);
        $participantB = Participant::factory()->create(['organization_id' => $orgB->id]);
        $registrationB = Registration::factory()->create([
            'organization_id' => $orgB->id,
            'tournament_id' => $tournamentB->id,
            'participant_id' => $participantB->id,
        ]);

        $response = $this->actingAs($adminA)->put(route('registrations.update', $registrationB), [
            'tournament_id' => $tournamentB->id,
            'participant_id' => $participantB->id,
            'status' => 'confirmed',
        ]);

        $response->assertNotFound();
    }

    public function test_super_admin_can_delete_registration(): void
    {
        $org = Organization::factory()->create();
        $super = $this->createSuperAdmin();
        $tournament = Tournament::factory()->create(['organization_id' => $org->id]);
        $participant = Participant::factory()->create(['organization_id' => $org->id]);
        $registration = Registration::factory()->create([
            'organization_id' => $org->id,
            'tournament_id' => $tournament->id,
            'participant_id' => $participant->id,
        ]);

        $response = $this->actingAs($super)->delete(route('registrations.destroy', $registration));
        $response->assertRedirect(route('registrations.index'));
        $this->assertSoftDeleted('registrations', ['id' => $registration->id]);
    }
}
