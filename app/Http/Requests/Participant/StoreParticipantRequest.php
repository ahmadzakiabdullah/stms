<?php

namespace App\Http\Requests\Participant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if (in_array($this->input('is_active'), ['true', 'false'], true)) {
            $this->merge([
                'is_active' => $this->input('is_active') === 'true',
            ]);
        }

        if (empty($this->organization_id)) {
            $this->merge([
                'organization_id' => $this->user()->organization_id,
            ]);
        }
    }

    public function rules(): array
    {
        $user = $this->user();

        return [
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
            'session_id' => ['nullable', 'uuid', 'exists:event_sessions,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('participants', 'slug')->where('organization_id', $user?->organization_id)->whereNull('deleted_at'),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'participant_type' => ['nullable', 'in:individual,team'],
            'team_name' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:registered,confirmed,withdrawn,disqualified'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', File::image(allowSvg: true)->max('2mb')],
            'is_active' => ['boolean'],
        ];
    }
}
