<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Result;
use App\Models\Session;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\Tournament;
use App\Models\User;
use App\Notifications\MatchResultNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class ResultTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    public function test_non_super_admin_only_sees_own_organization_results_via_global_scope(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $eventA = Event::factory()->create(['organization_id' => $orgA->id]);
        $matchA = Fixture::factory()->create(['organization_id' => $orgA->id, 'event_id' => $eventA->id]);
        Result::factory()->create(['organization_id' => $orgA->id, 'match_id' => $matchA->id]);

        $eventB = Event::factory()->create(['organization_id' => $orgB->id]);
        $matchB = Fixture::factory()->create(['organization_id' => $orgB->id, 'event_id' => $eventB->id]);
        Result::factory()->create(['organization_id' => $orgB->id, 'match_id' => $matchB->id]);

        $userA = $this->createOrgAdmin($orgA);

        $response = $this->actingAs($userA)->get(route('results.index'));
        $response->assertOk();
    }

    public function test_super_admin_can_create_result(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $org->id]);
        $match = Fixture::factory()->create(['organization_id' => $org->id, 'event_id' => $event->id]);
        $super = $this->createSuperAdmin();

        $response = $this->actingAs($super)->post(route('results.store'), [
            'match_id' => $match->id,
            'score_home' => 3,
            'score_away' => 1,
        ]);

        $response->assertRedirect(route('results.index'));
        $this->assertDatabaseHas('results', [
            'match_id' => $match->id,
            'score_home' => 3,
            'score_away' => 1,
        ]);
    }

    public function test_org_admin_can_update_own_result(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        $event = Event::factory()->create(['organization_id' => $org->id]);
        $match = Fixture::factory()->create(['organization_id' => $org->id, 'event_id' => $event->id]);
        $result = Result::factory()->create([
            'organization_id' => $org->id,
            'match_id' => $match->id,
            'score_home' => 1,
            'score_away' => 0,
        ]);

        $response = $this->actingAs($admin)->put(route('results.update', $result), [
            'match_id' => $match->id,
            'score_home' => 2,
            'score_away' => 1,
        ]);

        $response->assertRedirect(route('results.index'));
        $this->assertDatabaseHas('results', ['score_home' => 2, 'score_away' => 1]);
    }

    public function test_non_super_admin_cannot_update_result_in_other_org(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $adminA = $this->createOrgAdmin($orgA);

        $eventB = Event::factory()->create(['organization_id' => $orgB->id]);
        $matchB = Fixture::factory()->create(['organization_id' => $orgB->id, 'event_id' => $eventB->id]);
        $resultB = Result::factory()->create([
            'organization_id' => $orgB->id,
            'match_id' => $matchB->id,
        ]);

        $response = $this->actingAs($adminA)->put(route('results.update', $resultB), [
            'match_id' => $matchB->id,
            'score_home' => 99,
        ]);

        $response->assertNotFound();
    }

    public function test_super_admin_can_delete_result(): void
    {
        $org = Organization::factory()->create();
        $super = $this->createSuperAdmin(['organization_id' => $org->id]);
        $match = Fixture::factory()->create(['organization_id' => $org->id]);
        $result = Result::factory()->create([
            'organization_id' => $org->id,
            'match_id' => $match->id,
        ]);

        $response = $this->actingAs($super)->delete(route('results.destroy', $result));
        $response->assertRedirect(route('results.index'));
        $this->assertSoftDeleted('results', ['id' => $result->id]);
    }

    private function seedMatchWithParticipantUsers(): array
    {
        $org = Organization::factory()->create();
        $session = Session::factory()->create(['organization_id' => $org->id]);
        $tournament = Tournament::factory()->create(['organization_id' => $org->id, 'session_id' => $session->id]);
        $sport = Sport::factory()->create(['organization_id' => $org->id, 'name' => 'Badminton']);
        $cat = SportCategory::factory()->create(['organization_id' => $org->id, 'sport_id' => $sport->id, 'name' => 'Singles']);
        $event = Event::factory()->create([
            'organization_id' => $org->id, 'tournament_id' => $tournament->id,
            'sport_id' => $sport->id, 'sport_category_id' => $cat->id, 'name' => 'Badminton - Singles',
        ]);

        $home = Participant::factory()->create(['organization_id' => $org->id, 'session_id' => $session->id, 'name' => 'Fakulti Kejuruteraan']);
        $away = Participant::factory()->create(['organization_id' => $org->id, 'session_id' => $session->id, 'name' => 'Fakulti Teknologi']);

        $homeUser = User::factory()->create(['participant_id' => $home->id]);
        $awayUser = User::factory()->create(['participant_id' => $away->id]);

        $match = Fixture::factory()->create([
            'organization_id' => $org->id, 'event_id' => $event->id,
            'home_participant_id' => $home->id, 'away_participant_id' => $away->id, 'match_number' => 3,
        ]);

        return compact('org', 'match', 'home', 'away', 'homeUser', 'awayUser');
    }

    public function test_participants_are_notified_when_result_is_recorded(): void
    {
        Notification::fake();

        $data = $this->seedMatchWithParticipantUsers();
        $super = $this->createSuperAdmin(['organization_id' => $data['org']->id]);

        $this->actingAs($super)->post(route('results.store'), [
            'match_id' => $data['match']->id,
            'score_home' => 3,
            'score_away' => 1,
            'winner_participant_id' => $data['home']->id,
        ])->assertRedirect(route('results.index'));

        Notification::assertSentTo($data['homeUser'], MatchResultNotification::class,
            fn ($n) => $n->action === 'recorded' && str_contains($n->result->match->event->name, 'Badminton'));
        Notification::assertSentTo($data['awayUser'], MatchResultNotification::class,
            fn ($n) => $n->action === 'recorded');
    }

    public function test_participants_are_notified_when_result_is_updated(): void
    {
        Notification::fake();

        $data = $this->seedMatchWithParticipantUsers();
        $super = $this->createSuperAdmin(['organization_id' => $data['org']->id]);

        $result = $this->actingAs($super)->post(route('results.store'), [
            'match_id' => $data['match']->id, 'score_home' => 2, 'score_away' => 0,
        ])->assertRedirect(route('results.index'));

        $resultRow = Result::where('match_id', $data['match']->id)->first();

        $this->actingAs($super)->put(route('results.update', $resultRow), [
            'match_id' => $data['match']->id, 'score_home' => 2, 'score_away' => 1,
        ])->assertRedirect(route('results.index'));

        Notification::assertSentTo($data['homeUser'], MatchResultNotification::class,
            fn ($n) => $n->action === 'updated');
        Notification::assertSentTo($data['awayUser'], MatchResultNotification::class,
            fn ($n) => $n->action === 'updated');
    }

    public function test_all_users_of_both_participants_are_notified_when_result_is_recorded(): void
    {
        Notification::fake();

        $data = $this->seedMatchWithParticipantUsers();
        $homeDean = User::factory()->create(['participant_id' => $data['home']->id]);
        $awayDean = User::factory()->create(['participant_id' => $data['away']->id]);
        $super = $this->createSuperAdmin(['organization_id' => $data['org']->id]);

        $this->actingAs($super)->post(route('results.store'), [
            'match_id' => $data['match']->id,
            'score_home' => 3,
            'score_away' => 1,
        ])->assertRedirect(route('results.index'));

        Notification::assertSentTo($data['homeUser'], MatchResultNotification::class);
        Notification::assertSentTo($data['awayUser'], MatchResultNotification::class);
        Notification::assertSentTo($homeDean, MatchResultNotification::class);
        Notification::assertSentTo($awayDean, MatchResultNotification::class);
        Notification::assertCount(4, MatchResultNotification::class);
    }

    public function test_participants_are_notified_when_result_is_removed(): void
    {
        Notification::fake();

        $data = $this->seedMatchWithParticipantUsers();
        $super = $this->createSuperAdmin(['organization_id' => $data['org']->id]);

        $result = Result::factory()->create([
            'organization_id' => $data['org']->id, 'match_id' => $data['match']->id,
            'score_home' => 1, 'score_away' => 0,
        ]);

        $this->actingAs($super)->delete(route('results.destroy', $result))
            ->assertRedirect(route('results.index'));

        Notification::assertSentTo($data['homeUser'], MatchResultNotification::class,
            fn ($n) => $n->action === 'removed');
        Notification::assertSentTo($data['awayUser'], MatchResultNotification::class,
            fn ($n) => $n->action === 'removed');
    }
}
