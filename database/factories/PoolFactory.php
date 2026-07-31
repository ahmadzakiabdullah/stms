<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Pool;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pool>
 */
class PoolFactory extends Factory
{
    protected $model = Pool::class;

    public function definition(): array
    {
        $organization = Organization::factory();

        return [
            'organization_id' => $organization,
            'event_id' => Event::factory()->state(['organization_id' => $organization]),
            'name' => 'Group A',
            'sort_order' => 0,
        ];
    }
}
