<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Setting;
use App\Services\PublicPortalService;
use App\Services\SettingAssetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function index()
    {
        Gate::authorize('view-settings');

        $settings = Setting::where('organization_id', Auth::user()->organization_id)
            ->pluck('value', 'key')
            ->toArray();

        return Inertia::render('Settings/Index', [
            'settings' => [
                'app_name' => $settings['app_name'] ?? config('app.name', 'STMS Portal'),
                'logo_url' => $settings['logo_url'] ?? null,
                'inverse_logo_url' => $settings['inverse_logo_url'] ?? null,
                'favicon_url' => $settings['favicon_url'] ?? null,
                'tournament_logo_url' => $settings['tournament_logo_url'] ?? null,
                'secretariat_address' => $settings['secretariat_address'] ?? '',
                'secretariat_email' => $settings['secretariat_email'] ?? '',
                'secretariat_phone' => $settings['secretariat_phone'] ?? '',
                'secretariat_facebook_url' => $settings['secretariat_facebook_url'] ?? '',
                'secretariat_instagram_url' => $settings['secretariat_instagram_url'] ?? '',
                'secretariat_tiktok_url' => $settings['secretariat_tiktok_url'] ?? '',
                'secretariat_youtube_url' => $settings['secretariat_youtube_url'] ?? '',
                'public_theme_dark' => $settings['public_theme_dark'] ?? '#071B33',
                'public_theme_primary' => $settings['public_theme_primary'] ?? '#0057A8',
                'public_theme_accent' => $settings['public_theme_accent'] ?? '#20B8E6',
                'public_theme_highlight' => $settings['public_theme_highlight'] ?? '#F4B942',
                'public_theme_background' => $settings['public_theme_background'] ?? '#F4F7FA',
                'public_theme_text' => $settings['public_theme_text'] ?? '#102A43',
            ],
        ]);
    }

    public function update(UpdateSettingsRequest $request, SettingAssetService $assetService, PublicPortalService $publicPortal): RedirectResponse
    {
        Gate::authorize('update-settings');

        $orgId = Auth::user()->organization_id;

        if ($request->filled('app_name')) {
            Setting::updateOrCreate(
                ['organization_id' => $orgId, 'key' => 'app_name'],
                ['value' => $request->app_name]
            );
        }

        if ($request->hasFile('logo')) {
            $url = $assetService->store($request->file('logo'));
            Setting::updateOrCreate(
                ['organization_id' => $orgId, 'key' => 'logo_url'],
                ['value' => $url]
            );
        }

        foreach ([
            'secretariat_address',
            'secretariat_email',
            'secretariat_phone',
            'secretariat_facebook_url',
            'secretariat_instagram_url',
            'secretariat_tiktok_url',
            'secretariat_youtube_url',
        ] as $key) {
            Setting::updateOrCreate(
                ['organization_id' => $orgId, 'key' => $key],
                ['value' => (string) $request->input($key, '')]
            );
        }

        foreach (['public_theme_dark', 'public_theme_primary', 'public_theme_accent', 'public_theme_highlight', 'public_theme_background', 'public_theme_text'] as $key) {
            Setting::updateOrCreate(
                ['organization_id' => $orgId, 'key' => $key],
                ['value' => strtoupper($request->string($key)->toString())]
            );
        }

        if ($request->hasFile('inverse_logo')) {
            $url = $assetService->store($request->file('inverse_logo'));
            Setting::updateOrCreate(
                ['organization_id' => $orgId, 'key' => 'inverse_logo_url'],
                ['value' => $url]
            );
        }

        if ($request->hasFile('tournament_logo')) {
            $url = $assetService->store($request->file('tournament_logo'));
            Setting::updateOrCreate(
                ['organization_id' => $orgId, 'key' => 'tournament_logo_url'],
                ['value' => $url]
            );
        }

        if ($request->hasFile('favicon')) {
            $url = $assetService->store($request->file('favicon'));
            Setting::updateOrCreate(
                ['organization_id' => $orgId, 'key' => 'favicon_url'],
                ['value' => $url]
            );
        }

        $publicPortal->forgetForOrganization($orgId);

        return redirect()->back()->with('success', 'Settings updated.');
    }
}
