<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Organization;
use App\Models\Participant;
use App\Services\ParticipantScheduleConflictService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantScheduleConflictServiceTest extends TestCase
{
    use RefreshDatabase;

    private function seedParticipantWithMatches(): array
    {
        $org = Organization::factory()->create();
        $eventA = Event::factory()->create(['organization_id' => $org->id, 'name' => 'Badminton - Singles']);
        $eventB = Event::factory()->create(['organization_id' => $org->id, 'name' => 'Football - Team']);
        $participant = Participant::factory()->create(['organization_id' => $org->id]);

        $ep = EventParticipant::factory()->create([
            'organization_id' => $org->id,
            'event_id' => $eventA->id,
            'participant_id' => $participant->id,
            'status' => 'confirmed',
        ]);

        return compact('org', 'eventA', 'eventB', 'participant', 'ep');
    }

    public function test_detects_two_matches_same_day_same_venue(): void
    {
        $data = $this->seedParticipantWithMatches();

        \App\Models\Fixture::create([
            'organization_id' => $data['org']->id,
            'event_id' => $data['eventA']->id,
            'home_participant_id' => $data['participant']->id,
            'venue' => 'Stadium A',
            'scheduled_at' => now()->setDate(2026, 10, 5)->setTime(9, 0),
            'status' => 'scheduled',
        ]);
        \App\Models\Fixture::create([
            'organization_id' => $data['org']->id,
            'event_id' => $data['eventB']->id,
            'away_participant_id' => $data['participant']->id,
            'venue' => 'Stadium A',
            'scheduled_at' => now()->setDate(2026, 10, 5)->setTime(14, 0),
            'status' => 'scheduled',
        ]);

        $conflicts = app(ParticipantScheduleConflictService::class)->conflictsFor($data['ep']);

        $this->assertCount(1, $conflicts);
        $this->assertSame('2026-10-05', $conflicts[0]['date']);
        $this->assertSame('Stadium A', $conflicts[0]['venue']);
    }

    public function test_ignores_matches_on_different_days_same_venue(): void
    {
        $data = $this->seedParticipantWithMatches();

        \App\Models\Fixture::create([
            'organization_id' => $data['org']->id,
            'event_id' => $data['eventA']->id,
            'home_participant_id' => $data['participant']->id,
            'venue' => 'Stadium A',
            'scheduled_at' => now()->setDate(2026, 10, 5)->setTime(9, 0),
            'status' => 'scheduled',
        ]);
        \App\Models\Fixture::create([
            'organization_id' => $data['org']->id,
            'event_id' => $data['eventB']->id,
            'away_participant_id' => $data['participant']->id,
            'venue' => 'Stadium A',
            'scheduled_at' => now()->setDate(2026, 10, 6)->setTime(9, 0),
            'status' => 'scheduled',
        ]);

        $conflicts = app(ParticipantScheduleConflictService::class)->conflictsFor($data['ep']);

        $this->assertCount(0, $conflicts);
    }

    public function test_ignores_matches_without_venue(): void
    {
        $data = $this->seedParticipantWithMatches();

        \App\Models\Fixture::create([
            'organization_id' => $data['org']->id,
            'event_id' => $data['eventA']->id,
            'home_participant_id' => $data['participant']->id,
            'venue' => null,
            'scheduled_at' => now()->setDate(2026, 10, 5)->setTime(9, 0),
            'status' => 'scheduled',
        ]);
        \App\Models\Fixture::create([
            'organization_id' => $data['org']->id,
            'event_id' => $data['eventB']->id,
            'away_participant_id' => $data['participant']->id,
            'venue' => null,
            'scheduled_at' => now()->setDate(2026, 10, 5)->setTime(14, 0),
            'status' => 'scheduled',
        ]);

        $conflicts = app(ParticipantScheduleConflictService::class)->conflictsFor($data['ep']);

        $this->assertCount(0, $conflicts);
    }

    public function test_returns_empty_when_participant_has_no_matches(): void
    {
        $data = $this->seedParticipantWithMatches();

        $conflicts = app(ParticipantScheduleConflictService::class)->conflictsFor($data['ep']);

        $this->assertCount(0, $conflicts);
    }
}