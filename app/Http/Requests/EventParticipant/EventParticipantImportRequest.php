<?php

namespace App\Http\Requests\EventParticipant;

use App\Models\EventParticipant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class EventParticipantImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        if ($user->hasRole('faculty-representative') && $user->participant_id) {
            return true;
        }

        return Gate::allows('create', EventParticipant::class);
    }

    public function participantId(): string
    {
        $user = $this->user();

        if ($user->hasRole('faculty-representative') && $user->participant_id) {
            return $user->participant_id;
        }

        return $this->validated()['participant_id'];
    }

    public function rules(): array
    {
        $organizationId = $this->user()?->organization_id;
        $tenant = $this->user()?->hasRole('super-admin')
            ? fn ($query) => $query
            : fn ($query) => $query->where('organization_id', $organizationId);

        return [
            'participant_id' => ['nullable', 'uuid', Rule::exists('participants', 'id')->where($tenant)],
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:2048'],
        ];
    }
}
