<?php

namespace Tests\Feature;

use App\Actions\Participants\RegisterParticipantToEvent;
use App\Actions\Participants\WithdrawParticipantFromEvent;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class ParticipantTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    public function test_non_super_admin_only_sees_own_organization_participants_via_global_scope(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        Participant::factory()->create(['organization_id' => $orgA->id, 'name' => 'Participant A']);
        Participant::factory()->create(['organization_id' => $orgB->id, 'name' => 'Participant B']);

        $userA = $this->createOrgAdmin($orgA);

        $response = $this->actingAs($userA)->get(route('participants.index'));
        $response->assertOk();
        $participants = $response->viewData('page')['props']['participants']['data'] ?? [];
        $names = collect($participants)->pluck('name');

        $this->assertTrue($names->contains('Participant A'));
        $this->assertFalse($names->contains('Participant B'));
    }

    public function test_super_admin_can_create_participant(): void
    {
        $org = Organization::factory()->create();
        $super = $this->createSuperAdmin();

        $response = $this->actingAs($super)->post(route('participants.store'), [
            'name' => 'New Participant',
            'email' => 'newparticipant@example.com',
            'type' => 'individual',
        ]);

        $response->assertRedirect(route('participants.index'));
        $this->assertDatabaseHas('participants', ['name' => 'New Participant']);
    }

    public function test_org_admin_can_upload_standard_and_inverse_participant_logos(): void
    {
        Storage::fake('public');

        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);

        $response = $this->actingAs($admin)->post(route('participants.store'), [
            'name' => 'Faculty Brand',
            'participant_type' => 'team',
            'logo' => UploadedFile::fake()->image('standard.png', 100, 100),
            'inverse_logo' => UploadedFile::fake()->image('inverse.png', 100, 100),
        ]);

        $response->assertRedirect(route('participants.index'));

        $participant = Participant::where('name', 'Faculty Brand')->firstOrFail();
        $this->assertNotNull($participant->logo_path);
        $this->assertNotNull($participant->inverse_logo_path);
        $this->assertNotNull($participant->inverse_logo_url);
        Storage::disk('public')->assertExists($participant->logo_path);
        Storage::disk('public')->assertExists($participant->inverse_logo_path);
    }

    public function test_org_admin_can_replace_and_remove_an_inverse_participant_logo(): void
    {
        Storage::fake('public');

        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        $oldPath = 'logos/old-inverse.png';
        Storage::disk('public')->put($oldPath, 'old');
        $participant = Participant::factory()->create([
            'organization_id' => $org->id,
            'inverse_logo_path' => $oldPath,
        ]);

        $replaceResponse = $this->actingAs($admin)->put(route('participants.update', $participant), [
            'name' => $participant->name,
            'slug' => $participant->slug,
            'participant_type' => $participant->participant_type,
            'inverse_logo' => UploadedFile::fake()->image('replacement.png', 100, 100),
        ]);

        $replaceResponse->assertRedirect(route('participants.index'));
        $participant->refresh();
        $replacementPath = $participant->inverse_logo_path;
        $this->assertNotNull($replacementPath);
        $this->assertNotSame($oldPath, $replacementPath);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($replacementPath);

        $removeResponse = $this->actingAs($admin)->put(route('participants.update', $participant), [
            'name' => $participant->name,
            'slug' => $participant->slug,
            'participant_type' => $participant->participant_type,
            'remove_inverse_logo' => true,
        ]);

        $removeResponse->assertRedirect(route('participants.index'));
        $participant->refresh();
        $this->assertNull($participant->inverse_logo_path);
        Storage::disk('public')->assertMissing($replacementPath);
    }

    public function test_org_admin_can_update_own_participant(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        $participant = Participant::factory()->create(['organization_id' => $org->id]);

        $response = $this->actingAs($admin)->put(route('participants.update', $participant), [
            'name' => 'Updated Participant',
            'slug' => $participant->slug,
            'type' => $participant->type,
        ]);

        $response->assertRedirect(route('participants.index'));
        $this->assertDatabaseHas('participants', ['name' => 'Updated Participant']);
    }

    public function test_non_super_admin_cannot_update_participant_in_other_org(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $adminA = $this->createOrgAdmin($orgA);
        $participantB = Participant::factory()->create(['organization_id' => $orgB->id]);

        $response = $this->actingAs($adminA)->put(route('participants.update', $participantB), [
            'name' => 'Hacked',
            'slug' => $participantB->slug,
        ]);

        $response->assertNotFound();
    }

    public function test_super_admin_can_delete_participant(): void
    {
        $org = Organization::factory()->create();
        $super = $this->createSuperAdmin();
        $participant = Participant::factory()->create(['organization_id' => $org->id]);

        $response = $this->actingAs($super)->delete(route('participants.destroy', $participant));
        $response->assertRedirect(route('participants.index'));
        $this->assertSoftDeleted('participants', ['id' => $participant->id]);
    }

    public function test_register_participant_to_event_via_action(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $org->id]);
        $participant = Participant::factory()->create(['organization_id' => $org->id]);

        $action = app(RegisterParticipantToEvent::class);
        $action->handle($participant, $event->id);

        $this->assertDatabaseHas('event_participants', [
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'status' => 'pending',
        ]);
    }

    public function test_withdraw_participant_from_event_via_action(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $org->id]);
        $participant = Participant::factory()->create(['organization_id' => $org->id]);

        $register = app(RegisterParticipantToEvent::class);
        $register->handle($participant, $event->id);

        $withdraw = app(WithdrawParticipantFromEvent::class);
        $withdraw->handle($participant, $event->id);

        $this->assertDatabaseHas('event_participants', [
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'status' => 'withdrawn',
        ]);
    }
}
