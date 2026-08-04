<?php

namespace App\Http\Requests\Sport;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSportRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (in_array($this->input('is_active'), ['true', 'false'], true)) {
            $this->merge([
                'is_active' => $this->input('is_active') === 'true',
            ]);
        }
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $sport = $this->route('sport');
        $user = $this->user();

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sports', 'name')
                    ->where('organization_id', $sport?->organization_id ?? $user?->organization_id)
                    ->ignore($sport)
                    ->whereNull('deleted_at'),
            ],
            'slug' => [
                'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('sports', 'slug')
                    ->where('organization_id', $sport?->organization_id ?? $user?->organization_id)
                    ->ignore($sport)
                    ->whereNull('deleted_at'),
            ],
            'icon' => ['nullable', 'string', 'max:255'],
            'icon_file' => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'This sport already exists.',
            'slug.unique' => 'This sport slug already exists.',
        ];
    }
}
