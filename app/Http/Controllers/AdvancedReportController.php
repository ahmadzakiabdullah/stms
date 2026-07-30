<?php

namespace App\Http\Controllers;

use App\Models\EventParticipant;
use App\Models\Participant;
use App\Models\Result;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdvancedReportController extends Controller
{
    public function index(): Response
    {
        $organizationId = auth()->user()->organization_id;

        // 1. Participant Status Distribution (Pie Chart)
        $participantStatus = EventParticipant::where('organization_id', $organizationId)
            ->select('status', DB::raw('count(*) as value'))
            ->groupBy('status')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => ucfirst($item->status),
                    'value' => $item->value,
                ];
            });

        // 2. Medals / Results by Faculty (Bar Chart)
        // Simplified query for demo - we'll just count total wins per participant
        $topPerformers = Result::where('organization_id', $organizationId)
            ->whereNotNull('winner_id')
            ->join('event_participants', 'results.winner_id', '=', 'event_participants.id')
            ->join('participants', 'event_participants.participant_id', '=', 'participants.id')
            ->select('participants.name', DB::raw('count(results.id) as wins'))
            ->groupBy('participants.name')
            ->orderByDesc('wins')
            ->limit(10)
            ->get();

        // 3. Gender/Demographic split
        $demographics = Participant::where('organization_id', $organizationId)
            ->select('gender', DB::raw('count(*) as count'))
            ->groupBy('gender')
            ->get();

        return Inertia::render('Reports/AdvancedDashboard', [
            'participantStatus' => $participantStatus,
            'topPerformers' => $topPerformers,
            'demographics' => $demographics,
        ]);
    }
}
