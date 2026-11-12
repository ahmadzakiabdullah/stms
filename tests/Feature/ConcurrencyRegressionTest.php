<?php

namespace Tests\Feature;

use App\Imports\EventParticipantImport;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Result;
use App\Services\DrawService;
use App\Services\MatchService;
use App\Services\ResultService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Tests\Traits\CreatesTenantUsers;

class ConcurrencyRegressionTest extends TestCase
{
    use CreatesTenantUsers, RefreshDatabase;

    public function test_duplicate_fixture_generation_is_idempotent_after_a_draw(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create([
            'organization_id' => $org->id,
            'pool_size' => 4,
        ]);
        EventParticipant::factory()->count(4)->create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
            'status' => 'confirmed',
        ]);

        $draw = app(DrawService::class);
        $draw->drawGroups($event);
        $first = $draw->generateFixtures($event);
        $second = $draw->generateFixtures($event->fresh());

        $this->assertSame(6, $first['fixtures']);
        $this->assertSame(0, $second['fixtures']);
        $this->assertSame(6, Fixture::where('event_id', $event->id)->count());
        $this->assertSame(
            range(1, 6),
            Fixture::where('event_id', $event->id)->orderBy('match_number')->pluck('match_number')->all()
        );
    }

    public function test_duplicate_result_entry_is_rejected_without_creating_a_second_result(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $org->id]);
        $match = Fixture::factory()->scheduled()->create([
            'organization_id' => $org->id,
            'event_id' => $event->id,
        ]);
        $service = app(ResultService::class);

        $service->create($org, [
            'match_id' => $match->id,
            'score_home' => 2,
            'score_away' => 1,
        ]);

        try {
            $service->create($org, [
                'match_id' => $match->id,
                'score_home' => 3,
                'score_away' => 0,
            ]);
            $this->fail('A duplicate result entry should be rejected.');
        } catch (ValidationException) {
            // Expected: the match row is locked and can have only one result.
        }

        $this->assertSame(1, Result::withTrashed()->where('match_id', $match->id)->count());
    }

    public function test_repeated_bulk_import_is_idempotent_and_reports_the_duplicate(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create([
            'organization_id' => $org->id,
            'name' => 'Badminton - Singles',
        ]);
        $participant = Participant::factory()->create(['organization_id' => $org->id]);
        $rows = new Collection([['event_name' => $event->name]]);

        $first = new EventParticipantImport($participant, $org->id);
        $first->collection($rows);

        $second = new EventParticipantImport($participant, $org->id);
        $second->collection($rows);

        $this->assertSame(1, $first->createdCount());
        $this->assertSame(0, $second->createdCount());
        $this->assertSame(1, EventParticipant::withTrashed()
            ->where('event_id', $event->id)
            ->where('participant_id', $participant->id)
            ->count());
        $this->assertStringContainsString('already registered', implode(' ', $second->errors()));
    }

    public function test_duplicate_approval_cannot_overwrite_a_newer_result_state(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        $match = Fixture::factory()->scheduled()->create(['organization_id' => $org->id]);
        $result = Result::factory()->create([
            'organization_id' => $org->id,
            'match_id' => $match->id,
            'status' => Result::STATUS_SUBMITTED,
        ]);
        $service = app(ResultService::class);

        $service->approve($org, $result->id, $admin);

        try {
            $service->approve($org, $result->id, $admin);
            $this->fail('A duplicate approval should be rejected.');
        } catch (ValidationException) {
            // Expected: the result row is locked and is no longer submitted.
        }

        $this->assertDatabaseHas('results', [
            'id' => $result->id,
            'status' => Result::STATUS_APPROVED,
        ]);
    }

    public function test_locked_result_correction_is_rejected_after_a_stale_editor_attempt(): void
    {
        $org = Organization::factory()->create();
        $admin = $this->createOrgAdmin($org);
        $match = Fixture::factory()->scheduled()->create(['organization_id' => $org->id]);
        $result = Result::factory()->create([
            'organization_id' => $org->id,
            'match_id' => $match->id,
            'score_home' => 1,
            'score_away' => 0,
            'status' => Result::STATUS_SUBMITTED,
        ]);
        $service = app(ResultService::class);

        $service->approve($org, $result->id, $admin);
        $service->lock($org, $result->id, $admin);

        try {
            $service->update($org, $result->id, ['score_home' => 9, 'score_away' => 0]);
            $this->fail('A stale correction must not change a locked result.');
        } catch (ValidationException) {
            // Expected: the locked state wins over the stale editor payload.
        }

        $this->assertDatabaseHas('results', [
            'id' => $result->id,
            'score_home' => 1,
            'score_away' => 0,
            'status' => Result::STATUS_LOCKED,
        ]);
    }

    public function test_schedule_conflict_is_rechecked_inside_the_match_write_transaction(): void
    {
        $org = Organization::factory()->create();
        $event = Event::factory()->create(['organization_id' => $org->id]);
        $service = app(MatchService::class);
        $payload = [
            'event_id' => $event->id,
            'match_number' => 1,
            'venue' => 'Dewan Sukan',
            'scheduled_at' => '2026-10-22 09:00:00',
        ];

        $service->create($org, $payload);

        try {
            $service->create($org, [
                ...$payload,
                'match_number' => 2,
                'scheduled_at' => '2026-10-22 09:30:00',
            ]);
            $this->fail('An overlapping venue schedule should be rejected.');
        } catch (ValidationException) {
            // Expected: conflict validation also runs inside the write transaction.
        }

        $this->assertSame(1, Fixture::where('event_id', $event->id)->count());
    }
}
