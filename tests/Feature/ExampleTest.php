<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_the_public_page_receives_uploaded_branding(): void
    {
        $organization = Organization::factory()->create(['is_active' => true]);
        config(['app.public_org_slug' => $organization->slug]);
        Setting::create([
            'organization_id' => $organization->id,
            'key' => 'logo_url',
            'value' => '/storage/settings/logo.svg',
        ]);
        Setting::create([
            'organization_id' => $organization->id,
            'key' => 'favicon_url',
            'value' => '/storage/settings/favicon.png',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('<link rel="icon" href="/storage/settings/favicon.png">', false)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Index')
                ->where('settings.logo_url', '/storage/settings/logo.svg'));
    }

    public function test_guest_favicon_is_selected_from_the_configured_public_organization_only(): void
    {
        $publicOrganization = Organization::factory()->create(['slug' => 'public-org', 'is_active' => true]);
        $otherOrganization = Organization::factory()->create(['slug' => 'other-org', 'is_active' => true]);
        Setting::create(['organization_id' => $publicOrganization->id, 'key' => 'favicon_url', 'value' => '/public-favicon.png']);
        Setting::create(['organization_id' => $otherOrganization->id, 'key' => 'favicon_url', 'value' => '/other-favicon.png']);
        config(['app.public_org_slug' => $publicOrganization->slug]);

        $this->get('/')
            ->assertOk()
            ->assertSee('<link rel="icon" href="/public-favicon.png">', false)
            ->assertDontSee('/other-favicon.png');
    }

    public function test_public_shell_is_self_hosted_and_has_basic_search_metadata(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<meta name="description"', false)
            ->assertSee('<link rel="canonical"', false)
            ->assertSee('public.contact')
            ->assertDontSee('fonts.bunny.net');

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('public.index'), false)
            ->assertSee(route('public.contact'), false);
    }
}
