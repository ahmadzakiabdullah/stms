<?php

namespace App\Http\Controllers;

use App\Models\EventParticipant;
use App\Services\TeamRegistrationFormService;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TeamRegistrationFormController extends Controller
{
    public function show(EventParticipant $eventParticipant, TeamRegistrationFormService $service): Response
    {
        Gate::authorize('view', $eventParticipant);

        $user = request()->user();
        if ($user->hasAnyRole(['faculty-representative', 'dean'])) {
            abort_unless($user->participant_id === $eventParticipant->participant_id, 403);
        }

        return Inertia::render('TeamRegistrationForms/Show', $service->build($eventParticipant));
    }
}
