<?php

namespace App\Http\Requests\Session;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSessionRequest extends FormRequest
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
        $user = $this->user();
        $isSuper = $user?->hasRole('super-admin');

        return [
            'organization_id' => [
                'required', 'uuid',
                $isSuper ? Rule::exists('organizations', 'id') : Rule::in([$user?->organization_id]),
            ],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('event_sessions', 'slug')
                    ->where('organization_id', $user?->organization_id)
                    ->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_active' => ['boolean'],
        ];
    }
}
