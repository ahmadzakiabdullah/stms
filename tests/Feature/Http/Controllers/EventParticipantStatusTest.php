<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Session;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\Tournament;
use App\Models\User;
use App\Notifications\EventParticipantConfirmed;
use App\Notifications\EventParticipantRejected;
use App\Notifications\NewEventRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EventParticipantStatusTest extends TestCase
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

        $event = Event::factory()->create([
            'organization_id' => $org->id, 'tournament_id' => $tournament->id,
            'sport_id' => $sport->id, 'sport_category_id' => $cat->id,
            'name' => 'Badminton - Singles',
        ]);

        $facA = Participant::factory()->create(['organization_id' => $org->id, 'session_id' => $session->id, 'name' => 'Fakulti Kejuruteraan']);

        $ep = EventParticipant::create(['organization_id' => $org->id, 'event_id' => $event->id, 'participant_id' => $facA->id, 'status' => 'pending']);

        return ['org' => $org, 'session' => $session, 'event' => $event, 'facA' => $facA, 'ep' => $ep];
    }

    public function test_new_registration_notifies_same_organization_admins_and_dean(): void
    {
        Notification::fake();

        $actor = $this->seedSuperAdmin();
        $data = $this->seedData();
        Role::firstOrCreate(['name' => 'org-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'dean', 'guard_name' => 'web']);

        $participant = Participant::factory()->create([
            'organization_id' => $data['org']->id,
            'session_id' => $data['session']->id,
        ]);
        $orgAdmin = User::factory()->create(['organization_id' => $data['org']->id]);
        $orgAdmin->assignRole('org-admin');
        $dean = User::factory()->create([
            'organization_id' => $data['org']->id,
            'participant_id' => $participant->id,
        ]);
        $dean->assignRole('dean');

        $otherOrganization = Organization::factory()->create();
        $otherAdmin = User::factory()->create(['organization_id' => $otherOrganization->id]);
        $otherAdmin->assignRole('org-admin');

        $this->actingAs($actor)->post(route('event-participants.store'), [
            'event_id' => $data['event']->id,
            'participant_id' => $participant->id,
        ])->assertRedirect(route('event-participants.index'));

        Notification::assertSentTo($orgAdmin, NewEventRegistration::class);
        Notification::assertSentTo($dean, NewEventRegistration::class);
        Notification::assertNotSentTo($otherAdmin, NewEventRegistration::class);
    }

    public function test_faculty_representative_can_register_multiple_events_at_once(): void
    {
        Notification::fake();

        Role::firstOrCreate(['name' => 'faculty-representative', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'dean', 'guard_name' => 'web']);

        $data = $this->seedData();
        $eventB = Event::factory()->create([
            'organization_id' => $data['org']->id,
            'tournament_id' => $data['event']->tournament_id,
            'sport_id' => Sport::factory()->create(['organization_id' => $data['org']->id, 'name' => 'Football'])->id,
            'sport_category_id' => SportCategory::factory()->create(['organization_id' => $data['org']->id, 'name' => 'Team'])->id,
            'name' => 'Football - Team',
        ]);
        $eventC = Event::factory()->create([
            'organization_id' => $data['org']->id,
            'tournament_id' => $data['event']->tournament_id,
            'sport_id' => Sport::factory()->create(['organization_id' => $data['org']->id, 'name' => 'Netball'])->id,
            'sport_category_id' => SportCategory::factory()->create(['organization_id' => $data['org']->id, 'name' => 'Women'])->id,
            'name' => 'Netball - Women',
        ]);

        $dean = User::factory()->create([
            'organization_id' => $data['org']->id,
            'participant_id' => $data['facA']->id,
        ]);
        $dean->assignRole('dean');

        $facRep = User::factory()->create([
            'organization_id' => $data['org']->id,
            'participant_id' => $data['facA']->id,
        ]);
        $facRep->assignRole('faculty-representative');

        $response = $this->actingAs($facRep)
            ->post(route('event-participants.store-batch'), [
                'event_ids' => [$eventB->id, $eventC->id],
            ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success', 'Registered for 2 event(s).');

        $this->assertDatabaseHas('event_participants', [
            'event_id' => $eventB->id,
            'participant_id' => $data['facA']->id,
        ]);
        $this->assertDatabaseHas('event_participants', [
            'event_id' => $eventC->id,
            'participant_id' => $data['facA']->id,
        ]);

        Notification::assertSentToTimes($dean, NewEventRegistration::class, 2);
    }

    public function test_faculty_representative_batch_registration_skips_deadline_passed_events(): void
    {
        Role::firstOrCreate(['name' => 'faculty-representative', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'dean', 'guard_name' => 'web']);

        $data = $this->seedData();
        $freshEvent = Event::factory()->create([
            'organization_id' => $data['org']->id,
            'tournament_id' => $data['event']->tournament_id,
            'sport_id' => Sport::factory()->create(['organization_id' => $data['org']->id, 'name' => 'Football'])->id,
            'sport_category_id' => SportCategory::factory()->create(['organization_id' => $data['org']->id, 'name' => 'Team'])->id,
            'name' => 'Football - Team',
        ]);
        $expiredEvent = Event::factory()->create([
            'organization_id' => $data['org']->id,
            'tournament_id' => $data['event']->tournament_id,
            'sport_id' => Sport::factory()->create(['organization_id' => $data['org']->id, 'name' => 'Netball'])->id,
            'sport_category_id' => SportCategory::factory()->create(['organization_id' => $data['org']->id, 'name' => 'Women'])->id,
            'name' => 'Netball - Women',
            'registration_deadline' => now()->subDay(),
        ]);

        $facRep = User::factory()->create([
            'organization_id' => $data['org']->id,
            'participant_id' => $data['facA']->id,
        ]);
        $facRep->assignRole('faculty-representative');

        $response = $this->actingAs($facRep)
            ->post(route('event-participants.store-batch'), [
                'event_ids' => [$freshEvent->id, $expiredEvent->id],
            ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success', 'Registered for 1 event(s).');
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('event_participants', [
            'event_id' => $freshEvent->id,
            'participant_id' => $data['facA']->id,
        ]);
        $this->assertDatabaseMissing('event_participants', [
            'event_id' => $expiredEvent->id,
            'participant_id' => $data['facA']->id,
        ]);
    }

    public function test_faculty_representative_registration_redirects_back_to_dashboard(): void
    {
        Role::firstOrCreate(['name' => 'faculty-representative', 'guard_name' => 'web']);

        $data = $this->seedData();
        $facRep = User::factory()->create([
            'organization_id' => $data['org']->id,
            'participant_id' => $data['facA']->id,
        ]);
        $facRep->assignRole('faculty-representative');

        $this->actingAs($facRep)
            ->post(route('event-participants.store'), [
                'event_id' => $data['event']->id,
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('event_participants', [
            'event_id' => $data['event']->id,
            'participant_id' => $data['facA']->id,
        ]);
    }

    public function test_super_admin_can_approve_pending_registration(): void
    {
        Notification::fake();

        $user = $this->seedSuperAdmin();
        $data = $this->seedData();
        $facUser = User::factory()->create(['participant_id' => $data['facA']->id]);
        $data['facA']->users()->save($facUser);

        $this->actingAs($user)
            ->patch(route('event-participants.status', $data['ep']->id), ['status' => 'confirmed'])
            ->assertRedirect(route('event-participants.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('event_participants', [
            'id' => $data['ep']->id, 'status' => 'confirmed',
        ]);

        Notification::assertSentTo($facUser, EventParticipantConfirmed::class);
    }

    public function test_super_admin_can_reject_pending_registration(): void
    {
        Notification::fake();

        $user = $this->seedSuperAdmin();
        $data = $this->seedData();
        $facUser = User::factory()->create(['participant_id' => $data['facA']->id]);
        $data['facA']->users()->save($facUser);

        $this->actingAs($user)
            ->patch(route('event-participants.status', $data['ep']->id), ['status' => 'rejected'])
            ->assertRedirect(route('event-participants.index'));

        $this->assertDatabaseHas('event_participants', [
            'id' => $data['ep']->id, 'status' => 'rejected',
        ]);

        Notification::assertSentTo($facUser, EventParticipantRejected::class);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $user = $this->seedSuperAdmin();
        $data = $this->seedData();

        $this->actingAs($user)
            ->patch(route('event-participants.status', $data['ep']->id), ['status' => 'bogus'])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('event_participants', [
            'id' => $data['ep']->id, 'status' => 'pending',
        ]);
    }

    public function test_other_org_admin_cannot_update_status(): void
    {
        Role::firstOrCreate(['name' => 'org-admin']);
        $otherOrg = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $otherOrg->id]);
        $user->assignRole('org-admin');

        $data = $this->seedData();

        $this->actingAs($user)
            ->patch(route('event-participants.status', $data['ep']->id), ['status' => 'confirmed'])
            ->assertNotFound();

        $this->assertDatabaseHas('event_participants', [
            'id' => $data['ep']->id, 'status' => 'pending',
        ]);
    }

    public function test_index_exposes_status_counts_and_filters_by_status(): void
    {
        $user = $this->seedSuperAdmin();
        $data = $this->seedData();

        $response = $this->actingAs($user)
            ->get(route('event-participants.index', ['status' => 'pending']))
            ->assertInertia(fn ($page) => $page
                ->component('EventParticipants/Index')
                ->has('statusCounts', fn ($counts) => $counts->has('pending')->where('pending', 1)->etc())
                ->where('participants.data.0.name', $data['facA']->name)
            );

        $data['ep']->update(['status' => 'confirmed']);

        $this->actingAs($user)
            ->get(route('event-participants.index', ['status' => 'confirmed']))
            ->assertInertia(fn ($page) => $page
                ->where('participants.data.0.event_participants.0.status', 'confirmed')
            );
    }
}
