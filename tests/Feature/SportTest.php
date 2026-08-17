<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Sport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class SportTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    public function test_non_super_admin_only_sees_own_organization_sports_via_global_scope(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $sportA = Sport::factory()->create(['organization_id' => $orgA->id, 'name' => 'Football A']);
        $sportB = Sport::factory()->create(['organization_id' => $orgB->id, 'name' => 'Football B']);

        $userA = $this->createOrgAdmin($orgA);

        $response = $this->actingAs($userA)->get(route('sports.index'));

        $response->assertOk();
        $sports = $response->viewData('page')['props']['sports']['data'] ?? [];

        // With global scope, should only see orgA's sports
        $names = collect($sports)->pluck('name')->toArray();
        $this->assertContains('Football A', $names);
        $this->assertNotContains('Football B', $names);
    }

    public function test_super_admin_sees_all_sports(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        Sport::factory()->create(['organization_id' => $orgA->id]);
        Sport::factory()->create(['organization_id' => $orgB->id]);

        $super = $this->createSuperAdmin();

        $response = $this->actingAs($super)->get(route('sports.index'));

        $response->assertOk();
        $sports = $response->viewData('page')['props']['sports']['data'] ?? [];
        $this->assertGreaterThanOrEqual(2, count($sports));
    }

    public function test_non_super_admin_cannot_update_or_delete_sport_in_other_org(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $userA = $this->createStaffUser($orgA);

        $sportB = Sport::factory()->create(['organization_id' => $orgB->id]);

        $response = $this->actingAs($userA)->put(route('sports.update', $sportB), [
            'name' => 'Hacked',
            'slug' => 'hacked',
        ]);

        $response->assertNotFound();

        $response = $this->actingAs($userA)->delete(route('sports.destroy', $sportB));
        $response->assertNotFound();
    }

    public function test_org_admin_can_update_and_delete_own_sport(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        $sport = Sport::factory()->create(['organization_id' => $org->id]);

        $response = $this->actingAs($admin)->put(route('sports.update', $sport), [
            'name' => 'Updated Sport',
            'slug' => $sport->slug,
        ]);

        $response->assertRedirect(route('sports.index'));
        $this->assertDatabaseHas('sports', ['name' => 'Updated Sport']);

        $response = $this->actingAs($admin)->delete(route('sports.destroy', $sport));
        $response->assertRedirect(route('sports.index'));
        $this->assertSoftDeleted('sports', ['id' => $sport->id]);
    }

    public function test_duplicate_slug_rejected_within_same_org(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        Sport::factory()->create(['organization_id' => $org->id, 'slug' => 'same-slug']);

        $response = $this->actingAs($admin)->post(route('sports.store'), [
            'name' => 'Another Sport',
            'slug' => 'same-slug',
        ]);
        $response->assertSessionHasErrors('slug');
    }

    public function test_duplicate_name_rejected_within_same_org(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        Sport::factory()->create(['organization_id' => $org->id, 'name' => 'Basketball', 'slug' => 'basketball']);

        $response = $this->actingAs($admin)->post(route('sports.store'), [
            'name' => 'Basketball',
            'slug' => 'basketball-2',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_duplicate_slug_allowed_across_orgs(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        Sport::factory()->create(['organization_id' => $orgA->id, 'slug' => 'same-slug']);
        $adminB = $this->createOrgAdmin($orgB);

        $response = $this->actingAs($adminB)->post(route('sports.store'), [
            'name' => 'Same Slug Sport',
            'slug' => 'same-slug',
        ]);
        $this->assertContains($response->status(), [302, 201]);
    }

    public function test_sport_can_be_created_with_external_icon_url(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);

        $this->actingAs($admin)->post(route('sports.store'), [
            'name' => 'Badminton',
            'slug' => 'badminton',
            'icon' => 'https://cdn.simpleicons.org/badminton',
        ])->assertRedirect(route('sports.index'));

        $this->assertDatabaseHas('sports', [
            'name' => 'Badminton',
            'icon' => 'https://cdn.simpleicons.org/badminton',
        ]);
    }

    public function test_sport_can_be_created_with_uploaded_icon_file(): void
    {
        Storage::fake('public');

        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);

        $this->actingAs($admin)->post(route('sports.store'), [
            'name' => 'Football',
            'slug' => 'football',
            'icon_file' => UploadedFile::fake()->image('football.png'),
        ])->assertRedirect(route('sports.index'));

        $sport = Sport::where('slug', 'football')->first();

        $this->assertNotNull($sport);
        $this->assertStringStartsWith('/storage/sport-icons/', $sport->icon);
        Storage::disk('public')->assertExists(substr($sport->icon, strlen('/storage/')));
    }

    public function test_sport_icon_upload_accepts_form_data_boolean_strings(): void
    {
        Storage::fake('public');

        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);

        $this->actingAs($admin)->post(route('sports.store'), [
            'name' => 'Inactive Football',
            'slug' => 'inactive-football',
            'is_active' => 'false',
            'icon_file' => UploadedFile::fake()->image('football.png'),
        ])->assertRedirect(route('sports.index'));

        $this->assertDatabaseHas('sports', [
            'slug' => 'inactive-football',
            'is_active' => false,
        ]);
    }

    public function test_updating_icon_file_replaces_and_deletes_previous_icon(): void
    {
        Storage::fake('public');

        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        $sport = Sport::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($admin)->put(route('sports.update', $sport), [
            'name' => $sport->name,
            'slug' => $sport->slug,
            'icon_file' => UploadedFile::fake()->image('icon-v1.png'),
        ])->assertRedirect(route('sports.index'));

        $sport->refresh();
        $firstIconPath = substr($sport->icon, strlen('/storage/'));
        Storage::disk('public')->assertExists($firstIconPath);

        $this->actingAs($admin)->put(route('sports.update', $sport), [
            'name' => $sport->name,
            'slug' => $sport->slug,
            'icon_file' => UploadedFile::fake()->image('icon-v2.png'),
        ])->assertRedirect(route('sports.index'));

        $sport->refresh();

        $this->assertNotEquals($firstIconPath, substr($sport->icon, strlen('/storage/')));
        Storage::disk('public')->assertMissing($firstIconPath);
        Storage::disk('public')->assertExists(substr($sport->icon, strlen('/storage/')));
    }

    public function test_external_icon_url_is_not_deleted_when_replaced(): void
    {
        Storage::fake('public');

        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        $sport = Sport::factory()->create([
            'organization_id' => $org->id,
            'icon' => 'https://cdn.simpleicons.org/football',
        ]);

        $this->actingAs($admin)->put(route('sports.update', $sport), [
            'name' => $sport->name,
            'slug' => $sport->slug,
            'icon_file' => UploadedFile::fake()->image('icon-new.png'),
        ])->assertRedirect(route('sports.index'));

        $sport->refresh();

        $this->assertStringStartsWith('/storage/sport-icons/', $sport->icon);
        Storage::disk('public')->assertExists(substr($sport->icon, strlen('/storage/')));
    }
}
