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
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
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

    public function test_import_squad_catches_validation_exception(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createFacultyUser($org);

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

        $participant = Participant::factory()->create(['organization_id' => $org->id, 'session_id' => $session->id]);
        $user->update(['participant_id' => $participant->id]);

        $ep = EventParticipant::create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'status' => 'confirmed',
        ]);

        $csvContent = "name,role,ic_passport,phone\nJohn Doe,,,\n";
        $file = UploadedFile::fake()->createWithContent('squad.csv', $csvContent);

        $validator = Validator::make([], []);
        $validator->errors()->add('file', 'name and role are required');
        $validationException = new ValidationException($validator);

        Excel::shouldReceive('import')
            ->once()
            ->andThrow($validationException);

        $response = $this->actingAs($user)
            ->from(route('faculty.dashboard'))
            ->post(route('faculty.squad.import'), [
                'event_participant_id' => $ep->id,
                'file' => $file,
            ]);

        $response->assertRedirect(route('faculty.dashboard'));
        $response->assertSessionHasErrors(['file' => 'name and role are required']);
    }

    public function test_import_squad_catches_throwable(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createFacultyUser($org);

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

        $participant = Participant::factory()->create(['organization_id' => $org->id, 'session_id' => $session->id]);
        $user->update(['participant_id' => $participant->id]);

        $ep = EventParticipant::create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'status' => 'confirmed',
        ]);

        $csvContent = "name,role,ic_passport,phone\nJohn Doe,athlete_male,,\n";
        $file = UploadedFile::fake()->createWithContent('squad.csv', $csvContent);

        Excel::shouldReceive('import')
            ->once()
            ->andThrow(new \Exception('Mocked Excel error'));

        $response = $this->actingAs($user)->post(route('faculty.squad.import'), [
            'event_participant_id' => $ep->id,
            'file' => $file,
        ]);

        $response->assertRedirect(route('faculty.dashboard'));
        $response->assertSessionHas('error', 'Failed to import file: Mocked Excel error');
    }

    private function createFacultyUser(Organization $org): User
    {
        Role::firstOrCreate(['name' => 'faculty-representative']);
        $user = User::factory()->create(['organization_id' => $org->id]);
        $user->assignRole('faculty-representative');

        return $user;
    }
}
