<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Fixture;
use App\Models\MatchScoringEvent;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Result;
use App\Models\Session;
use App\Models\Setting;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\SquadMember;
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

    public function test_public_schedule_page_displays_configured_session_fixtures(): void
    {
        $organization = Organization::factory()->create(['is_active' => true]);
        $session = Session::factory()->create(['organization_id' => $organization->id, 'is_active' => true]);
        config(['app.public_org_slug' => $organization->slug, 'app.public_session_slug' => $session->slug]);
        $tournament = Tournament::factory()->forSession($session)->create();
        $event = Event::factory()->forTournament($tournament)->create();
        $home = Participant::factory()->create(['organization_id' => $organization->id, 'session_id' => $session->id, 'name' => 'Fakulti A']);
        $away = Participant::factory()->create(['organization_id' => $organization->id, 'session_id' => $session->id, 'name' => 'Fakulti B']);
        Fixture::factory()->scheduled()->create(['organization_id' => $organization->id, 'event_id' => $event->id, 'home_participant_id' => $home->id, 'away_participant_id' => $away->id]);

        $this->get(route('public.schedule'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/Schedule')->has('upcoming', 1)->where('upcoming.0.home.name', 'Fakulti A')->where('upcoming.0.away.name', 'Fakulti B'));
    }

    public function test_public_schedule_exposes_scorers_grouped_by_match_participant(): void
    {
        $organization = Organization::factory()->create(['is_active' => true]);
        $session = Session::factory()->create(['organization_id' => $organization->id, 'is_active' => true]);
        config(['app.public_org_slug' => $organization->slug, 'app.public_session_slug' => $session->slug]);
        $tournament = Tournament::factory()->forSession($session)->create();
        $sport = Sport::factory()->create(['organization_id' => $organization->id, 'scoring_mode' => 'individual', 'name' => 'Hockey']);
        $event = Event::factory()->forTournament($tournament)->create(['sport_id' => $sport->id]);
        $home = Participant::factory()->create(['organization_id' => $organization->id, 'session_id' => $session->id, 'name' => 'FTKM']);
        $away = Participant::factory()->create(['organization_id' => $organization->id, 'session_id' => $session->id, 'name' => 'STEP']);
        $homeEntry = EventParticipant::factory()->create(['organization_id' => $organization->id, 'event_id' => $event->id, 'participant_id' => $home->id, 'status' => 'confirmed']);
        $homeAthlete = SquadMember::factory()->create(['organization_id' => $organization->id, 'event_participant_id' => $homeEntry->id, 'name' => 'Ali Penjaring', 'role' => 'athlete_male']);
        $fixture = Fixture::factory()->completed()->create(['organization_id' => $organization->id, 'event_id' => $event->id, 'home_participant_id' => $home->id, 'away_participant_id' => $away->id]);
        $result = Result::factory()->forOrganization($organization)->create(['match_id' => $fixture->id, 'score_home' => 1, 'score_away' => 0]);
        MatchScoringEvent::create(['organization_id' => $organization->id, 'result_id' => $result->id, 'match_id' => $fixture->id, 'participant_id' => $home->id, 'squad_member_id' => $homeAthlete->id, 'event_type' => 'goal', 'minute' => 18, 'points' => 1]);

        $this->get(route('public.schedule'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/Schedule')
            ->where('completed.0.scoring_events.0.participant_id', $home->id)
            ->where('completed.0.scoring_events.0.name', 'Ali Penjaring')
            ->where('completed.0.scoring_events.0.minute', 18));
    }

    public function test_public_athletes_page_exposes_only_confirmed_active_rosters_without_sensitive_fields(): void
    {
        $organization = Organization::factory()->create(['is_active' => true]);
        $session = Session::factory()->create(['organization_id' => $organization->id, 'is_active' => true]);
        config(['app.public_org_slug' => $organization->slug, 'app.public_session_slug' => $session->slug]);
        $tournament = Tournament::factory()->forSession($session)->create();
        $event = Event::factory()->forTournament($tournament)->create();
        $faculty = Participant::factory()->create(['organization_id' => $organization->id, 'session_id' => $session->id, 'name' => 'Fakulti Sukan', 'is_active' => true]);
        $registration = EventParticipant::factory()->create(['organization_id' => $organization->id, 'event_id' => $event->id, 'participant_id' => $faculty->id, 'status' => 'confirmed']);
        SquadMember::factory()->create(['organization_id' => $organization->id, 'event_participant_id' => $registration->id, 'name' => 'Atlet Awam', 'role' => 'athlete_male', 'identification_no' => 'RAHSIA-123', 'phone' => '0129999999', 'is_active' => true]);
        SquadMember::factory()->create(['organization_id' => $organization->id, 'event_participant_id' => $registration->id, 'name' => 'Atlet Tidak Aktif', 'role' => 'athlete_female', 'is_active' => false]);
        $opponent = Participant::factory()->create(['organization_id' => $organization->id, 'session_id' => $session->id, 'name' => 'Fakulti Lawan', 'is_active' => true]);
        $fixture = Fixture::factory()->completed()->create(['organization_id' => $organization->id, 'event_id' => $event->id, 'home_participant_id' => $faculty->id, 'away_participant_id' => $opponent->id]);
        Result::factory()->forOrganization($organization)->create(['match_id' => $fixture->id, 'score_home' => 3, 'score_away' => 1, 'winner_participant_id' => $faculty->id]);

        $this->get(route('public.athletes'))->assertOk()->assertDontSee('RAHSIA-123')->assertDontSee('0129999999')->assertInertia(fn (Assert $page) => $page
            ->component('Public/Athletes')
            ->where('stats.teams', 1)
            ->where('stats.athletes', 1)
            ->where('rosters.0.name', 'Fakulti Sukan')
            ->where('rosters.0.members.0.name', 'Atlet Awam')
            ->where('athletes.0.name', 'Atlet Awam')
            ->where('athletes.0.faculty', 'Fakulti Sukan')
            ->missing('upcoming')
            ->missing('medals'));

        $this->get(route('public.athletes.show', $registration->squadMembers()->where('name', 'Atlet Awam')->value('id')))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Athlete')
                ->where('athlete.name', 'Atlet Awam')
                ->where('stats.matches', 1)
                ->where('stats.wins', 1)
                ->where('matches.0.opponent', 'Fakulti Lawan'));
    }

    public function test_public_schedule_includes_sport_categories_for_filtering(): void
    {
        $organization = Organization::factory()->create(['is_active' => true]);
        $session = Session::factory()->create(['organization_id' => $organization->id, 'is_active' => true]);
        config(['app.public_org_slug' => $organization->slug, 'app.public_session_slug' => $session->slug]);
        $tournament = Tournament::factory()->forSession($session)->create();
        $sport = Sport::factory()->create(['organization_id' => $organization->id]);
        $category = SportCategory::factory()->forSport($sport)->create(['name' => 'Lelaki']);
        $event = Event::factory()->forTournament($tournament)->create(['sport_id' => $sport->id, 'sport_category_id' => $category->id]);
        $home = Participant::factory()->create(['organization_id' => $organization->id, 'session_id' => $session->id, 'name' => 'Fakulti A']);
        $away = Participant::factory()->create(['organization_id' => $organization->id, 'session_id' => $session->id, 'name' => 'Fakulti B']);
        Fixture::factory()->scheduled()->create(['organization_id' => $organization->id, 'event_id' => $event->id, 'home_participant_id' => $home->id, 'away_participant_id' => $away->id]);

        $this->get(route('public.schedule'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/Schedule')
            ->where('upcoming.0.sport', $sport->name)
            ->where('upcoming.0.category', 'Lelaki')
            ->has('sports_catalog', 1)
            ->where('sports_catalog.0.name', $sport->name)
            ->where('sports_catalog.0.categories.0', 'Lelaki'));
    }

    public function test_knockout_fixtures_on_the_same_date_appear_in_play_order(): void
    {
        $organization = Organization::factory()->create(['is_active' => true]);
        $session = Session::factory()->create(['organization_id' => $organization->id, 'is_active' => true]);
        config(['app.public_org_slug' => $organization->slug, 'app.public_session_slug' => $session->slug]);
        $tournament = Tournament::factory()->forSession($session)->create();
        $event = Event::factory()->forTournament($tournament)->create();
        $home = Participant::factory()->create(['organization_id' => $organization->id, 'session_id' => $session->id, 'name' => 'Fakulti A']);
        $away = Participant::factory()->create(['organization_id' => $organization->id, 'session_id' => $session->id, 'name' => 'Fakulti B']);
        $third = Participant::factory()->create(['organization_id' => $organization->id, 'session_id' => $session->id, 'name' => 'Fakulti C']);
        $fourth = Participant::factory()->create(['organization_id' => $organization->id, 'session_id' => $session->id, 'name' => 'Fakulti D']);
        $scheduledAt = now()->addDay()->startOfDay();

        foreach ([
            ['stage' => 'semi_final', 'round' => 1, 'match_number' => 5, 'home_participant_id' => $home->id, 'away_participant_id' => $away->id],
            ['stage' => 'semi_final', 'round' => 2, 'match_number' => 6, 'home_participant_id' => $third->id, 'away_participant_id' => $fourth->id],
            ['stage' => 'bronze', 'round' => 3, 'match_number' => 7],
            ['stage' => 'final', 'round' => 4, 'match_number' => 8],
        ] as $fixture) {
            Fixture::factory()->scheduled()->create(array_merge([
                'organization_id' => $organization->id,
                'event_id' => $event->id,
                'scheduled_at' => $scheduledAt,
            ], $fixture));
        }

        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/Index')
            ->has('upcoming', 4)
            ->where('upcoming.0.stage', 'semi_final')->where('upcoming.0.round', 1)
            ->where('upcoming.1.stage', 'semi_final')->where('upcoming.1.round', 2)
            ->where('upcoming.2.stage', 'bronze')
            ->where('upcoming.3.stage', 'final'));
    }

    public function test_public_matches_inherit_the_event_venue_and_venues_list_combines_sources(): void
    {
        $organization = Organization::factory()->create(['is_active' => true]);
        $session = Session::factory()->create(['organization_id' => $organization->id, 'is_active' => true]);
        config(['app.public_org_slug' => $organization->slug, 'app.public_session_slug' => $session->slug]);
        $tournament = Tournament::factory()->forSession($session)->create();
        $event = Event::factory()->forTournament($tournament)->create(['venues' => ['Stadium Mini UTeM', 'Padang B']]);
        $home = Participant::factory()->create(['organization_id' => $organization->id, 'session_id' => $session->id, 'is_active' => true]);
        $away = Participant::factory()->create(['organization_id' => $organization->id, 'session_id' => $session->id, 'is_active' => true]);
        Fixture::factory()->scheduled()->create(['organization_id' => $organization->id, 'event_id' => $event->id, 'home_participant_id' => $home->id, 'away_participant_id' => $away->id, 'venue' => null]);

        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/Index')
            ->has('upcoming', 1)
            ->where('upcoming.0.venue', 'Stadium Mini UTeM')
            ->has('venues', 2)
            ->where('venues.0', 'Padang B')
            ->where('venues.1', 'Stadium Mini UTeM'));
    }

    public function test_public_directory_pages_render_without_authentication(): void
    {
        $organization = Organization::factory()->create(['is_active' => true]);
        $session = Session::factory()->create(['organization_id' => $organization->id, 'is_active' => true]);
        config(['app.public_org_slug' => $organization->slug, 'app.public_session_slug' => $session->slug]);

        foreach (['public.sports' => 'sports', 'public.faculties' => 'faculties', 'public.venues' => 'venues'] as $routeName => $section) {
            $this->get(route($routeName))->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('Public/Directory')->where('section', $section));
        }
    }

    public function test_legacy_match_pages_redirect_to_the_consolidated_schedule(): void
    {
        foreach (['public.matches', 'public.results', 'public.live'] as $routeName) {
            $this->get(route($routeName))->assertRedirect('/schedule')->assertStatus(301);
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
