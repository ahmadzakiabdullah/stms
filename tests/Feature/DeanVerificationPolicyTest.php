<?php

namespace Tests\Feature;

use App\Models\EventParticipant;
use App\Models\Participant;
use App\Models\User;
use App\Policies\DeanVerificationPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DeanVerificationPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_dean_can_view_dashboard_and_verify_own_faculty_registration(): void
    {
        Role::firstOrCreate(['name' => 'dean', 'guard_name' => 'web']);

        $participant = Participant::factory()->create();
        $dean = User::factory()->forOrganization($participant->organization)->create([
            'participant_id' => $participant->id,
        ]);
        $dean->assignRole('dean');

        $registration = new EventParticipant(['participant_id' => $participant->id]);
        $policy = new DeanVerificationPolicy;

        $this->assertTrue($policy->viewAny($dean));
        $this->assertTrue($policy->verify($dean, $registration));
    }

    public function test_faculty_representative_cannot_use_dean_verification_actions(): void
    {
        Role::firstOrCreate(['name' => 'faculty-representative', 'guard_name' => 'web']);

        $participant = Participant::factory()->create();
        $representative = User::factory()->forOrganization($participant->organization)->create([
            'participant_id' => $participant->id,
        ]);
        $representative->assignRole('faculty-representative');

        $registration = new EventParticipant(['participant_id' => $participant->id]);
        $policy = new DeanVerificationPolicy;

        $this->assertFalse($policy->viewAny($representative));
        $this->assertFalse($policy->verify($representative, $registration));
    }
}
