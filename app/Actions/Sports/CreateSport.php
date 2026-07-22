<?php

namespace App\Actions\Sports;

use App\Models\Sport;
use App\Services\SportService;

class CreateSport
{
    public function handle(array $data, ?SportService $service = null): Sport
    {
        $service = $service ?? app(SportService::class);
        return $service->createSport($data);
    }
}
