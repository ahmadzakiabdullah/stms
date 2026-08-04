<?php

namespace App\Http\Controllers;

use App\Services\ParticipationConfirmationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ParticipationConfirmationController extends Controller
{
    public function index(Request $request, ParticipationConfirmationService $service): Response
    {
        Gate::authorize('view-participation-confirmations');

        return Inertia::render('ParticipationConfirmations/Index', $service->build(
            $request->user(),
            $request->only(['participant_id', 'session_id']),
        ));
    }
}
