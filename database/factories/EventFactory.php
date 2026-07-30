<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $tournament = Tournament::factory()->create();
        $sport = Sport::factory()->create(['organization_id' => $tournament->organization_id]);
        $category = SportCategory::factory()->forSport($sport)->create();

        $name = $sport->name.' - '.$category->name;

        return [
            'organization_id' => $tournament->organization_id,
            'tournament_id' => $tournament->id,
            'sport_id' => $sport->id,
            'sport_category_id' => $category->id,
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
            'description' => fake()->optional()->sentence(),
            'start_date' => $tournament->start_date,
            'end_date' => $tournament->end_date,
            'is_active' => true,
        ];
    }

    public function forTournament(Tournament $tournament): static
    {
        $sport = Sport::factory()->create(['organization_id' => $tournament->organization_id]);
        $category = SportCategory::factory()->forSport($sport)->create();

        return $this->state(fn (array $attributes) => [
            'organization_id' => $tournament->organization_id,
            'tournament_id' => $tournament->id,
            'sport_id' => $sport->id,
            'sport_category_id' => $category->id,
            'start_date' => $tournament->start_date,
            'end_date' => $tournament->end_date,
        ]);
    }
}
