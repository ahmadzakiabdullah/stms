<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Pool;
use App\Models\User;
use App\Services\DrawService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DrawServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DrawService $service;

    protected User $user;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DrawService;

        // Due to the BelongsToOrganization trait, the authenticated user must have
        // the same organization_id as the created models, or be a super-admin.
        $this->organization = Organization::factory()->create();
        $this->user = User::factory()->create(['organization_id' => $this->organization->id]);
        $this->actingAs($this->user);
    }

    private function createEventParticipant(Event $event, string $status = 'confirmed'): EventParticipant
    {
        $participant = Participant::factory()->create(['organization_id' => $event->organization_id]);

        $ep = new EventParticipant([
            'organization_id' => $event->organization_id,
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'status' => $status,
        ]);
        $ep->save();

        return $ep;
    }

    public function test_it_throws_exception_if_fewer_than_two_confirmed_participants()
    {
        $event = Event::factory()->create(['organization_id' => $this->organization->id]);
        $this->createEventParticipant($event, 'confirmed');
        $this->createEventParticipant($event, 'pending');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Need at least 2 confirmed participants to draw.');

        $this->service->drawAndGenerateFixtures($event);
    }

    public function test_it_draws_and_generates_fixtures_for_even_participants()
    {
        $event = Event::factory()->create([
            'organization_id' => $this->organization->id,
            'pool_size' => 4,
        ]);

        for ($i = 0; $i < 4; $i++) {
            $this->createEventParticipant($event, 'confirmed');
        }

        $result = $this->service->drawAndGenerateFixtures($event);

        $this->assertEquals(1, $result['pools']);
        $this->assertEquals(4, $result['participants']);
        // For 4 participants, number of fixtures in round robin: (4 * 3) / 2 = 6
        $this->assertEquals(6, $result['fixtures']);

        $this->assertDatabaseCount('pools', 1);
        $this->assertDatabaseCount('matches', 6);
    }

    public function test_it_draws_and_generates_fixtures_for_odd_participants()
    {
        $event = Event::factory()->create([
            'organization_id' => $this->organization->id,
            'pool_size' => 4,
        ]);

        for ($i = 0; $i < 3; $i++) {
            $this->createEventParticipant($event, 'confirmed');
        }

        $result = $this->service->drawAndGenerateFixtures($event);

        $this->assertEquals(1, $result['pools']);
        $this->assertEquals(3, $result['participants']);
        // For 3 participants, they play a round robin with one bye each round.
        // Teams: A, B, C, Bye. Total matches excluding byes: 3
        // 3 teams = (3 * 2) / 2 = 3 matches
        $this->assertEquals(3, $result['fixtures']);

        $this->assertDatabaseCount('pools', 1);
        $this->assertDatabaseCount('matches', 3);
    }

    public function test_it_creates_multiple_pools()
    {
        $event = Event::factory()->create([
            'organization_id' => $this->organization->id,
            'pool_size' => 3,
        ]);

        for ($i = 0; $i < 7; $i++) {
            $this->createEventParticipant($event, 'confirmed');
        }

        $result = $this->service->drawAndGenerateFixtures($event);

        // 7 participants, max 3 per pool -> ceil(7/3) = 3 pools
        $this->assertEquals(3, $result['pools']);
        $this->assertEquals(7, $result['participants']);

        $pools = Pool::where('event_id', $event->id)->get();
        $this->assertCount(3, $pools);

        // Distributions should be roughly equal: 3, 2, 2
        $counts = EventParticipant::whereNotNull('pool_id')
            ->selectRaw('pool_id, count(*) as count')
            ->groupBy('pool_id')
            ->pluck('count')
            ->toArray();

        sort($counts);
        $this->assertEquals([2, 2, 3], $counts);
    }

    public function test_it_moves_participant_to_pool_before_fixtures()
    {
        $event = Event::factory()->create([
            'organization_id' => $this->organization->id,
            'pool_size' => 2,
        ]);

        for ($i = 0; $i < 4; $i++) {
            $this->createEventParticipant($event, 'confirmed');
        }

        $this->service->drawGroups($event);

        $this->assertDatabaseCount('matches', 0);

        $pools = Pool::where('event_id', $event->id)->get();
        $poolA = $pools[0];
        $poolB = $pools[1];

        // Find a participant in pool A
        $participantToMove = EventParticipant::where('pool_id', $poolA->id)->first();

        $this->service->moveParticipantToPool($event, $participantToMove->id, $poolB->id);

        $updatedParticipant = EventParticipant::find($participantToMove->id);
        $this->assertEquals($poolB->id, $updatedParticipant->pool_id);
    }

    public function test_it_throws_exception_if_moving_participant_after_fixtures()
    {
        $event = Event::factory()->create([
            'organization_id' => $this->organization->id,
            'pool_size' => 2,
        ]);

        for ($i = 0; $i < 4; $i++) {
            $this->createEventParticipant($event, 'confirmed');
        }

        $this->service->drawAndGenerateFixtures($event);

        $pools = Pool::where('event_id', $event->id)->get();
        $poolA = $pools[0];
        $poolB = $pools[1];

        $participantToMove = EventParticipant::where('pool_id', $poolA->id)->first();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Reset the draw before changing groups after fixtures have been created.');

        $this->service->moveParticipantToPool($event, $participantToMove->id, $poolB->id);
    }
}
