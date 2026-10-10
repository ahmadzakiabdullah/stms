<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Activity;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Session;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EventParticipantBatchTest extends TestCase
{
    use RefreshDatabase;

    private function seedSuperAdmin(): User
    {
        Role::firstOrCreate(['name' => 'super-admin']);
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }

    private function seedData(): array
    {
        $org = Organization::factory()->create();
        $session = Session::factory()->create(['organization_id' => $org->id]);
        $tournament = Tournament::factory()->create(['organization_id' => $org->id, 'session_id' => $session->id]);
        $sport = Sport::factory()->create(['organization_id' => $org->id, 'name' => 'Badminton']);
        $cat = SportCategory::factory()->create(['organization_id' => $org->id, 'sport_id' => $sport->id, 'name' => 'Singles']);

        $eventA = Event::factory()->create([
            'organization_id' => $org->id, 'tournament_id' => $tournament->id,
            'sport_id' => $sport->id, 'sport_category_id' => $cat->id, 'name' => 'Badminton - Singles',
        ]);

        $sportB = Sport::factory()->create(['organization_id' => $org->id, 'name' => 'Football']);
        $catB = SportCategory::factory()->create(['organization_id' => $org->id, 'sport_id' => $sportB->id, 'name' => 'Team']);
        $eventB = Event::factory()->create([
            'organization_id' => $org->id, 'tournament_id' => $tournament->id,
            'sport_id' => $sportB->id, 'sport_category_id' => $catB->id, 'name' => 'Football - Team',
        ]);

        $facA = Participant::factory()->create(['organization_id' => $org->id, 'session_id' => $session->id, 'name' => 'Fakulti Kejuruteraan']);

        $epA = EventParticipant::create(['organization_id' => $org->id, 'event_id' => $eventA->id, 'participant_id' => $facA->id, 'status' => 'pending']);
        $epB = EventParticipant::create(['organization_id' => $org->id, 'event_id' => $eventB->id, 'participant_id' => $facA->id, 'status' => 'pending']);

        return ['org' => $org, 'eventA' => $eventA, 'eventB' => $eventB, 'facA' => $facA, 'epA' => $epA, 'epB' => $epB];
    }

    public function test_admin_can_batch_approve_pending_registrations(): void
    {
        $user = $this->seedSuperAdmin();
        $data = $this->seedData();

        $this->actingAs($user)
            ->post(route('event-participants.batch-status'), [
                'ids' => [$data['epA']->id, $data['epB']->id],
                'status' => 'confirmed',
            ])
            ->assertRedirect(route('event-participants.index'))
            ->assertSessionHas('success', '2 registration(s) approved.');

        $this->assertDatabaseHas('event_participants', ['id' => $data['epA']->id, 'status' => 'confirmed']);
        $this->assertDatabaseHas('event_participants', ['id' => $data['epB']->id, 'status' => 'confirmed']);
    }

    public function test_batch_approve_records_summary_activity(): void
    {
        $user = $this->seedSuperAdmin();
        $data = $this->seedData();

        $this->actingAs($user)
            ->post(route('event-participants.batch-status'), [
                'ids' => [$data['epA']->id, $data['epB']->id],
                'status' => 'confirmed',
            ])
            ->assertSessionHas('success', '2 registration(s) approved.');

        $summary = Activity::query()
            ->where('event', 'bulk_status_updated')
            ->where('subject_type', Organization::class)
            ->where('subject_id', $data['org']->id)
            ->firstOrFail();

        $this->assertSame('event_participants.batch_status', $summary->properties['bulk_action']);
        $this->assertSame('confirmed', $summary->properties['target_status']);
        $this->assertSame(2, $summary->properties['selected_count']);
        $this->assertSame(2, $summary->properties['updated_count']);
        $this->assertSame(0, $summary->properties['skipped_count']);
        $this->assertNotEmpty($summary->properties['bulk_action_id']);
    }

    public function test_admin_can_batch_reject_with_shared_reason(): void
    {
        $user = $this->seedSuperAdmin();
        $data = $this->seedData();

        $this->actingAs($user)
            ->post(route('event-participants.batch-status'), [
                'ids' => [$data['epA']->id],
                'status' => 'rejected',
                'notes' => 'Over quota.',
            ])
            ->assertRedirect(route('event-participants.index'))
            ->assertSessionHas('success', '1 registration(s) rejected.');

        $this->assertDatabaseHas('event_participants', ['id' => $data['epA']->id, 'status' => 'rejected']);
    }

    public function test_batch_reject_without_reason_is_rejected_by_validation(): void
    {
        $user = $this->seedSuperAdmin();
        $data = $this->seedData();

        $this->actingAs($user)
            ->post(route('event-participants.batch-status'), [
                'ids' => [$data['epA']->id],
                'status' => 'rejected',
            ])
            ->assertSessionHasErrors('notes');

        $this->assertDatabaseHas('event_participants', ['id' => $data['epA']->id, 'status' => 'pending']);
    }

    public function test_batch_reject_records_shared_reason_in_summary_activity(): void
    {
        $user = $this->seedSuperAdmin();
        $data = $this->seedData();

        $this->actingAs($user)
            ->post(route('event-participants.batch-status'), [
                'ids' => [$data['epA']->id],
                'status' => 'rejected',
                'notes' => 'Over quota.',
            ])
            ->assertSessionHas('success', '1 registration(s) rejected.');

        $summary = Activity::query()
            ->where('event', 'bulk_status_updated')
            ->where('subject_type', Organization::class)
            ->where('subject_id', $data['org']->id)
            ->firstOrFail();

        $this->assertSame('rejected', $summary->properties['target_status']);
        $this->assertSame('Over quota.', $summary->properties['notes']);
        $this->assertSame(1, $summary->properties['selected_count']);
        $this->assertSame(1, $summary->properties['updated_count']);
    }

    public function test_batch_rejects_cannot_touch_other_organizations(): void
    {
        Role::firstOrCreate(['name' => 'org-admin']);
        $otherOrg = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $otherOrg->id]);
        $user->assignRole('org-admin');

        $data = $this->seedData();

        $this->actingAs($user)
            ->post(route('event-participants.batch-status'), [
                'ids' => [$data['epA']->id],
                'status' => 'confirmed',
            ])
            ->assertRedirect(route('event-participants.index'))
            ->assertSessionHas('success', '0 registration(s) approved.');

        $this->assertDatabaseHas('event_participants', ['id' => $data['epA']->id, 'status' => 'pending']);
    }

    public function test_super_admin_batch_status_rejects_mixed_organization_selection(): void
    {
        $user = $this->seedSuperAdmin();
        $tenantA = $this->seedData();
        $tenantB = $this->seedData();

        $this->actingAs($user)
            ->post(route('event-participants.batch-status'), [
                'ids' => [$tenantA['epA']->id, $tenantB['epA']->id],
                'status' => 'confirmed',
            ])
            ->assertRedirect(route('event-participants.index'))
            ->assertSessionHas('error', 'Bulk status updates can only be applied to registrations from one organization at a time.');

        $this->assertDatabaseHas('event_participants', ['id' => $tenantA['epA']->id, 'status' => 'pending']);
        $this->assertDatabaseHas('event_participants', ['id' => $tenantB['epA']->id, 'status' => 'pending']);
    }

    public function test_batch_skips_confirmed_registrations_in_approval_batch(): void
    {
        $user = $this->seedSuperAdmin();
        $data = $this->seedData();
        $data['epA']->update(['status' => 'confirmed']);

        $this->actingAs($user)
            ->post(route('event-participants.batch-status'), [
                'ids' => [$data['epA']->id, $data['epB']->id],
                'status' => 'confirmed',
            ])
            ->assertSessionHas('success', '1 registration(s) approved.');

        $this->assertDatabaseHas('event_participants', ['id' => $data['epA']->id, 'status' => 'confirmed']);
        $this->assertDatabaseHas('event_participants', ['id' => $data['epB']->id, 'status' => 'confirmed']);
    }

    public function test_faculty_representative_can_withdraw_own_registration(): void
    {
        Role::firstOrCreate(['name' => 'faculty-representative', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'dean', 'guard_name' => 'web']);

        $data = $this->seedData();
        $data['epA']->update(['status' => 'confirmed']);

        $facRep = User::factory()->create([
            'organization_id' => $data['org']->id,
            'participant_id' => $data['facA']->id,
        ]);
        $facRep->assignRole('faculty-representative');

        $this->actingAs($facRep)
            ->post(route('event-participants.withdraw', $data['epA']->id))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('event_participants', ['id' => $data['epA']->id, 'status' => 'withdrawn']);
    }

    public function test_faculty_representative_cannot_withdraw_other_faculty_registration(): void
    {
        Role::firstOrCreate(['name' => 'faculty-representative', 'guard_name' => 'web']);

        $data = $this->seedData();
        $facB = Participant::factory()->create(['organization_id' => $data['org']->id, 'name' => 'Fakulti Sains']);

        $facRep = User::factory()->create([
            'organization_id' => $data['org']->id,
            'participant_id' => $facB->id,
        ]);
        $facRep->assignRole('faculty-representative');

        $this->actingAs($facRep)
            ->post(route('event-participants.withdraw', $data['epA']->id))
            ->assertForbidden();

        $this->assertDatabaseHas('event_participants', ['id' => $data['epA']->id, 'status' => 'pending']);
    }

    public function test_cannot_withdraw_from_disqualified_state(): void
    {
        $user = $this->seedSuperAdmin();
        $data = $this->seedData();
        $data['epA']->update(['status' => 'disqualified']);

        $this->actingAs($user)
            ->post(route('event-participants.withdraw', $data['epA']->id))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('event_participants', ['id' => $data['epA']->id, 'status' => 'disqualified']);
    }

    public function test_admin_can_import_registrations_from_csv(): void
    {
        $user = $this->seedSuperAdmin();
        $data = $this->seedData();
        $data['epA']->forceDelete();
        $data['epB']->forceDelete();

        $file = UploadedFile::fake()->createWithContent('registrations.csv', implode("\n", [
            'event_name',
            'Badminton - Singles',
            'Football - Team',
        ]));

        $this->actingAs($user)
            ->post(route('event-participants.import'), [
                'participant_id' => $data['facA']->id,
                'file' => $file,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('event_participants', [
            'event_id' => $data['eventA']->id, 'participant_id' => $data['facA']->id, 'status' => 'pending',
        ]);
        $this->assertDatabaseHas('event_participants', [
            'event_id' => $data['eventB']->id, 'participant_id' => $data['facA']->id, 'status' => 'pending',
        ]);

        $summary = Activity::query()
            ->where('event', 'bulk_imported')
            ->where('subject_type', Participant::class)
            ->where('subject_id', $data['facA']->id)
            ->firstOrFail();

        $this->assertSame('event_participants.import', $summary->properties['bulk_action']);
        $this->assertSame(2, $summary->properties['selected_count']);
        $this->assertSame(2, $summary->properties['created_count']);
        $this->assertSame(0, $summary->properties['error_count']);
        $this->assertNotEmpty($summary->properties['bulk_action_id']);
    }

    public function test_import_reports_unknown_event_and_skips_duplicates(): void
    {
        $user = $this->seedSuperAdmin();
        $data = $this->seedData();
        $data['epB']->forceDelete();

        $file = UploadedFile::fake()->createWithContent('registrations.csv', implode("\n", [
            'event_name',
            'Badminton - Singles',
            'Football - Team',
            'Unknown Sport Cup',
        ]));

        $response = $this->actingAs($user)
            ->post(route('event-participants.import'), [
                'participant_id' => $data['facA']->id,
                'file' => $file,
            ])
            ->assertSessionHas('error');

        $error = $response->getSession()->get('error');
        $this->assertStringContainsString('already registered', $error, 'Expected duplicate skip notice');
        $this->assertStringContainsString('Unknown Sport Cup', $error, 'Expected unknown event notice');

        $this->assertDatabaseHas('event_participants', [
            'event_id' => $data['eventB']->id, 'participant_id' => $data['facA']->id,
        ]);

        $summary = Activity::query()
            ->where('event', 'bulk_imported')
            ->where('subject_type', Participant::class)
            ->where('subject_id', $data['facA']->id)
            ->firstOrFail();

        $this->assertSame('event_participants.import', $summary->properties['bulk_action']);
        $this->assertSame(1, $summary->properties['created_count']);
        $this->assertSame(2, $summary->properties['error_count']);
        $this->assertNotEmpty($summary->properties['failures']);
    }

    public function test_import_template_download_requires_create_ability(): void
    {
        $user = $this->seedSuperAdmin();

        $response = $this->actingAs($user)->get(route('event-participants.import.template'));
        $response->assertOk();
        $this->assertStringContainsString('event_name', $response->streamedContent());
    }
}
