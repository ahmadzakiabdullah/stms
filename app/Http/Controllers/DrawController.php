<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Fixture;
use App\Services\DrawService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class DrawController extends Controller
{
    public function draw(Event $event, DrawService $drawService): RedirectResponse
    {
        Gate::authorize('update', $event);

        try {
            $result = $drawService->drawAndGenerateFixtures($event);

            activity()
                ->performedOn($event)
                ->causedBy(Auth::user())
                ->event('draw')
                ->withProperties($result)
                ->log("Draw completed for '{$event->name}': {$result['pools']} pools, {$result['fixtures']} fixtures");

            return redirect()->route('events.index')
                ->with('success', "Draw completed: {$result['pools']} pools, {$result['participants']} participants, {$result['fixtures']} fixtures generated.");
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('events.index')
                ->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Draw failed', ['event_id' => $event->id, 'error' => $e->getMessage()]);

            return redirect()->route('events.index')
                ->with('error', 'Draw failed: '.$e->getMessage());
        }
    }

    public function show(Event $event): Response
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

        $hasStartedMatches = Fixture::where('event_id', $event->id)
            ->whereIn('status', ['in_progress', 'completed'])
            ->exists();

        return Inertia::render('DrawResult/Index', [
            'event' => $event,
            'pools' => $pools,
            'canEdit' => ! $hasStartedMatches,
        ]);
    }

    public function moveParticipant(Request $request, Event $event, DrawService $drawService): RedirectResponse
    {
        Gate::authorize('update', $event);

        $hasStartedMatches = Fixture::where('event_id', $event->id)
            ->whereIn('status', ['in_progress', 'completed'])
            ->exists();

        if ($hasStartedMatches) {
            return redirect()->route('events.draw-result', $event)
                ->with('error', 'Cannot modify pools after a match has started.');
        }

        $request->validate([
            'event_participant_id' => ['required', 'uuid', 'exists:event_participants,id'],
            'target_pool_id' => ['required', 'uuid', 'exists:pools,id'],
        ]);

        try {
            $drawService->moveParticipantToPool(
                $event,
                $request->input('event_participant_id'),
                $request->input('target_pool_id')
            );

            activity()
                ->performedOn($event)
                ->causedBy(Auth::user())
                ->event('move_participant')
                ->withProperties([
                    'event_participant_id' => $request->input('event_participant_id'),
                    'target_pool_id' => $request->input('target_pool_id'),
                ])
                ->log("Participant moved to different pool in '{$event->name}'");

            return redirect()->route('events.draw-result', $event)
                ->with('success', 'Participant moved and fixtures regenerated.');
        } catch (\Throwable $e) {
            Log::error('Move participant failed', ['event_id' => $event->id, 'error' => $e->getMessage()]);

            return redirect()->route('events.draw-result', $event)
                ->with('error', $e->getMessage());
        }
    }
}
