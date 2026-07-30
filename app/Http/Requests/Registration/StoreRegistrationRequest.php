<?php

namespace App\Http\Requests\Registration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegistrationRequest extends FormRequest
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
        $isSuper = $user && $user->hasRole('super-admin');

        return [
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
            'tournament_id' => [
                'required',
                'uuid',
                Rule::exists('tournaments', 'id')->where(function ($query) use ($isSuper, $user) {
                    if (! $isSuper) {
                        $query->where('organization_id', $user->organization_id);
                    }
                }),
            ],
            'participant_id' => [
                'required',
                'uuid',
                Rule::exists('participants', 'id')->where(function ($query) use ($isSuper, $user) {
                    if (! $isSuper) {
                        $query->where('organization_id', $user->organization_id);
                    }
                }),
            ],
            'status' => ['nullable', 'in:pending,confirmed,rejected,cancelled'],
            'registered_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
