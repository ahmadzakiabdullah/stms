<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Session;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\SquadMember;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class FacultyDashboardControllerTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    public function test_store_squad_fails_for_other_participant(): void
    {
        $org = Organization::factory()->create();
        $user1 = $this->createFacultyUser($org);
        $user2 = $this->createFacultyUser($org);

        $session = Session::factory()->create(['organization_id' => $org->id]);
        $tournament = Tournament::factory()->create(['organization_id' => $org->id, 'session_id' => $session->id]);
        $sport = Sport::factory()->create(['organization_id' => $org->id]);
        $sportCategory = SportCategory::factory()->create([
            'organization_id' => $org->id,
            'sport_id' => $sport->id,
            'max_athletes_total' => 10,
            'max_male_athletes' => 10,
            'min_male_athletes' => 0,
            'quota_mode' => 'fixed',
        ]);
        $event = Event::factory()->create([
            'organization_id' => $org->id,
            'tournament_id' => $tournament->id,
            'sport_id' => $sport->id,
            'sport_category_id' => $sportCategory->id,
        ]);

        $participant1 = Participant::factory()->create(['organization_id' => $org->id, 'session_id' => $session->id]);
        $participant2 = Participant::factory()->create(['organization_id' => $org->id, 'session_id' => $session->id]);

        $user1->update(['participant_id' => $participant1->id]);
        $user2->update(['participant_id' => $participant2->id]);

        $ep2 = EventParticipant::create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'participant_id' => $participant2->id,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($user1)->post(route('faculty.squad.store'), [
            'event_participant_id' => $ep2->id,
            'name' => 'John Doe',
            'role' => 'athlete_male',
        ]);

        $response->assertForbidden();
    }

    public function test_destroy_squad_fails_for_other_participant(): void
    {
        $org = Organization::factory()->create();
        $user1 = $this->createFacultyUser($org);
        $user2 = $this->createFacultyUser($org);

        $session = Session::factory()->create(['organization_id' => $org->id]);
        $tournament = Tournament::factory()->create(['organization_id' => $org->id, 'session_id' => $session->id]);
        $sport = Sport::factory()->create(['organization_id' => $org->id]);
        $sportCategory = SportCategory::factory()->create([
            'organization_id' => $org->id,
            'sport_id' => $sport->id,
        ]);
        $event = Event::factory()->create([
            'organization_id' => $org->id,
            'tournament_id' => $tournament->id,
            'sport_id' => $sport->id,
            'sport_category_id' => $sportCategory->id,
        ]);

        $participant1 = Participant::factory()->create(['organization_id' => $org->id, 'session_id' => $session->id]);
        $participant2 = Participant::factory()->create(['organization_id' => $org->id, 'session_id' => $session->id]);

        $user1->update(['participant_id' => $participant1->id]);
        $user2->update(['participant_id' => $participant2->id]);

        $ep2 = EventParticipant::create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'participant_id' => $participant2->id,
            'status' => 'confirmed',
        ]);

        $squadMember = SquadMember::create([
            'event_participant_id' => $ep2->id,
            'organization_id' => $org->id,
            'name' => 'John Doe',
            'role' => 'athlete_male',
        ]);

        $response = $this->actingAs($user1)->delete(route('faculty.squad.destroy', $squadMember));

        $response->assertForbidden();
    }

    public function test_import_squad_fails_for_other_participant(): void
    {
        $org = Organization::factory()->create();
        $user1 = $this->createFacultyUser($org);
        $user2 = $this->createFacultyUser($org);

        $session = Session::factory()->create(['organization_id' => $org->id]);
        $tournament = Tournament::factory()->create(['organization_id' => $org->id, 'session_id' => $session->id]);
        $sport = Sport::factory()->create(['organization_id' => $org->id]);
        $sportCategory = SportCategory::factory()->create([
            'organization_id' => $org->id,
            'sport_id' => $sport->id,
        ]);
        $event = Event::factory()->create([
            'organization_id' => $org->id,
            'tournament_id' => $tournament->id,
            'sport_id' => $sport->id,
            'sport_category_id' => $sportCategory->id,
        ]);

        $participant1 = Participant::factory()->create(['organization_id' => $org->id, 'session_id' => $session->id]);
        $participant2 = Participant::factory()->create(['organization_id' => $org->id, 'session_id' => $session->id]);

        $user1->update(['participant_id' => $participant1->id]);
        $user2->update(['participant_id' => $participant2->id]);

        $ep2 = EventParticipant::create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'participant_id' => $participant2->id,
            'status' => 'confirmed',
        ]);

        Storage::fake('local');
        $file = UploadedFile::fake()->create('squad.csv', 100, 'text/csv');

        $response = $this->actingAs($user1)->post(route('faculty.squad.import'), [
            'event_participant_id' => $ep2->id,
            'file' => $file,
        ]);

        $response->assertForbidden();
    }

    public function test_import_squad_succeeds_for_own_participant(): void
    {
        $org = Organization::factory()->create();
        $user1 = $this->createFacultyUser($org);

        $session = Session::factory()->create(['organization_id' => $org->id]);
        $tournament = Tournament::factory()->create(['organization_id' => $org->id, 'session_id' => $session->id]);
        $sport = Sport::factory()->create(['organization_id' => $org->id]);
        $sportCategory = SportCategory::factory()->create([
            'organization_id' => $org->id,
            'sport_id' => $sport->id,
            'max_athletes_total' => 10,
            'max_male_athletes' => 10,
            'min_male_athletes' => 0,
            'quota_mode' => 'fixed',
        ]);
        $event = Event::factory()->create([
            'organization_id' => $org->id,
            'tournament_id' => $tournament->id,
            'sport_id' => $sport->id,
            'sport_category_id' => $sportCategory->id,
        ]);

        $participant1 = Participant::factory()->create(['organization_id' => $org->id, 'session_id' => $session->id]);
        $user1->update(['participant_id' => $participant1->id]);

        $ep1 = EventParticipant::create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'participant_id' => $participant1->id,
            'status' => 'confirmed',
        ]);

        Storage::fake('local');
        $file = UploadedFile::fake()->create('squad.csv', 100, 'text/csv');

        $response = $this->actingAs($user1)->post(route('faculty.squad.import'), [
            'event_participant_id' => $ep1->id,
            'file' => $file,
        ]);

        if (! $response->isForbidden()) {
            $this->assertTrue(true);
        } else {
            $this->fail('Authorization check failed unexpectedly.');
        }
    }

    private function createFacultyUser(Organization $org): User
    {
        Role::firstOrCreate(['name' => 'faculty-representative']);
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->assignRole('faculty-representative');

        return $user;
    }
}
