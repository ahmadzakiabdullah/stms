<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Session;
use App\Models\Setting;
use App\Models\Tournament;
use App\Services\PublicPortalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'api.open-meteo.com/*' => Http::response(['current' => ['temperature_2m' => 30.2]]),
        ]);
    }

    public function test_public_portal_displays_only_active_session_data_without_contact_details(): void
    {
        $organization = Organization::factory()->create(['is_active' => true]);
        $session = Session::factory()->create(['organization_id' => $organization->id, 'name' => 'Sukan Antara Fakulti 2026', 'is_active' => true]);
        config(['app.public_org_slug' => $organization->slug, 'app.public_session_slug' => $session->slug]);
        $tournament = Tournament::factory()->forSession($session)->create();
        $event = Event::factory()->forTournament($tournament)->create();
        $home = Participant::factory()->create(['organization_id' => $organization->id, 'session_id' => $session->id, 'name' => 'Fakulti Teknologi Maklumat', 'email' => 'rahsia@example.test', 'inverse_logo_path' => 'logos/ftmk-inverse.svg', 'is_active' => true]);
        $away = Participant::factory()->create(['organization_id' => $organization->id, 'session_id' => $session->id, 'name' => 'Fakulti Kejuruteraan', 'is_active' => true]);
        Fixture::factory()->scheduled()->create(['organization_id' => $organization->id, 'event_id' => $event->id, 'home_participant_id' => $home->id, 'away_participant_id' => $away->id, 'scheduled_at' => now()->addDay()]);

        $this->get('/')->assertOk()->assertDontSee('rahsia@example.test')->assertInertia(fn (Assert $page) => $page
            ->component('Public/Index')->where('competition.name', 'Sukan Antara Fakulti 2026')
            ->where('stats.faculties', 2)->has('upcoming', 1)
            ->where('upcoming.0.home.name', 'Fakulti Teknologi Maklumat')
            ->where('upcoming.0.home.inverse_logo_url', $home->inverse_logo_url)
            ->where('weather.location', 'Durian Tunggal')
            ->where('weather.temperature', 30));
    }

    public function test_index_php_redirects_to_the_canonical_public_url(): void
    {
        $this->get('/index.php')->assertRedirect('/')->assertStatus(301);
    }

    public function test_public_matches_page_displays_configured_session_fixtures(): void
    {
        $organization = Organization::factory()->create(['is_active' => true]);
        $session = Session::factory()->create(['organization_id' => $organization->id, 'is_active' => true]);
        config(['app.public_org_slug' => $organization->slug, 'app.public_session_slug' => $session->slug]);
        $tournament = Tournament::factory()->forSession($session)->create();
        $event = Event::factory()->forTournament($tournament)->create();
        $home = Participant::factory()->create(['organization_id' => $organization->id, 'session_id' => $session->id, 'name' => 'Fakulti A']);
        $away = Participant::factory()->create(['organization_id' => $organization->id, 'session_id' => $session->id, 'name' => 'Fakulti B']);
        Fixture::factory()->scheduled()->create(['organization_id' => $organization->id, 'event_id' => $event->id, 'home_participant_id' => $home->id, 'away_participant_id' => $away->id]);

        $this->get(route('public.matches'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/Matches')->has('upcoming', 1)->where('upcoming.0.home.name', 'Fakulti A')->where('upcoming.0.away.name', 'Fakulti B'));
    }

    public function test_public_directory_pages_render_without_authentication(): void
    {
        $organization = Organization::factory()->create(['is_active' => true]);
        $session = Session::factory()->create(['organization_id' => $organization->id, 'is_active' => true]);
        config(['app.public_org_slug' => $organization->slug, 'app.public_session_slug' => $session->slug]);

        foreach (['public.sports' => 'sports', 'public.schedule' => 'schedule', 'public.results' => 'results', 'public.faculties' => 'faculties', 'public.venues' => 'venues', 'public.live' => 'live'] as $routeName => $section) {
            $this->get(route($routeName))->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('Public/Directory')->where('section', $section));
        }
    }

    public function test_iis_portal_mount_path_renders_without_a_self_redirect(): void
    {
        $this->get('/portal')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/Index'));

        $this->get('/portal/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/Index'));
    }

    public function test_completed_fixtures_without_scheduled_at_still_appear_in_results(): void
    {
        $organization = Organization::factory()->create(['is_active' => true]);
        $session = Session::factory()->create(['organization_id' => $organization->id, 'name' => 'Sukan Antara Fakulti 2026', 'is_active' => true]);
        config(['app.public_org_slug' => $organization->slug, 'app.public_session_slug' => $session->slug]);
        $tournament = Tournament::factory()->forSession($session)->create();
        $event = Event::factory()->forTournament($tournament)->create();
        $home = Participant::factory()->create(['organization_id' => $organization->id, 'session_id' => $session->id, 'name' => 'Fakulti A', 'is_active' => true]);
        $away = Participant::factory()->create(['organization_id' => $organization->id, 'session_id' => $session->id, 'name' => 'Fakulti B', 'is_active' => true]);
        Fixture::factory()->completed()->create(['organization_id' => $organization->id, 'event_id' => $event->id, 'home_participant_id' => $home->id, 'away_participant_id' => $away->id, 'scheduled_at' => null]);

        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/Index')
            ->where('stats.completed_matches', 1)
            ->where('stats.total_matches', 1)
            ->has('results', 1)
            ->where('results.0.home.name', 'Fakulti A')
            ->has('upcoming', 0));
    }

    public function test_scheduled_fixtures_without_a_date_still_appear_in_the_public_schedule(): void
    {
        $organization = Organization::factory()->create(['is_active' => true]);
        $session = Session::factory()->create(['organization_id' => $organization->id, 'is_active' => true]);
        config(['app.public_org_slug' => $organization->slug, 'app.public_session_slug' => $session->slug]);
        $tournament = Tournament::factory()->forSession($session)->create();
        $event = Event::factory()->forTournament($tournament)->create();
        $home = Participant::factory()->create(['organization_id' => $organization->id, 'session_id' => $session->id, 'is_active' => true]);
        $away = Participant::factory()->create(['organization_id' => $organization->id, 'session_id' => $session->id, 'is_active' => true]);
        Fixture::factory()->scheduled()->create([
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'home_participant_id' => $home->id,
            'away_participant_id' => $away->id,
            'scheduled_at' => null,
        ]);

        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/Index')
            ->has('upcoming', 1)
            ->where('upcoming.0.scheduled_at', null));
    }

    public function test_public_portal_has_a_safe_empty_state(): void
    {
        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/Index')->where('competition', null)->has('upcoming', 0)->has('results', 0));
    }

    public function test_public_contact_exposes_only_valid_channels_for_the_configured_organization(): void
    {
        $organization = Organization::factory()->create(['is_active' => true]);
        $otherOrganization = Organization::factory()->create(['is_active' => true]);
        $session = Session::factory()->create(['organization_id' => $organization->id, 'is_active' => true]);
        config(['app.public_org_slug' => $organization->slug, 'app.public_session_slug' => $session->slug]);

        foreach ([
            'secretariat_address' => "Pusat Sukan\nUniversiti Teknikal Malaysia Melaka",
            'secretariat_email' => 'pusatsukan@utem.edu.my',
            'secretariat_phone' => '+606-2292326',
            'secretariat_facebook_url' => 'https://www.facebook.com/pusatsukanutem',
            'secretariat_instagram_url' => 'https://www.instagram.com/pusatsukanutem',
            'secretariat_tiktok_url' => 'https://www.tiktok.com/pusatsukanutem',
            'secretariat_youtube_url' => 'https://www.youtube.com/pusatsukanutem',
        ] as $key => $value) {
            Setting::create(['organization_id' => $organization->id, 'key' => $key, 'value' => $value]);
        }

        Setting::create([
            'organization_id' => $otherOrganization->id,
            'key' => 'secretariat_email',
            'value' => 'private-other@example.test',
        ]);

        $this->get(route('public.contact'))
            ->assertOk()
            ->assertDontSee('private-other@example.test')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Contact')
                ->where('contact.address', "Pusat Sukan\nUniversiti Teknikal Malaysia Melaka")
                ->where('contact.email', 'pusatsukan@utem.edu.my')
                ->where('contact.phone', '+606-2292326')
                ->where('contact.social.facebook', 'https://www.facebook.com/pusatsukanutem')
                ->where('contact.social.instagram', 'https://www.instagram.com/pusatsukanutem')
                ->where('contact.social.tiktok', 'https://www.tiktok.com/pusatsukanutem')
                ->where('contact.social.youtube', 'https://www.youtube.com/pusatsukanutem'));
    }

    public function test_public_contact_filters_unsafe_channels_inserted_outside_the_settings_form(): void
    {
        $organization = Organization::factory()->create(['is_active' => true]);
        $session = Session::factory()->create(['organization_id' => $organization->id, 'is_active' => true]);
        config(['app.public_org_slug' => $organization->slug, 'app.public_session_slug' => $session->slug]);

        Setting::create(['organization_id' => $organization->id, 'key' => 'secretariat_email', 'value' => 'invalid-email']);
        Setting::create(['organization_id' => $organization->id, 'key' => 'secretariat_phone', 'value' => 'invalid-phone']);
        Setting::create(['organization_id' => $organization->id, 'key' => 'secretariat_facebook_url', 'value' => 'javascript:alert(1)']);

        $this->get(route('public.contact'))->assertInertia(fn (Assert $page) => $page
            ->where('contact.email', null)
            ->where('contact.phone', null)
            ->where('contact.social.facebook', null));
    }

    public function test_same_session_slug_in_two_organizations_never_mixes_public_data(): void
    {
        $orgA = Organization::factory()->create(['slug' => 'org-a']);
        $orgB = Organization::factory()->create(['slug' => 'org-b']);
        $sessionA = Session::factory()->create(['organization_id' => $orgA->id, 'slug' => 'games-2026', 'name' => 'Organization A Games']);
        Session::factory()->create(['organization_id' => $orgB->id, 'slug' => 'games-2026', 'name' => 'Organization B Games']);

        config(['app.public_org_slug' => 'org-a', 'app.public_session_slug' => 'games-2026']);

        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('competition.name', $sessionA->name)
            ->where('competition.organization', $orgA->name));
    }

    public function test_invalid_or_inactive_public_organization_returns_safe_empty_state(): void
    {
        Organization::factory()->inactive()->create(['slug' => 'inactive-org']);
        config(['app.public_org_slug' => 'inactive-org', 'app.public_session_slug' => 'games-2026']);

        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('competition', null)->has('upcoming', 0)->has('results', 0));
    }

    public function test_public_portal_cache_is_invalidated_only_for_the_changed_organization(): void
    {
        $orgA = Organization::factory()->create(['slug' => 'cache-org-a']);
        $orgB = Organization::factory()->create(['slug' => 'cache-org-b']);
        $sessionA = Session::factory()->create(['organization_id' => $orgA->id, 'slug' => 'cache-session-a']);
        $sessionB = Session::factory()->create(['organization_id' => $orgB->id, 'slug' => 'cache-session-b']);

        Cache::put('public-portal:v4:'.$sessionA->id.':12', ['stale' => true], 60);
        Cache::put('public-portal:v4:'.$sessionA->id.':all', ['stale' => true], 60);
        Cache::put('public-portal:v6:'.$sessionA->id.':12', ['stale' => true], 60);
        Cache::put('public-portal:v6:'.$sessionA->id.':all', ['stale' => true], 60);
        Cache::put('public-portal:v4:'.$sessionB->id.':12', ['keep' => true], 60);

        config(['app.public_org_slug' => $orgA->slug, 'app.public_session_slug' => $sessionA->slug]);
        app(PublicPortalService::class)->forgetForOrganization($orgA->id);

        $this->assertNull(Cache::get('public-portal:v4:'.$sessionA->id.':12'));
        $this->assertNull(Cache::get('public-portal:v4:'.$sessionA->id.':all'));
        $this->assertNull(Cache::get('public-portal:v6:'.$sessionA->id.':12'));
        $this->assertNull(Cache::get('public-portal:v6:'.$sessionA->id.':all'));
        $this->assertSame(['keep' => true], Cache::get('public-portal:v4:'.$sessionB->id.':12'));
    }
}
