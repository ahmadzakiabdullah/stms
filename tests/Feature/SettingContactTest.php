<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class SettingContactTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    public function test_org_admin_can_update_only_their_organizations_public_contact_settings(): void
    {
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $admin = $this->createOrgAdmin($organization);
        Setting::create([
            'organization_id' => $otherOrganization->id,
            'key' => 'secretariat_email',
            'value' => 'other@example.test',
        ]);

        $payload = $this->validPayload();

        $this->actingAs($admin)
            ->post(route('settings.update'), $payload)
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        foreach (array_filter($payload, fn (string $key): bool => str_starts_with($key, 'secretariat_'), ARRAY_FILTER_USE_KEY) as $key => $value) {
            $this->assertDatabaseHas('settings', [
                'organization_id' => $organization->id,
                'key' => $key,
                'value' => $value,
            ]);
        }

        $this->assertDatabaseHas('settings', [
            'organization_id' => $otherOrganization->id,
            'key' => 'secretariat_email',
            'value' => 'other@example.test',
        ]);

        $this->actingAs($admin)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Index')
                ->where('settings.secretariat_email', 'pusatsukan@utem.edu.my')
                ->where('settings.secretariat_phone', '+606-2292326')
                ->where('settings.secretariat_facebook_url', 'https://www.facebook.com/pusatsukanutem')
                ->where('settings.secretariat_instagram_url', 'https://www.instagram.com/pusatsukanutem')
                ->where('settings.secretariat_tiktok_url', 'https://www.tiktok.com/pusatsukanutem')
                ->where('settings.secretariat_youtube_url', 'https://www.youtube.com/pusatsukanutem'));
    }

    public function test_org_admin_can_upload_dark_mode_inverse_logo(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->createOrgAdmin($organization);

        $this->actingAs($admin)
            ->post(route('settings.update'), array_merge($this->validPayload(), [
                'inverse_logo' => UploadedFile::fake()->image('inverse-logo.png', 200, 80),
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $url = Setting::where('organization_id', $organization->id)
            ->where('key', 'inverse_logo_url')
            ->value('value');

        $this->assertNotNull($url);
        $storageBasePath = parse_url((string) config('filesystems.disks.public.url'), PHP_URL_PATH);
        $relativePath = (string) Str::after((string) parse_url($url, PHP_URL_PATH), $storageBasePath.'/');
        Storage::disk('public')->assertExists($relativePath);

        $this->actingAs($admin)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Index')
                ->where('settings.inverse_logo_url', $url));
    }

    public function test_public_contact_settings_reject_unsafe_urls_and_invalid_contact_values(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->createOrgAdmin($organization);

        $this->actingAs($admin)
            ->post(route('settings.update'), array_merge($this->validPayload(), [
                'secretariat_email' => 'not-an-email',
                'secretariat_phone' => 'call-me-now',
                'secretariat_facebook_url' => 'javascript:alert(1)',
                'secretariat_instagram_url' => 'ftp://example.test/profile',
            ]))
            ->assertSessionHasErrors([
                'secretariat_email',
                'secretariat_phone',
                'secretariat_facebook_url',
                'secretariat_instagram_url',
            ]);

        $this->assertDatabaseMissing('settings', [
            'organization_id' => $organization->id,
            'key' => 'secretariat_facebook_url',
        ]);
    }

    private function validPayload(): array
    {
        return [
            'app_name' => 'SAF UTeM',
            'secretariat_address' => "Pusat Sukan\nUniversiti Teknikal Malaysia Melaka",
            'secretariat_email' => 'pusatsukan@utem.edu.my',
            'secretariat_phone' => '+606-2292326',
            'secretariat_facebook_url' => 'https://www.facebook.com/pusatsukanutem',
            'secretariat_instagram_url' => 'https://www.instagram.com/pusatsukanutem',
            'secretariat_tiktok_url' => 'https://www.tiktok.com/pusatsukanutem',
            'secretariat_youtube_url' => 'https://www.youtube.com/pusatsukanutem',
            'public_theme_dark' => '#071B33',
            'public_theme_primary' => '#0057A8',
            'public_theme_accent' => '#20B8E6',
            'public_theme_highlight' => '#F4B942',
            'public_theme_background' => '#F4F7FA',
            'public_theme_text' => '#102A43',
        ];
    }
}
