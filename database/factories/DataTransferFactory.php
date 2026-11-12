<?php

namespace Database\Factories;

use App\Models\DataTransfer;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DataTransfer> */
class DataTransferFactory extends Factory
{
    protected $model = DataTransfer::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'type' => DataTransfer::TYPE_EXPORT_FIXTURES,
            'status' => DataTransfer::STATUS_PENDING,
            'progress' => 0,
            'processed' => 0,
            'total' => null,
        ];
    }
}
