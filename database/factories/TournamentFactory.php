<?php

namespace Database\Factories;

use App\Models\Session;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tournament>
 */
class TournamentFactory extends Factory
{
    protected $model = Tournament::class;

    public function definition(): array
    {
        $name = fake()->randomElement([
            "Men's Football", "Women's Badminton", "Open Futsal", "Volleyball Championship"
        ]);

        $session = Session::factory()->create(); // ensure we have a session

        return [
            'organization_id' => $session->organization_id,
            'session_id' => $session->id,
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(4),
            'description' => fake()->sentence(),
            'start_date' => $session->start_date,
            'end_date' => $session->end_date,
            'is_active' => true,
        ];
    }

    public function forSession(Session $session): static
    {
        return $this->state(fn (array $attributes) => [
            'organization_id' => $session->organization_id,
            'session_id' => $session->id,
            'start_date' => $session->start_date,
            'end_date' => $session->end_date,
        ]);
    }
}
