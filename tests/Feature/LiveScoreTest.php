<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Pool;
use App\Models\Result;
use App\Models\Session;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LiveScoreTest extends TestCase
{
    use RefreshDatabase;

    private function seedCompletedMatch(Organization $org, ?string $sportName = null, ?string $sportSlug = null): array
    {
        $session = Session::factory()->create(['organization_id' => $org->id]);
        $tournament = Tournament::factory()->create(['organization_id' => $org->id, 'session_id' => $session->id]);
        $sport = Sport::factory()->create(array_filter([
            'organization_id' => $org->id,
            'name' => $sportName ?? 'Badminton',
            'slug' => $sportSlug,
        ], fn ($value) => $value !== null));
        $category = SportCategory::factory()->create(['organization_id' => $org->id, 'sport_id' => $sport->id, 'name' => 'Singles']);
        $event = Event::factory()->create([
            'organization_id' => $org->id,
            'tournament_id' => $tournament->id,
            'sport_id' => $sport->id,
            'sport_category_id' => $category->id,
            'name' => $sport->name.' - Singles',
        ]);

        $home = Participant::factory()->create(['organization_id' => $org->id, 'session_id' => $session->id, 'name' => 'FTKEK']);
        $away = Participant::factory()->create(['organization_id' => $org->id, 'session_id' => $session->id, 'name' => 'FTKM']);

        $match = Fixture::factory()->create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'home_participant_id' => $home->id,
            'away_participant_id' => $away->id,
            'match_number' => 1,
            'status' => 'completed',
        ]);

        $result = Result::factory()->create([
            'organization_id' => $org->id,
            'match_id' => $match->id,
            'score_home' => 2,
            'score_away' => 1,
            'winner_participant_id' => $home->id,
        ]);

        return compact('org', 'event', 'sport', 'home', 'away', 'match', 'result');
    }

    public function test_live_page_is_publicly_accessible_without_login(): void
    {
        Organization::factory()->create(['is_active' => true]);

        $this->get(route('live.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Live/Index'));
    }

    public function test_completed_matches_with_results_are_displayed(): void
    {
        $data = $this->seedCompletedMatchWithLeague(Organization::factory()->create(['is_active' => true]));

        $this->get(route('live.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Live/Index')
                ->has('matches', 1)
                ->where('matches.0.result.score_home', 2)
                ->where('matches.0.result.score_away', 1)
                ->has('standings', 1)
                ->has('standings.0.pools.0.rows', 2)
                ->where('standings.0.pools.0.rows.0.points', 3));
    }

    public function test_live_page_scopes_data_to_the_first_active_organization(): void
    {
        $orgA = Organization::factory()->create(['is_active' => true]);
        $orgB = Organization::factory()->create(['is_active' => true]);

        $this->seedCompletedMatchWithLeague($orgA);
        $this->seedCompletedMatchWithLeague($orgB);

        $this->get(route('live.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('organization.id', $orgA->id)
                ->has('matches', 1));
    }

    public function test_live_page_ignores_inactive_organizations(): void
    {
        $inactiveOrg = Organization::factory()->inactive()->create();
        $session = Session::factory()->create(['organization_id' => $inactiveOrg->id]);
        $tournament = Tournament::query()->create([
            'organization_id' => $inactiveOrg->id,
            'session_id' => $session->id,
            'name' => 'Fasa 1',
            'slug' => 'fasa-1',
            'start_date' => $session->start_date,
            'end_date' => $session->end_date,
            'is_active' => true,
        ]);
        $sport = Sport::factory()->create(['organization_id' => $inactiveOrg->id, 'name' => 'Futsal']);
        $category = SportCategory::factory()->create(['organization_id' => $inactiveOrg->id, 'sport_id' => $sport->id, 'name' => "Men's Open"]);
        $event = Event::query()->create([
            'organization_id' => $inactiveOrg->id,
            'tournament_id' => $tournament->id,
            'sport_id' => $sport->id,
            'sport_category_id' => $category->id,
            'name' => 'Futsal - Men Open',
            'slug' => 'futsal-men-open',
            'start_date' => $tournament->start_date,
            'end_date' => $tournament->end_date,
            'is_active' => true,
        ]);

        $home = Participant::factory()->create(['organization_id' => $inactiveOrg->id, 'session_id' => $session->id, 'name' => 'FTKEK']);
        $away = Participant::factory()->create(['organization_id' => $inactiveOrg->id, 'session_id' => $session->id, 'name' => 'FTKM']);

        $match = Fixture::query()->create([
            'organization_id' => $inactiveOrg->id,
            'event_id' => $event->id,
            'home_participant_id' => $home->id,
            'away_participant_id' => $away->id,
            'match_number' => 1,
            'status' => 'completed',
        ]);

        Result::query()->create([
            'organization_id' => $inactiveOrg->id,
            'match_id' => $match->id,
            'score_home' => 3,
            'score_away' => 0,
            'winner_participant_id' => $home->id,
        ]);

        $this->get(route('live.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('organization', null)
                ->has('matches', 0));
    }

    public function test_matches_can_be_filtered_by_sport_slug(): void
    {
        $org = Organization::factory()->create(['is_active' => true]);

        $football = $this->seedCompletedMatchWithLeague($org, 'Football', 'football')['match'];
        $badminton = $this->seedCompletedMatchWithLeague($org, 'Badminton', 'badminton')['match'];

        $this->get(route('live.index', ['sport' => 'football']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.sport', 'football')
                ->has('matches', 1)
                ->where('matches.0.id', $football->id));
    }

    public function test_matches_can_be_filtered_by_event_slug(): void
    {
        $org = Organization::factory()->create(['is_active' => true]);

        $data = $this->seedCompletedMatchWithLeague($org);

        $this->get(route('live.index', ['event' => $data['event']->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.event', $data['event']->slug)
                ->has('matches', 1)
                ->where('matches.0.id', $data['match']->id)
                ->has('standings', 1));
    }

    public function test_match_without_result_is_not_listed(): void
    {
        $org = Organization::factory()->create(['is_active' => true]);
        $session = Session::factory()->create(['organization_id' => $org->id]);
        $tournament = Tournament::factory()->create(['organization_id' => $org->id, 'session_id' => $session->id]);
        $sport = Sport::factory()->create(['organization_id' => $org->id, 'name' => 'Futsal']);
        $category = SportCategory::factory()->create(['organization_id' => $org->id, 'sport_id' => $sport->id, 'name' => "Men's Open"]);
        $event = Event::factory()->create([
            'organization_id' => $org->id,
            'tournament_id' => $tournament->id,
            'sport_id' => $sport->id,
            'sport_category_id' => $category->id,
        ]);

        Fixture::factory()->completed()->create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'match_number' => 1,
        ]);

        $this->get(route('live.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('matches', 0));
    }

    private function seedCompletedMatchWithLeague(Organization $org, ?string $sportName = null, ?string $sportSlug = null): array
    {
        $data = $this->seedCompletedMatch($org, $sportName, $sportSlug);

        $pool = Pool::factory()->create([
            'organization_id' => $org->id,
            'event_id' => $data['event']->id,
            'name' => 'Pool A',
            'sort_order' => 1,
        ]);

        EventParticipant::factory()->create([
            'organization_id' => $org->id,
            'event_id' => $data['event']->id,
            'pool_id' => $pool->id,
            'participant_id' => $data['home']->id,
        ]);

        EventParticipant::factory()->create([
            'organization_id' => $org->id,
            'event_id' => $data['event']->id,
            'pool_id' => $pool->id,
            'participant_id' => $data['away']->id,
        ]);

        $data['match']->update(['pool_id' => $pool->id]);

        return [...$data, 'pool' => $pool];
    }
}
