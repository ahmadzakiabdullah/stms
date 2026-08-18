<?php

namespace App\Http\Controllers;

use App\Services\DashboardDataService;
use App\Services\FacultyDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(
        Request $request,
        DashboardDataService $dashboardData,
        FacultyDashboardService $facultyDashboard,
    ): Response|RedirectResponse {
        Gate::authorize('view-dashboard');

        $user = $request->user();
        abort_unless($user, 401);

        try {
            $isSuper = $user->hasRole('super-admin');
        } catch (\Throwable $exception) {
            $isSuper = false;
            Log::warning('Dashboard role check fallback activated.', [
                'exception' => $exception,
                'correlation_id' => $request->attributes->get('correlation_id'),
                'user_id' => $user->uuid,
                'organization_id' => $user->organization_id,
                'route' => $request->path(),
            ]);
        }

        $hasParticipant = ! is_null($user->participant_id);

        if ($hasParticipant && $user->hasRole('faculty-representative')) {
            return Inertia::render('Faculty/Dashboard', $facultyDashboard->dataFor($user));
        }

        if ($hasParticipant && $user->hasRole('dean')) {
            return redirect()->route('dean.dashboard');
        }

        return Inertia::render('Dashboard', $dashboardData->dataFor(
            $user,
            $request->only(['sport_id', 'faculty_id', 'status']),
            $isSuper,
        ));
    }
}
