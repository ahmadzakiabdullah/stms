<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Session;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Session>
 */
class SessionFactory extends Factory
{
    protected $model = Session::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 month', '+6 months');
        $end = (clone $start)->modify('+14 days');

        $name = 'SUKMA / Event '.$start->format('Y');

        return [
            'organization_id' => Organization::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
            'description' => fake()->sentence(),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
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
