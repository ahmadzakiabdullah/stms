<?php

namespace App\Http\Requests\EventParticipant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BatchRegisterEventParticipantsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $organizationId = $this->user()?->organization_id;
        $tenant = $this->user()?->hasRole('super-admin')
            ? fn ($query) => $query
            : fn ($query) => $query->where('organization_id', $organizationId);

        return [
            'participant_id' => ['nullable', 'uuid', Rule::exists('participants', 'id')->where($tenant)],
            'event_ids' => ['required', 'array', 'min:1'],
            'event_ids.*' => ['required', 'uuid', Rule::exists('events', 'id')->where($tenant)],
        ];
    }
}
