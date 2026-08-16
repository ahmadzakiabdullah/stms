<?php

namespace App\Http\Requests\EventParticipant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterEventParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $organizationId = $this->user()?->organization_id;
        $isSuperAdmin = $this->user()?->hasRole('super-admin');

        return [
            'event_id' => ['required', 'uuid', Rule::exists('events', 'id')->where(function ($query) use ($isSuperAdmin, $organizationId) {
                if (! $isSuperAdmin) {
                    $query->where('organization_id', $organizationId);
                }
            })],
            'participant_id' => ['nullable', 'uuid', Rule::exists('participants', 'id')->where(function ($query) use ($isSuperAdmin, $organizationId) {
                if (! $isSuperAdmin) {
                    $query->where('organization_id', $organizationId);
                }
            })],
        ];
    }
}
