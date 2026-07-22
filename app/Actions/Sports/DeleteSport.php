<?php

namespace App\Actions\Sports;

use App\Models\Sport;
use App\Services\SportService;

class DeleteSport
{
    public function handle(Sport $sport, ?SportService $service = null): void
    {
        $service = $service ?? app(SportService::class);
        $service->deleteSport($sport);
    }
}
