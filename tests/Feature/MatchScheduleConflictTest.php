<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Participant;
use App\Services\MatchScheduleConflictValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class MatchScheduleConflictTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    private function org(): Organization
    {
        return Organization::factory()->create();
    }

    public function test_no_conflict_when_same_venue_different_day(): void
    {
        $org = $this->org();

        Fixture::factory()->create([
            'organization_id' => $org->id,
            'event_id' => Event::factory()->create(['organization_id' => $org->id])->id,
            'venue' => 'Stadium A',
            'scheduled_at' => '2026-10-01 09:00:00',
        ]);

        $conflicts = app(MatchScheduleConflictValidator::class)->conflictsFor($org, [
            'venue' => 'Stadium A',
            'scheduled_at' => '2026-10-02 09:00:00',
        ]);

        $this->assertCount(0, $conflicts);
    }

    public function test_venue_conflict_detected_on_same_day_overlapping_window(): void
    {
        $org = $this->org();

        Fixture::factory()->create([
            'organization_id' => $org->id,
            'event_id' => Event::factory()->create(['organization_id' => $org->id])->id,
            'venue' => 'Stadium A',
            'scheduled_at' => '2026-10-01 09:00:00',
        ]);

        $conflicts = app(MatchScheduleConflictValidator::class)->conflictsFor($org, [
            'venue' => 'Stadium A',
            'scheduled_at' => '2026-10-01 09:30:00',
        ]);

        $this->assertCount(1, $conflicts);
        $this->assertSame('venue', $conflicts[0]['kind']);
    }

    public function test_participant_conflict_detected_when_home_participant_plays_elsewhere(): void
    {
        $org = $this->org();
        $participant = Participant::factory()->create(['organization_id' => $org->id]);

        Fixture::factory()->create([
            'organization_id' => $org->id,
            'event_id' => Event::factory()->create(['organization_id' => $org->id])->id,
            'home_participant_id' => $participant->id,
            'scheduled_at' => '2026-10-01 10:00:00',
        ]);

        $conflicts = app(MatchScheduleConflictValidator::class)->conflictsFor($org, [
            'home_participant_id' => $participant->id,
            'scheduled_at' => '2026-10-01 11:00:00',
        ]);

        $this->assertCount(1, $conflicts);
        $this->assertSame('participant', $conflicts[0]['kind']);
    }

    public function test_edit_ignores_itself_so_no_false_positive(): void
    {
        $org = $this->org();
        $event = Event::factory()->create(['organization_id' => $org->id]);

        $fixture = Fixture::factory()->create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'venue' => 'Stadium A',
            'scheduled_at' => '2026-10-01 09:00:00',
        ]);

        $conflicts = app(MatchScheduleConflictValidator::class)->conflictsFor($org, [
            'venue' => 'Stadium A',
            'scheduled_at' => '2026-10-01 09:00:00',
        ], $fixture->id);

        $this->assertCount(0, $conflicts);
    }

    public function test_store_match_is_blocked_on_venue_conflict(): void
    {
        $org = $this->org();
        $user = $this->createOrgAdmin($org);
        $event = Event::factory()->create(['organization_id' => $org->id]);

        Fixture::factory()->create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'venue' => 'Main Court',
            'scheduled_at' => '2026-10-01 09:00:00',
        ]);

        $this->actingAs($user)->post(route('matches.store'), [
            'event_id' => $event->id,
            'match_number' => 99,
            'venue' => 'Main Court',
            'scheduled_at' => '2026-10-01 09:30:00',
        ])->assertSessionHasErrors('scheduled_at');

        $this->assertDatabaseMissing('matches', ['match_number' => 99]);
    }

    public function test_same_day_distant_time_is_allowed(): void
    {
        $org = $this->org();
        $event = Event::factory()->create(['organization_id' => $org->id]);

        Fixture::factory()->create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'venue' => 'Stadium A',
            'scheduled_at' => '2026-10-01 09:00:00',
        ]);

        $conflicts = app(MatchScheduleConflictValidator::class)->conflictsFor($org, [
            'venue' => 'Stadium A',
            'scheduled_at' => '2026-10-01 15:00:00',
        ]);

        $this->assertCount(0, $conflicts);
    }
}
