<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Session;
use App\Models\Sport;
use App\Models\SportDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class DocumentIsolationTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    public function test_sport_document_upload_uses_an_organization_and_session_scoped_path(): void
    {
        Storage::fake('public');
        $organization = Organization::factory()->create();
        $admin = $this->createOrgAdmin($organization);
        $session = Session::factory()->create(['organization_id' => $organization->id]);
        $sport = Sport::factory()->create(['organization_id' => $organization->id]);

        $response = $this->actingAs($admin)->post(route('sports.documents.store', $sport), [
            'title' => 'Competition Rules',
            'session_id' => $session->id,
            'document' => UploadedFile::fake()->create('rules.pdf', 10, 'application/pdf'),
        ]);

        $response->assertRedirect();
        $document = SportDocument::query()->where('sport_id', $sport->id)->firstOrFail();
        $expectedPrefix = 'documents/'.$organization->id.'/'.$session->id.'/sports/'.$sport->id.'/';

        $this->assertStringStartsWith($expectedPrefix, $document->file_path);
        Storage::disk('public')->assertExists($document->file_path);
    }

    public function test_sport_document_cannot_select_a_file_from_another_session_or_organization(): void
    {
        Storage::fake('public');
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        $adminA = $this->createOrgAdmin($organizationA);
        $sessionA = Session::factory()->create(['organization_id' => $organizationA->id]);
        $otherSessionA = Session::factory()->create(['organization_id' => $organizationA->id]);
        $sessionB = Session::factory()->create(['organization_id' => $organizationB->id]);
        $sportA = Sport::factory()->create(['organization_id' => $organizationA->id]);

        $sameOrgOtherSessionPath = 'documents/'.$organizationA->id.'/'.$otherSessionA->id.'/sports/'.$sportA->id.'/other.pdf';
        $otherOrgPath = 'documents/'.$organizationB->id.'/'.$sessionB->id.'/sports/foreign-sport/foreign.pdf';
        Storage::disk('public')->put($sameOrgOtherSessionPath, 'other session');
        Storage::disk('public')->put($otherOrgPath, 'other organization');

        foreach ([$sameOrgOtherSessionPath, $otherOrgPath] as $filePath) {
            $response = $this->actingAs($adminA)->post(route('sports.documents.select', $sportA), [
                'title' => 'Foreign file',
                'session_id' => $sessionA->id,
                'file_path' => $filePath,
            ]);

            $response->assertStatus(422);
        }

        $this->assertDatabaseMissing('sport_documents', ['title' => 'Foreign file']);
    }

    public function test_session_available_files_are_limited_to_the_session_directory(): void
    {
        Storage::fake('public');
        $organization = Organization::factory()->create();
        $admin = $this->createOrgAdmin($organization);
        $session = Session::factory()->create(['organization_id' => $organization->id]);
        $allowedPath = 'documents/'.$organization->id.'/'.$session->id.'/general/allowed.pdf';
        $legacyGlobalPath = 'documents/'.$session->start_date->format('Y').'/general/not-tenant-scoped.pdf';
        Storage::disk('public')->put($allowedPath, 'allowed');
        Storage::disk('public')->put($legacyGlobalPath, 'must not be listed');

        $response = $this->actingAs($admin)->get(route('sessions.documents.available', $session));

        $response->assertOk()
            ->assertJsonPath('files.0.path', $allowedPath)
            ->assertJsonMissing(['path' => $legacyGlobalPath]);
    }
}
