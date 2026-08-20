<?php

namespace App\Http\Controllers;

use App\Models\DrawVersion;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Fixture;
use App\Services\DrawService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DrawController extends Controller
{
    public function draw(Request $request, Event $event, DrawService $drawService): RedirectResponse
    {
        Gate::authorize('update', $event);

        $format = $request->input('format', 'group_knockout');
        if (! in_array($format, ['league', 'group_knockout', 'knockout'], true)) {
            $format = 'group_knockout';
        }

        $incompleteSquads = EventParticipant::query()
            ->where('event_id', $event->id)
            ->where('status', 'confirmed')
            ->with('participant:id,name')
            ->get()
            ->filter(fn (EventParticipant $ep) => $ep->squadMembers()
                ->whereIn('role', ['athlete_male', 'athlete_female'])
                ->count() === 0)
            ->pluck('participant.name')
            ->filter()
            ->values();

        if ($incompleteSquads->isNotEmpty()) {
            return redirect()->route('events.draw-result', $event)
                ->with('error', 'Draw blocked: '.$incompleteSquads->implode(', ').' '.($incompleteSquads->count() === 1 ? 'has' : 'have').' a confirmed registration but no athletes yet. Complete the squads before drawing.');
        }

        try {
            $result = $drawService->drawGroups($event);

            $event->update(['format' => $format]);

            activity()
                ->performedOn($event)
                ->causedBy(Auth::user())
                ->event('draw')
                ->withProperties($result)
                ->log("Draw created for '{$event->name}': {$result['pools']} groups, {$result['participants']} participants");

            return redirect()->route('events.draw-result', $event)
                ->with('success', "Draw created: {$result['pools']} ".($result['pools'] === 1 ? 'group' : 'groups')." with {$result['participants']} participants. Review the groups, then generate fixtures.");
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('events.draw-result', $event)
                ->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Draw failed', ['event_id' => $event->id, 'error' => $e->getMessage()]);

            return redirect()->route('events.draw-result', $event)
                ->with('error', 'Draw failed: '.$e->getMessage());
        }
    }

    public function generateFixtures(Event $event, DrawService $drawService): RedirectResponse
    {
        Gate::authorize('update', $event);

        try {
            $result = $drawService->generateFixtures($event);

            activity()
                ->performedOn($event)
                ->causedBy(Auth::user())
                ->event('generate_fixtures')
                ->withProperties($result)
                ->log("Fixtures generated for '{$event->name}': {$result['fixtures']} fixtures across {$result['pools']} groups");

            return redirect()->route('events.draw-result', $event)
                ->with('success', "Fixtures generated: {$result['fixtures']} ".($result['fixtures'] === 1 ? 'fixture' : 'fixtures')." across {$result['pools']} ".($result['pools'] === 1 ? 'group' : 'groups').'.');
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('events.draw-result', $event)
                ->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Fixture generation failed', ['event_id' => $event->id, 'error' => $e->getMessage()]);

            return redirect()->route('events.draw-result', $event)
                ->with('error', 'Fixture generation failed: '.$e->getMessage());
        }
    }

    public function resetDraw(Event $event, DrawService $drawService): RedirectResponse
    {
        Gate::authorize('update', $event);

        $hasStartedMatches = Fixture::where('event_id', $event->id)
            ->whereIn('status', ['in_progress', 'completed'])
            ->exists();

        if ($hasStartedMatches) {
            return redirect()->route('events.draw-result', $event)
                ->with('error', 'Cannot reset the draw after a match has started.');
        }

        try {
            $drawService->resetDraw($event);

            activity()
                ->performedOn($event)
                ->causedBy(Auth::user())
                ->event('reset_draw')
                ->log("Draw reset for '{$event->name}'");

            return redirect()->route('events.draw-result', $event)
                ->with('success', 'Draw reset. All groups and fixtures were removed.');
        } catch (\Throwable $e) {
            Log::error('Draw reset failed', ['event_id' => $event->id, 'error' => $e->getMessage()]);

            return redirect()->route('events.draw-result', $event)
                ->with('error', 'Draw reset failed: '.$e->getMessage());
        }
    }

    public function show(Event $event): Response
    {
        Gate::authorize('view', $event);

        $event->load(['tournament', 'sport', 'sportCategory']);

        $pools = $event->pools()
            ->with([
                'eventParticipants' => function ($query) {
                    $query->with('participant')
                        ->orderByRaw('CASE WHEN seed_number IS NULL THEN 1 ELSE 0 END')
                        ->orderBy('seed_number')
                        ->orderBy('created_at');
                },
                'fixtures' => function ($q) {
                    $q->with(['homeParticipant', 'awayParticipant', 'result'])
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
            'drawVersions' => $event->drawVersions()
                ->with('actor:uuid,name')
                ->take(20)
                ->get(['id', 'event_id', 'actor_id', 'version', 'action', 'seed', 'created_at']),
        ]);
    }

    public function rollback(Event $event, DrawVersion $drawVersion, DrawService $drawService): RedirectResponse
    {
        Gate::authorize('update', $event);

        try {
            $drawService->rollback($event, $drawVersion);

            activity()->performedOn($event)->causedBy(Auth::user())
                ->event('rollback_draw')
                ->withProperties(['draw_version_id' => $drawVersion->id, 'version' => $drawVersion->version])
                ->log("Draw rolled back for '{$event->name}' to version {$drawVersion->version}");

            return redirect()->route('events.draw-result', $event)
                ->with('success', "Draw restored from version {$drawVersion->version}.");
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('events.draw-result', $event)->with('error', $e->getMessage());
        }
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
            'event_participant_id' => [
                'required', 'uuid',
                Rule::exists('event_participants', 'id')->where(fn ($query) => $query
                    ->where('event_id', $event->id)
                    ->where('organization_id', $event->organization_id)),
            ],
            'target_pool_id' => [
                'required', 'uuid',
                Rule::exists('pools', 'id')->where(fn ($query) => $query
                    ->where('event_id', $event->id)
                    ->where('organization_id', $event->organization_id)),
            ],
            'seed_number' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($request->filled('seed_number')) {
            $duplicate = EventParticipant::query()
                ->where('event_id', $event->id)
                ->where('pool_id', $request->input('target_pool_id'))
                ->where('id', '!=', $request->input('event_participant_id'))
                ->where('seed_number', $request->integer('seed_number'))
                ->exists();

            if ($duplicate) {
                return redirect()->route('events.draw-result', $event)
                    ->with('error', 'Position #'.$request->integer('seed_number').' is already assigned in this group. Choose another position.');
            }
        }

        try {
            $fixturesRegenerated = $drawService->moveParticipantToPool(
                $event,
                $request->input('event_participant_id'),
                $request->input('target_pool_id'),
                $request->integer('seed_number') ?: null,
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
                ->with('success', $fixturesRegenerated
                    ? 'Participant moved and fixtures regenerated.'
                    : 'Participant moved and group assignment saved.');
        } catch (\Throwable $e) {
            Log::error('Move participant failed', ['event_id' => $event->id, 'error' => $e->getMessage()]);

            return redirect()->route('events.draw-result', $event)
                ->with('error', $e->getMessage());
        }
    }
}
