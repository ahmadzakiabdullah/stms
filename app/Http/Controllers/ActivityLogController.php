<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('view-activity-logs');

        $user = Auth::user();
        $isSuperAdmin = $user->hasRole('super-admin');
        $filters = $request->validate([
            'organization_id' => 'nullable|uuid|exists:organizations,id',
            'event' => 'nullable|string|max:50',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $activities = Activity::with('causer')
            ->when(! $isSuperAdmin, fn ($query) => $query->where(function ($scope) use ($user) {
                $scope->whereHas('causer', fn ($causer) => $causer->where('organization_id', $user->organization_id))
                    ->orWhereNull('causer_id');
            }))
            ->when($isSuperAdmin && ! empty($filters['organization_id']), fn ($query) => $query
                ->whereHas('causer', fn ($causer) => $causer->where('organization_id', $filters['organization_id'])))
            ->when(! empty($filters['event']), fn ($query) => $query->where('event', $filters['event']))
            ->when(! empty($filters['from']), fn ($query) => $query->whereDate('created_at', '>=', $filters['from']))
            ->when(! empty($filters['to']), fn ($query) => $query->whereDate('created_at', '<=', $filters['to']))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('ActivityLogs/Index', [
            'activities' => $activities,
            'filters' => [
                'organization_id' => $isSuperAdmin ? ($filters['organization_id'] ?? '') : '',
                'event' => $filters['event'] ?? '',
                'from' => $filters['from'] ?? '',
                'to' => $filters['to'] ?? '',
            ],
            'isSuperAdmin' => $isSuperAdmin,
            'organizations' => $isSuperAdmin
                ? Organization::query()->active()->orderBy('name')->get(['id', 'name'])
                : [],
        ]);
    }
}
