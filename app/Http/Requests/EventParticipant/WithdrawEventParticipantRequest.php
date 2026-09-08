<?php

namespace App\Http\Requests\EventParticipant;

use App\Models\EventParticipant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class WithdrawEventParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        $eventParticipant = $this->route('eventParticipant');
        if (! $eventParticipant instanceof EventParticipant) {
            return false;
        }

        $isOwner = $user->hasRole('faculty-representative')
            && $user->participant_id
            && $user->participant_id === $eventParticipant->participant_id;

        return $isOwner || Gate::allows('update', $eventParticipant);
    }

    public function rules(): array
    {
        return [];
    }
}