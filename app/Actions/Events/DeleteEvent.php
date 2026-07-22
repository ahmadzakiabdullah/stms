<?php

namespace App\Actions\Events;

use App\Models\Event;
use App\Services\EventService;

class DeleteEvent
{
    public function handle(Event $event, ?EventService $service = null): void
    {
        $service = $service ?? app(EventService::class);
        $service->deleteEvent($event);
    }
}
