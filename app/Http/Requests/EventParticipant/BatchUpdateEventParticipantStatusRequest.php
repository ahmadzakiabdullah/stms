<?php

namespace App\Http\Requests\EventParticipant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BatchUpdateEventParticipantStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['required', 'uuid', Rule::exists('event_participants', 'id')],
            'status' => ['required', 'string', 'in:confirmed,rejected'],
            'notes' => ['nullable', 'string', 'max:1000', 'required_if:status,rejected'],
        ];
    }
}
