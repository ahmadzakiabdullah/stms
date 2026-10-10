<?php

namespace App\Http\Controllers;

use App\Models\EventParticipant;
use App\Http\Requests\DeanBulkApprovalRequest;
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
        Gate::authorize('view-dean-dashboard');

        $user = Auth::user();

        $registrations = $this->safePaginatedQuery(function () use ($user) {
            return EventParticipant::with([
                'event.sport:id,name',
                'event.sportCategory:id,name,quota_mode,max_athletes_total,max_male_athletes,max_female_athletes,max_officials',
                'event.tournament:id,name',
                'participant:id,name',
            ])
                ->withCount([
                    'squadMembers',
                    'squadMembers as male_athletes_count' => fn ($query) => $query->where('role', 'athlete_male'),
                    'squadMembers as female_athletes_count' => fn ($query) => $query->where('role', 'athlete_female'),
                    'squadMembers as officials_count' => fn ($query) => $query->whereIn('role', ['assistant_manager', 'manager', 'coach', 'physio']),
                ])
                ->where('participant_id', $user->participant_id)
                ->orderByRaw("FIELD(status, 'pending') DESC")
                ->orderBy('created_at', 'desc')
                ->paginate(20)
                ->withQueryString();
        });

        $counts = $this->safeCollectionQuery(function () use ($user) {
            return EventParticipant::where('participant_id', $user->participant_id)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');
        }, fn () => collect([]));

        return Inertia::render('Dean/Dashboard', [
            'registrations' => $registrations,
            'counts' => $counts,
        ]);
    }

    public function approve(EventParticipant $eventParticipant): RedirectResponse
    {
        Gate::authorize('verify-registration', $eventParticipant);

        $eventParticipant->update(['status' => 'confirmed']);

        if ($eventParticipant->participant?->users) {
            foreach ($eventParticipant->participant->users as $user) {
                $user->notify(new EventParticipantConfirmed($eventParticipant));
            }
        }

        return redirect()->route('dean.dashboard')
            ->with('success', 'Registration approved.');
    }

    public function approveBulk(DeanBulkApprovalRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $registrations = EventParticipant::whereIn('id', $request->validated('event_participant_ids'))
            ->where('participant_id', $user->participant_id)
            ->where('status', EventParticipant::STATUS_PENDING)
            ->get();

        foreach ($registrations as $registration) {
            Gate::authorize('verify-registration', $registration);
            $registration->update(['status' => EventParticipant::STATUS_CONFIRMED]);
        }

        return redirect()->route('dean.dashboard')
            ->with('success', $registrations->count().' registrations approved.');
    }

    public function reject(EventParticipant $eventParticipant): RedirectResponse
    {
        Gate::authorize('verify-registration', $eventParticipant);

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
