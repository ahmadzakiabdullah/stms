<?php

namespace App\Http\Requests\Participant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UpdateParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $participant = $this->route('participant');

        foreach (['is_active', 'remove_logo', 'remove_inverse_logo'] as $field) {
            if (in_array($this->input($field), ['true', 'false'], true)) {
                $this->merge([
                    $field => $this->input($field) === 'true',
                ]);
            }
        }

        if (empty($this->organization_id)) {
            $this->merge([
                'organization_id' => $participant?->organization_id ?? $this->user()->organization_id,
            ]);
        }
    }

    public function rules(): array
    {
        $user = $this->user();
        $participant = $this->route('participant');
        $organizationId = $participant?->organization_id ?? $user?->organization_id;

        return [
            'organization_id' => ['required', 'uuid', Rule::in([$organizationId])],
            'session_id' => [
                'nullable',
                'uuid',
                Rule::exists('event_sessions', 'id')->where('organization_id', $organizationId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('participants', 'slug')
                    ->where('organization_id', $organizationId)
                    ->ignore($participant?->id)
                    ->whereNull('deleted_at'),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'participant_type' => ['nullable', 'in:individual,team'],
            'team_name' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:registered,confirmed,withdrawn,disqualified'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', File::image(allowSvg: true)->max('2mb')],
            'inverse_logo' => ['nullable', File::image(allowSvg: true)->max('2mb')],
            'logo_path_existing' => ['nullable', 'string', 'starts_with:logos/', 'regex:/\.(png|jpe?g|gif|webp|svg)$/i', 'not_regex:/\.\./'],
            'inverse_logo_path_existing' => ['nullable', 'string', 'starts_with:logos/', 'regex:/\.(png|jpe?g|gif|webp|svg)$/i', 'not_regex:/\.\./'],
            'remove_logo' => ['boolean'],
            'remove_inverse_logo' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
