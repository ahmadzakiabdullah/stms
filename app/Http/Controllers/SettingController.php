<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::where('organization_id', Auth::user()->organization_id)
            ->pluck('value', 'key')
            ->toArray();

        return Inertia::render('Settings/Index', [
            'settings' => [
                'app_name' => $settings['app_name'] ?? config('app.name', 'STMS Portal'),
                'logo_url' => $settings['logo_url'] ?? null,
                'favicon_url' => $settings['favicon_url'] ?? null,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'app_name' => 'nullable|string|max:255',
            'logo' => 'nullable|file|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'favicon' => 'nullable|file|mimes:png,ico,svg|max:1024',
        ]);

        $orgId = Auth::user()->organization_id;

        if ($request->filled('app_name')) {
            Setting::updateOrCreate(
                ['organization_id' => $orgId, 'key' => 'app_name'],
                ['value' => $request->app_name]
            );
        }

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('settings', 'public');
            $url = Storage::url($path);
            Setting::updateOrCreate(
                ['organization_id' => $orgId, 'key' => 'logo_url'],
                ['value' => $url]
            );
        }

        if ($request->hasFile('favicon')) {
            $path = $request->file('favicon')->store('settings', 'public');
            $url = Storage::url($path);
            Setting::updateOrCreate(
                ['organization_id' => $orgId, 'key' => 'favicon_url'],
                ['value' => $url]
            );
        }

        return redirect()->back()->with('success', 'Settings updated.');
    }
}
