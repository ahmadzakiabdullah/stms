<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ParticipantDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', EventParticipant::class);

        $sportId = $request->input('sport_id');
        $facultyId = $request->input('faculty_id');
        $status = $request->input('status');

        $totalRegistrations = EventParticipant::count();
        $pendingCount = EventParticipant::where('status', 'pending')->count();
        $confirmedCount = EventParticipant::where('status', 'confirmed')->count();
        $totalFaculties = Participant::where('is_active', true)->count();
        $totalEvents = Event::where('is_active', true)->count();

        $facultyStats = $this->safeCollectionQuery(function () use ($sportId, $status) {
            return Participant::where('is_active', true)
                ->withCount(['eventParticipants as total' => function ($q) use ($sportId, $status) {
                    if ($sportId) $q->whereHas('event', fn($q) => $q->where('sport_id', $sportId));
                    if ($status) $q->where('status', $status);
                }])
                ->withCount(['eventParticipants as pending' => fn($q) => $q->where('status', 'pending')])
                ->withCount(['eventParticipants as confirmed' => fn($q) => $q->where('status', 'confirmed')])
                ->withCount(['eventParticipants as rejected' => fn($q) => $q->where('status', 'rejected')])
                ->orderBy('name')
                ->get(['id', 'name']);
        }, fn() => collect());

        $eventStats = $this->safeCollectionQuery(function () use ($facultyId, $status) {
            return Event::where('is_active', true)
                ->with(['sport', 'sportCategory', 'tournament'])
                ->withCount(['eventParticipants as total' => function ($q) use ($facultyId, $status) {
                    if ($facultyId) $q->where('participant_id', $facultyId);
                    if ($status) $q->where('status', $status);
                }])
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString();
        }, fn() => collect());

        $sports = $this->safeCollectionQuery(function () {
            return \App\Models\Sport::orderBy('name')->get(['id', 'name']);
        }, fn() => collect());

        $faculties = $this->safeCollectionQuery(function () {
            return Participant::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        }, fn() => collect());

        return Inertia::render('ParticipantDashboard/Index', [
            'stats' => [
                'totalRegistrations' => $totalRegistrations,
                'pending' => $pendingCount,
                'confirmed' => $confirmedCount,
                'totalFaculties' => $totalFaculties,
                'totalEvents' => $totalEvents,
            ],
            'facultyStats' => $facultyStats,
            'eventStats' => $eventStats,
            'sports' => $sports,
            'faculties' => $faculties,
        ]);
    }
}
