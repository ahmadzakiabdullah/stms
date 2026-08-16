<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        $supportedLocales = config('app.supported_locales', []);
        if (! is_array($supportedLocales) || $supportedLocales === []) {
            $supportedLocales = ['en', 'ms'];
        }

        $shared = [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? (function () use ($user) {
                    try {
                        return $user->loadMissing('roles');
                    } catch (\Throwable $e) {
                        $this->logShareFallback($request, 'auth_user_roles', $e);

                        return $user;
                    }
                })() : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'locale' => app()->getLocale(),
            'locales' => collect(config('app.locale_labels', []))
                ->only($supportedLocales)
                ->map(fn (string $label, string $code): array => [
                    'code' => $code,
                    'label' => $label,
                ])
                ->values(),
            'settings' => function () use ($request) {
                $orgId = $request->user()?->organization_id
                    ?? Organization::where('is_active', true)->value('id');

                return $orgId
                    ? Setting::where('organization_id', $orgId)->pluck('value', 'key')->toArray()
                    : [];
            },
            'app' => function () use ($request) {
                $orgId = $request->user()?->organization_id
                    ?? Organization::where('is_active', true)->value('id');
                $appName = $orgId
                    ? Setting::where('organization_id', $orgId)->where('key', 'app_name')->value('value')
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
                $this->logShareFallback($request, 'super_admin_role', $e);
            }

            try {
                $shared['currentOrganization'] = $user->organization;
            } catch (\Throwable $e) {
                $shared['currentOrganization'] = null;
                $this->logShareFallback($request, 'current_organization', $e);
            }

            $shared['isSuperAdmin'] = $isSuperAdmin;

            try {
                $shared['isFacultyRep'] = $user->hasRole('faculty-representative') && $user->participant_id;
            } catch (\Throwable $e) {
                $shared['isFacultyRep'] = false;
                $this->logShareFallback($request, 'faculty_representative_role', $e);
            }

            try {
                $shared['isDean'] = $user->hasRole('dean') && $user->participant_id;
            } catch (\Throwable $e) {
                $shared['isDean'] = false;
                $this->logShareFallback($request, 'dean_role', $e);
            }

            // For super admins, provide all active organizations for switching/context
            if ($isSuperAdmin) {
                try {
                    $shared['organizations'] = Organization::query()
                        ->active()
                        ->orderBy('name')
                        ->get(['id', 'name', 'slug']);
                } catch (\Throwable $e) {
                    $shared['organizations'] = [];
                    $this->logShareFallback($request, 'organizations', $e);
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
                $this->logShareFallback($request, 'notifications', $e);
            }
        }

        return $shared;
    }

    private function logShareFallback(Request $request, string $operation, \Throwable $exception): void
    {
        Log::warning('Inertia shared prop fallback activated.', [
            'operation' => $operation,
            'exception' => $exception,
            'correlation_id' => $request->attributes->get('correlation_id'),
            'user_id' => $request->user()?->uuid,
            'organization_id' => $request->user()?->organization_id,
            'route' => $request->path(),
        ]);
    }
}
