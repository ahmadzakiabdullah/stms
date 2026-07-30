<?php

namespace Database\Factories;

use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Result;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Result>
 */
class ResultFactory extends Factory
{
    protected $model = Result::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'match_id' => Fixture::factory(),
            'score_home' => fake()->numberBetween(0, 10),
            'score_away' => fake()->numberBetween(0, 10),
            'winner_participant_id' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function forOrganization(Organization $organization): static
    {
        return $this->state(fn (array $attributes) => [
            'organization_id' => $organization->id,
        ]);
    }

    public function draw(): static
    {
        return $this->state(function () {
            $score = fake()->numberBetween(0, 10);

            return [
                'score_home' => $score,
                'score_away' => $score,
                'winner_participant_id' => null,
            ];
        });
    }
}
