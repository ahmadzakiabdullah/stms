<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index()
    {
        Gate::authorize('view-activity-logs');

        $activities = Activity::with('causer')
            ->where(function ($q) {
                $q->whereHas('causer', function ($sub) {
                    $sub->where('organization_id', Auth::user()->organization_id);
                })->orWhereNull('causer_id');
            })
            ->latest()
            ->paginate(30);

        return Inertia::render('ActivityLogs/Index', [
            'activities' => $activities,
        ]);
    }
}
