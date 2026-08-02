<?php

namespace Database\Factories;

use App\Models\EventParticipant;
use App\Models\Organization;
use App\Models\SquadMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SquadMember>
 */
class SquadMemberFactory extends Factory
{
    protected $model = SquadMember::class;

    public function definition(): array
    {
        return [
            'event_participant_id' => EventParticipant::factory(),
            'organization_id' => Organization::factory(),
            'name' => fake()->name(),
            'role' => fake()->randomElement(['athlete_male', 'athlete_female', 'assistant_manager', 'manager', 'coach', 'physio']),
            'matrix_no' => fake()->optional()->numerify('A####'),
            'identification_no' => fake()->optional()->numerify('9#######'),
            'phone' => fake()->optional()->phoneNumber(),
            'is_active' => true,
        ];
    }
}
