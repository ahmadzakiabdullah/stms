<?php

namespace App\Actions\Events;

use App\Models\Event;
use App\Services\EventService;

class UpdateEvent
{
    public function handle(Event $event, array $data, ?EventService $service = null): Event
    {
        $service = $service ?? app(EventService::class);

        return $service->updateEvent($event, $data);
    }
}
