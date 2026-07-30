<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return config('app.asset_version', parent::version($request));
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        $shared = [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? (function () use ($user) {
                    try {
                        return $user->loadMissing('roles');
                    } catch (\Throwable $e) {
                        return $user;
                    }
                })() : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'settings' => function () use ($request) {
                $orgId = $request->user()?->organization_id
                    ?? \App\Models\Organization::where('is_active', true)->value('id');
                return $orgId
                    ? \App\Models\Setting::where('organization_id', $orgId)->pluck('value', 'key')->toArray()
                    : [];
            },
            'app' => function () use ($request) {
                $orgId = $request->user()?->organization_id
                    ?? \App\Models\Organization::where('is_active', true)->value('id');
                $appName = $orgId
                    ? \App\Models\Setting::where('organization_id', $orgId)->where('key', 'app_name')->value('value')
                    : null;
                return [
                    'name' => $appName ?? config('app.name', 'STMS Portal'),
                ];
            },
        ];

        if ($user) {
            $isSuperAdmin = false;
            try {
                $isSuperAdmin = $user->hasRole('super-admin');
            } catch (\Throwable $e) {
                $isSuperAdmin = false;
            }

            try {
                $shared['currentOrganization'] = $user->organization;
            } catch (\Throwable $e) {
                $shared['currentOrganization'] = null;
            }

            $shared['isSuperAdmin'] = $isSuperAdmin;

            try {
                $shared['isFacultyRep'] = $user->hasRole('faculty-representative') && $user->participant_id;
            } catch (\Throwable $e) {
                $shared['isFacultyRep'] = false;
            }

            try {
                $shared['isDean'] = $user->hasRole('dean') && $user->participant_id;
            } catch (\Throwable $e) {
                $shared['isDean'] = false;
            }

            // For super admins, provide all active organizations for switching/context
            if ($isSuperAdmin) {
                try {
                    $shared['organizations'] = \App\Models\Organization::query()
                        ->active()
                        ->orderBy('name')
                        ->get(['id', 'name', 'slug']);
                } catch (\Throwable $e) {
                    $shared['organizations'] = [];
                }
            }

            try {
                $shared['notification_count'] = $user->unreadNotifications()->count();
                $shared['notifications'] = $user->notifications()->take(5)->get()->map(function ($n) {
                    return [
                        'id' => $n->id,
                        'data' => $n->data,
                        'read_at' => $n->read_at,
                        'created_at' => $n->created_at->diffForHumans(),
                    ];
                });
            } catch (\Throwable $e) {
                $shared['notification_count'] = 0;
                $shared['notifications'] = [];
            }
        }

        return $shared;
    }
}
