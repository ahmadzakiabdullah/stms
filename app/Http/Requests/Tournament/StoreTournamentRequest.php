<?php

namespace App\Http\Requests\Tournament;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTournamentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if (empty($this->organization_id)) {
            $this->merge([
                'organization_id' => $this->user()->organization_id,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
            'session_id' => [
                'required',
                'uuid',
                Rule::exists('event_sessions', 'id')->where(function ($query) {
                    $user = $this->user();
                    if (! $user->hasRole('super-admin')) {
                        $query->where('organization_id', $user->organization_id);
                    }
                }),
            ],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('tournaments', 'slug')
                    ->where('organization_id', $this->user()?->organization_id)
                    ->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_active' => ['boolean'],
            'sports' => ['array'],
            'sports.*' => [
                'uuid',
                Rule::exists('sports', 'id')->where(function ($query) {
                    $user = $this->user();
                    if (! $user->hasRole('super-admin')) {
                        $query->where('organization_id', $user->organization_id);
                    }
                }),
            ],
        ];
    }
}
