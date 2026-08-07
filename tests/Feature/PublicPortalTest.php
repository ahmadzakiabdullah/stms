<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Session;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_portal_displays_only_active_session_data_without_contact_details(): void
    {
        $organization = Organization::factory()->create(['is_active' => true]);
        $session = Session::factory()->create(['organization_id' => $organization->id, 'name' => 'Sukan Antara Fakulti 2026', 'is_active' => true]);
        config(['app.public_org_slug' => $organization->slug, 'app.public_session_slug' => $session->slug]);
        $tournament = Tournament::factory()->forSession($session)->create();
        $event = Event::factory()->forTournament($tournament)->create();
        $home = Participant::factory()->create(['organization_id' => $organization->id, 'session_id' => $session->id, 'name' => 'Fakulti Teknologi Maklumat', 'email' => 'rahsia@example.test', 'is_active' => true]);
        $away = Participant::factory()->create(['organization_id' => $organization->id, 'session_id' => $session->id, 'name' => 'Fakulti Kejuruteraan', 'is_active' => true]);
        Fixture::factory()->scheduled()->create(['organization_id' => $organization->id, 'event_id' => $event->id, 'home_participant_id' => $home->id, 'away_participant_id' => $away->id, 'scheduled_at' => now()->addDay()]);

        $this->get('/')->assertOk()->assertDontSee('rahsia@example.test')->assertInertia(fn (Assert $page) => $page
            ->component('Public/Index')->where('competition.name', 'Sukan Antara Fakulti 2026')
            ->where('stats.faculties', 2)->has('upcoming', 1)
            ->where('upcoming.0.home.name', 'Fakulti Teknologi Maklumat'));
    }

    public function test_index_php_redirects_to_the_canonical_public_url(): void
    {
        $this->get('/index.php')->assertRedirect('/portal/')->assertStatus(301);
    }

    public function test_portal_without_trailing_slash_redirects_to_the_canonical_public_url(): void
    {
        $this->get('/portal')->assertRedirect(config('app.url').'/')->assertStatus(301);
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

    public function test_public_portal_has_a_safe_empty_state(): void
    {
        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/Index')->where('competition', null)->has('upcoming', 0)->has('results', 0));
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
}
