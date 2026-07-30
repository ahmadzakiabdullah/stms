<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $query = Event::with(['tournament', 'sport', 'category']);

        if ($request->has('tournament_id')) {
            $query->where('tournament_id', $request->tournament_id);
        }

        $events = $query->paginate(15);

        return EventResource::collection($events);
    }

    public function show(Event $event)
    {
        $event->load(['tournament', 'sport', 'category']);

        return new EventResource($event);
    }
}
