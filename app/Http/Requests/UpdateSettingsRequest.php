<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

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
            'logo' => ['nullable', File::image(allowSvg: true)->max('2mb')],
            'inverse_logo' => ['nullable', File::image(allowSvg: true)->max('2mb')],
            'favicon' => ['nullable', File::image(allowSvg: true)->max('1mb')],
            'tournament_logo' => ['nullable', File::image(allowSvg: true)->max('2mb')],
            'secretariat_address' => 'nullable|string|max:1000',
            'secretariat_email' => ['nullable', 'email:rfc', 'max:255'],
            'secretariat_phone' => ['nullable', 'string', 'max:50', 'regex:/^\+?[0-9\s().-]{7,50}$/'],
            'secretariat_facebook_url' => ['nullable', 'url:http,https', 'max:2048'],
            'secretariat_instagram_url' => ['nullable', 'url:http,https', 'max:2048'],
            'secretariat_tiktok_url' => ['nullable', 'url:http,https', 'max:2048'],
            'secretariat_youtube_url' => ['nullable', 'url:http,https', 'max:2048'],
            'public_theme_dark' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'public_theme_primary' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'public_theme_accent' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'public_theme_highlight' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'public_theme_background' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'public_theme_text' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $contactKeys = [
            'secretariat_address',
            'secretariat_email',
            'secretariat_phone',
            'secretariat_facebook_url',
            'secretariat_instagram_url',
            'secretariat_tiktok_url',
            'secretariat_youtube_url',
        ];

        $this->merge(collect($contactKeys)
            ->mapWithKeys(fn (string $key): array => [$key => trim((string) $this->input($key, ''))])
            ->all());
    }
}
