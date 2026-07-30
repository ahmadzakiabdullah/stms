<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Fixture;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fixture>
 */
class FixtureFactory extends Factory
{
    protected $model = Fixture::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'event_id' => Event::factory(),
            'match_number' => fake()->unique()->numberBetween(1, 100),
            'home_participant_id' => null,
            'away_participant_id' => null,
            'venue' => fake()->optional()->city(),
            'scheduled_at' => fake()->optional()->dateTimeBetween('now', '+30 days'),
            'status' => fake()->randomElement(['scheduled', 'in_progress', 'completed', 'cancelled']),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function forOrganization(Organization $organization): static
    {
        return $this->state(fn (array $attributes) => [
            'organization_id' => $organization->id,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn () => ['status' => 'scheduled']);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => 'completed']);
    }
}
