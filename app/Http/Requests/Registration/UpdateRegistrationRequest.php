<?php

namespace App\Http\Requests\Registration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if (empty($this->organization_id)) {
            $this->merge([
                'organization_id' => $this->route('registration')?->organization_id ?? $this->user()->organization_id,
            ]);
        }
    }

    public function rules(): array
    {
        $user = $this->user();
        $registration = $this->route('registration');
        $organizationId = $registration?->organization_id ?? $user?->organization_id;
        $organizationRule = $user?->hasRole('super-admin')
            ? Rule::in([$organizationId])
            : Rule::in([$user?->organization_id]);

        return [
            'organization_id' => ['required', 'uuid', $organizationRule],
            'tournament_id' => [
                'required',
                'uuid',
                Rule::exists('tournaments', 'id')->where('organization_id', $organizationId),
            ],
            'participant_id' => [
                'required',
                'uuid',
                Rule::exists('participants', 'id')->where('organization_id', $organizationId),
            ],
            'status' => ['nullable', 'in:pending,confirmed,rejected,cancelled'],
            'registered_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
