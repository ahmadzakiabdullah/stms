<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        // Only super-admins should view this, handled by middleware on the route
        $logs = Activity::with('causer')
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->through(fn ($log) => [
                'id' => $log->id,
                'log_name' => $log->log_name,
                'description' => $log->description,
                'subject_type' => class_basename($log->subject_type),
                'subject_id' => $log->subject_id,
                'causer' => $log->causer ? $log->causer->name : 'System',
                'properties' => $log->properties,
                'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                'created_at_human' => $log->created_at->diffForHumans(),
            ]);

        return Inertia::render('ActivityLogs/Index', [
            'logs' => $logs,
        ]);
    }
}
