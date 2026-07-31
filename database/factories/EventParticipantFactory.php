<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Organization;
use App\Models\Participant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventParticipant>
 */
class EventParticipantFactory extends Factory
{
    protected $model = EventParticipant::class;

    public function definition(): array
    {
        $organization = Organization::factory();

        return [
            'organization_id' => $organization,
            'event_id' => Event::factory()->state(['organization_id' => $organization]),
            'participant_id' => Participant::factory()->state(['organization_id' => $organization]),
            'status' => 'confirmed',
            'registration_date' => now(),
        ];
    }
}
