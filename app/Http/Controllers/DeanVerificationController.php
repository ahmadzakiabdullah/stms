<?php

namespace App\Http\Controllers;

use App\Models\EventParticipant;
use App\Notifications\EventParticipantConfirmed;
use App\Notifications\EventParticipantRejected;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DeanVerificationController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();

        $registrations = $this->safePaginatedQuery(function () use ($user) {
            return EventParticipant::with([
                'event.sport:id,name',
                'event.sportCategory:id,name',
                'event.tournament:id,name',
                'participant:id,name',
            ])
                ->where('participant_id', $user->participant_id)
                ->orderByRaw("FIELD(status, 'pending') DESC")
                ->orderBy('created_at', 'desc')
                ->paginate(20)
                ->withQueryString();
        });

        $counts = $this->safeCollectionQuery(function () use ($user) {
            return EventParticipant::where('participant_id', $user->participant_id)
                ->selectRaw("status, count(*) as total")
                ->groupBy('status')
                ->pluck('total', 'status');
        }, fn() => collect([]));

        return Inertia::render('Dean/Dashboard', [
            'registrations' => $registrations,
            'counts' => $counts,
        ]);
    }

    public function approve(EventParticipant $eventParticipant): RedirectResponse
    {
        $user = Auth::user();

        if ($eventParticipant->participant_id !== $user->participant_id) {
            abort(403, 'You can only verify your own faculty registrations.');
        }

        $eventParticipant->update(['status' => 'confirmed']);

        if ($eventParticipant->participant?->users) {
            foreach ($eventParticipant->participant->users as $user) {
                $user->notify(new EventParticipantConfirmed($eventParticipant));
            }
        }

        return redirect()->route('dean.dashboard')
            ->with('success', 'Registration approved.');
    }

    public function reject(EventParticipant $eventParticipant): RedirectResponse
    {
        $user = Auth::user();

        if ($eventParticipant->participant_id !== $user->participant_id) {
            abort(403, 'You can only verify your own faculty registrations.');
        }

        $eventParticipant->update(['status' => 'rejected']);

        if ($eventParticipant->participant?->users) {
            foreach ($eventParticipant->participant->users as $user) {
                $user->notify(new EventParticipantRejected($eventParticipant));
            }
        }

        return redirect()->route('dean.dashboard')
            ->with('success', 'Registration rejected.');
    }
}
