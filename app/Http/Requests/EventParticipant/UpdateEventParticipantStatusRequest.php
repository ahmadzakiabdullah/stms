<?php

namespace App\Http\Requests\EventParticipant;

use App\Models\EventParticipant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateEventParticipantStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $eventParticipant = $this->route('eventParticipant');

        return $eventParticipant instanceof EventParticipant && Gate::allows('update', $eventParticipant);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:confirmed,rejected'],
            'notes' => ['nullable', 'string', 'max:1000', 'required_if:status,rejected'],
        ];
    }
}
