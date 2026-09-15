<?php

namespace App\Actions\Sports;

use App\Models\Sport;
use App\Services\SportService;

class UpdateSport
{
    public function handle(Sport $sport, array $data, ?SportService $service = null): Sport
    {
        $service = $service ?? app(SportService::class);
        return $service->updateSport($sport, $data);
    }
}
