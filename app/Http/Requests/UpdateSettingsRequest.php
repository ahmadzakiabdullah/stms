<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update-settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'app_name' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'favicon' => 'nullable|image|mimes:png,ico,svg|max:1024',
            'tournament_logo' => 'nullable|file|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'secretariat_address' => 'nullable|string|max:1000',
            'public_theme_dark' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'public_theme_primary' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'public_theme_accent' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'public_theme_highlight' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'public_theme_background' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'public_theme_text' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }
}
