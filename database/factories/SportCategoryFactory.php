<?php

namespace Database\Factories;

use App\Models\Sport;
use App\Models\SportCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SportCategory>
 */
class SportCategoryFactory extends Factory
{
    protected $model = SportCategory::class;

    public function definition(): array
    {
        $name = fake()->randomElement([
            "Men's Open", "Women's Open", 'U-21', 'U-18', 'Mixed',
            "Men's Team", "Women's Team", 'Singles', 'Doubles',
        ]);

        return [
            'organization_id' => null, // will be set via sport relationship in factory state if needed
            'sport_id' => Sport::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
        ];
    }

    public function forSport(Sport $sport): static
    {
        return $this->state(fn (array $attributes) => [
            'organization_id' => $sport->organization_id,
            'sport_id' => $sport->id,
        ]);
    }
}
