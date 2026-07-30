<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Sport;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Sport>
 */
class SportFactory extends Factory
{
    protected $model = Sport::class;

    public function definition(): array
    {
        $name = fake()->randomElement([
            'Football', 'Badminton', 'Volleyball', 'Futsal', 'Table Tennis',
            'Basketball', 'Swimming', 'Athletics', 'Hockey', 'Tennis',
        ]);

        return [
            'organization_id' => Organization::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
            'icon' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
