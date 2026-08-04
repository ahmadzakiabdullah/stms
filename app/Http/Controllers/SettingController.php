<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\SettingAssetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
                'favicon_url' => $settings['favicon_url'] ?? null,
                'tournament_logo_url' => $settings['tournament_logo_url'] ?? null,
                'secretariat_address' => $settings['secretariat_address'] ?? '',
            ],
        ]);
    }

    public function update(Request $request, SettingAssetService $assetService): RedirectResponse
    {
        Gate::authorize('update-settings');

        $request->validate([
            'app_name' => 'nullable|string|max:255',
            'logo' => 'nullable|file|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'favicon' => 'nullable|file|mimes:png,ico,svg|max:1024',
            'tournament_logo' => 'nullable|file|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'secretariat_address' => 'nullable|string|max:1000',
        ]);

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

        Setting::updateOrCreate(
            ['organization_id' => $orgId, 'key' => 'secretariat_address'],
            ['value' => (string) $request->input('secretariat_address', '')]
        );

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

        return redirect()->back()->with('success', 'Settings updated.');
    }
}
