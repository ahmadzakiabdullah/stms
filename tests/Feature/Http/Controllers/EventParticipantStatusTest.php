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
