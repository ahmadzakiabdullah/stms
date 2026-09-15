<?php

namespace App\Http\Requests\EventParticipant;

use App\Models\EventParticipant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class RegisterEventParticipantRequest extends FormRequest
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

    /**
     * Resolve the participant the current user may register for. Faculty
     * representatives always act on behalf of their own participant.
     */
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
