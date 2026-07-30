<?php

namespace App\Actions\Events;

use App\Models\Event;
use App\Services\EventService;

class CreateEvent
{
    public function handle(array $data, ?EventService $service = null): Event
    {
        $service = $service ?? app(EventService::class);

        return $service->createEvent($data);
    }
}
