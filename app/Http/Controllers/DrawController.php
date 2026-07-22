<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\DrawService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class DrawController extends Controller
{
    public function draw(Event $event, DrawService $drawService): RedirectResponse
    {
        Gate::authorize('update', $event);

        try {
            $result = $drawService->drawAndGenerateFixtures($event);
            return redirect()->route('events.index')
                ->with('success', "Draw completed: {$result['pools']} pools, {$result['participants']} participants, {$result['fixtures']} fixtures generated.");
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('events.index')
                ->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Draw failed', ['event_id' => $event->id, 'error' => $e->getMessage()]);
            return redirect()->route('events.index')
                ->with('error', 'Draw failed: ' . $e->getMessage());
        }
    }

    public function show(Event $event): \Inertia\Response
    {
        Gate::authorize('view', $event);

        $event->load(['tournament', 'sport', 'sportCategory']);

        $pools = $event->pools()
            ->with([
                'eventParticipants.participant',
                'fixtures' => function ($q) {
                    $q->with(['homeParticipant', 'awayParticipant'])
                        ->orderBy('round')
                        ->orderBy('match_number');
                },
            ])
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('DrawResult/Index', [
            'event' => $event,
            'pools' => $pools,
        ]);
    }
}
