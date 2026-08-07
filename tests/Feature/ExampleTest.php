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
}
