<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Session;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class ParticipantImportTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    private function validCsv(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('participants.csv', implode("\n", [
            'name,participant_type,team_name,email,phone,status,is_active,slug',
            'Fakulti Kejuruteraan Elektronik,team,FKE,fke@example.com,0123456789,registered,true,',
            'Ahmad bin Ali,individual,,ahmad@example.com,,confirmed,true,',
        ]));
    }

    public function test_import_preview_parses_file_and_stages_rows_without_creating(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createOrgAdmin($org);

        $response = $this->actingAs($user)
            ->post(route('participants.import.preview'), ['file' => $this->validCsv()])
            ->assertSessionHas('participant_import_preview');

        $preview = $response->getSession()->get('participant_import_preview');
        $this->assertSame(2, $preview['valid_count']);
        $this->assertSame(0, $preview['error_count']);
        $this->assertCount(2, $preview['rows']);
        $this->assertNotEmpty($preview['token']);

        $this->assertDatabaseCount('participants', 0);
        $this->assertDatabaseMissing('participants', ['name' => 'Ahmad bin Ali']);
    }

    public function test_import_preview_reports_invalid_rows(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createOrgAdmin($org);
        Participant::factory()->create(['organization_id' => $org->id, 'name' => 'Existing Kontinjen']);

        $file = UploadedFile::fake()->createWithContent('participants.csv', implode("\n", [
            'name,participant_type,team_name,email,phone,status,is_active,slug',
            'Existing Kontinjen,team,,, ,registered,true,',
            'No Name,duplicate,,, ,registered,true,',
            'Bad Type Row,invalid,,, ,registered,true,',
            'Valid Row,team,,, ,registered,true,',
        ]));

        $response = $this->actingAs($user)
            ->post(route('participants.import.preview'), ['file' => $file])
            ->assertSessionHas('participant_import_preview');

        $preview = $response->getSession()->get('participant_import_preview');
        $this->assertSame(1, $preview['valid_count']);
        $this->assertSame(3, $preview['error_count']);
        $this->assertStringContainsString('already exists', implode(' ', $preview['errors']));
        $this->assertStringContainsString("participant_type 'invalid'", implode(' ', $preview['errors']));

        $this->assertDatabaseCount('participants', 1);
    }

    public function test_import_preview_rejects_session_from_another_organization(): void
    {
        $org = Organization::factory()->create();
        $otherOrg = Organization::factory()->create();
        $otherSession = Session::factory()->create(['organization_id' => $otherOrg->id]);
        $user = $this->createOrgAdmin($org);

        $this->actingAs($user)
            ->post(route('participants.import.preview'), [
                'file' => $this->validCsv(),
                'session_id' => $otherSession->id,
            ])
            ->assertSessionHasErrors('session_id');
    }

    public function test_import_confirm_creates_participants_from_staged_preview(): void
    {
        $org = Organization::factory()->create();
        $session = Session::factory()->create(['organization_id' => $org->id]);
        $user = $this->createOrgAdmin($org);

        $response = $this->actingAs($user)
            ->post(route('participants.import.preview'), [
                'file' => $this->validCsv(),
                'session_id' => $session->id,
            ]);

        $token = $response->getSession()->get('participant_import_preview')['token'];

        $this->actingAs($user)
            ->post(route('participants.import.confirm'), ['token' => $token])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('participants', 2);
        $this->assertDatabaseHas('participants', [
            'organization_id' => $org->id,
            'session_id' => $session->id,
            'name' => 'Fakulti Kejuruteraan Elektronik',
            'participant_type' => 'team',
            'email' => 'fke@example.com',
        ]);
        $this->assertDatabaseHas('participants', [
            'organization_id' => $org->id,
            'session_id' => $session->id,
            'name' => 'Ahmad bin Ali',
            'participant_type' => 'individual',
            'status' => 'confirmed',
        ]);

        $summary = Activity::query()
            ->where('event', 'bulk_imported')
            ->where('subject_type', Organization::class)
            ->where('subject_id', $org->id)
            ->firstOrFail();

        $this->assertSame('participants.import_confirm', $summary->properties['bulk_action']);
        $this->assertSame(2, $summary->properties['selected_count']);
        $this->assertSame(2, $summary->properties['created_count']);
        $this->assertSame($session->id, $summary->properties['session_id']);
        $this->assertNotEmpty($summary->properties['bulk_action_id']);
    }

    public function test_import_confirm_rejects_expired_or_unknown_token(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createOrgAdmin($org);

        $this->actingAs($user)
            ->post(route('participants.import.confirm'), ['token' => Str::uuid()->toString()])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('participants', 0);
    }

    public function test_import_confirm_rolls_back_when_a_row_fails(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createOrgAdmin($org);

        $row = ['row_number' => 2, 'data' => ['email' => 'orphan@example.com']];
        $key = 'participants_import_'.$user->id.'_'.($token = Str::uuid()->toString());
        Cache::put($key, [
            'organization_id' => $org->id,
            'session_id' => null,
            'rows' => [$row],
        ], now()->addMinutes(30));

        $this->actingAs($user)
            ->post(route('participants.import.confirm'), ['token' => $token])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('participants', 0);
    }

    public function test_import_template_download_works(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createOrgAdmin($org);

        $response = $this->actingAs($user)->get(route('participants.import.template'));
        $response->assertOk();
        $this->assertStringContainsString('name', $response->streamedContent());
        $this->assertStringContainsString('participant_type', $response->streamedContent());
    }

    public function test_import_preview_requires_create_permission(): void
    {
        $org = Organization::factory()->create();
        $user = $this->createStaffUser($org);

        $this->actingAs($user)
            ->post(route('participants.import.preview'), ['file' => $this->validCsv()])
            ->assertForbidden();
    }
}
