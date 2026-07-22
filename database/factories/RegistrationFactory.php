<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Participant;
use App\Models\Registration;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Registration>
 */
class RegistrationFactory extends Factory
{
    protected $model = Registration::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'tournament_id' => Tournament::factory(),
            'participant_id' => Participant::factory(),
            'status' => fake()->randomElement(['pending', 'confirmed', 'rejected', 'cancelled']),
            'registered_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function forOrganization(Organization $organization): static
    {
        return $this->state(fn (array $attributes) => [
            'organization_id' => $organization->id,
        ]);
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function confirmed(): static
    {
        return $this->state(['status' => 'confirmed']);
    }
}
