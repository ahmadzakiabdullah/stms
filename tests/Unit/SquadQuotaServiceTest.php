<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Participant;
use App\Models\SquadMember;
use App\Services\SquadQuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SquadQuotaServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_total_quota_allows_any_gender_mix_up_to_total(): void
    {
        $eventParticipant = $this->eventParticipantForCategory([
            'quota_mode' => 'open_total',
            'max_athletes_total' => 6,
            'max_male_athletes' => 6,
            'max_female_athletes' => 6,
        ]);

        $service = new SquadQuotaService;

        for ($i = 0; $i < 6; $i++) {
            $this->assertNull($service->validateAddition($eventParticipant, 'athlete_male'));
            $this->createSquadMember($eventParticipant, 'athlete_male', "Player {$i}");
        }

        $this->assertSame(
            'Athlete quota full (6/6).',
            $service->validateAddition($eventParticipant, 'athlete_female')
        );
    }

    public function test_mixed_total_quota_enforces_minimum_gender_mix_when_roster_reaches_total(): void
    {
        $eventParticipant = $this->eventParticipantForCategory([
            'quota_mode' => 'mixed_total',
            'max_athletes_total' => 6,
            'max_male_athletes' => 6,
            'max_female_athletes' => 6,
            'min_male_athletes' => 2,
            'min_female_athletes' => 2,
        ]);

        $service = new SquadQuotaService;

        foreach (['athlete_male', 'athlete_male', 'athlete_male', 'athlete_male', 'athlete_female'] as $index => $role) {
            $this->createSquadMember($eventParticipant, $role, "Player {$index}");
        }

        $this->assertNull($service->validateAddition($eventParticipant, 'athlete_female'));

        $eventParticipant = $this->eventParticipantForCategory([
            'quota_mode' => 'mixed_total',
            'max_athletes_total' => 6,
            'max_male_athletes' => 6,
            'max_female_athletes' => 6,
            'min_male_athletes' => 2,
            'min_female_athletes' => 2,
        ]);

        foreach (['athlete_male', 'athlete_male', 'athlete_male', 'athlete_male', 'athlete_male'] as $index => $role) {
            $this->createSquadMember($eventParticipant, $role, "Player {$index}");
        }

        $this->assertStringContainsString(
            'Athlete mix does not meet the required minimum gender quota',
            $service->validateAddition($eventParticipant, 'athlete_female')
        );
    }

    private function eventParticipantForCategory(array $categoryAttributes): EventParticipant
    {
        $event = Event::factory()->create();
        $event->sportCategory->update($categoryAttributes);

        $participant = Participant::factory()->create([
            'organization_id' => $event->organization_id,
            'session_id' => $event->tournament->session_id,
        ]);

        return EventParticipant::create([
            'organization_id' => $event->organization_id,
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'status' => 'confirmed',
        ])->load('event.sportCategory');
    }

    private function createSquadMember(EventParticipant $eventParticipant, string $role, string $name): SquadMember
    {
        return SquadMember::create([
            'event_participant_id' => $eventParticipant->id,
            'organization_id' => $eventParticipant->organization_id,
            'name' => $name,
            'role' => $role,
        ]);
    }
}
