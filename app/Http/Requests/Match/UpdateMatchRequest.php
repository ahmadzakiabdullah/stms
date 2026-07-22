<?php

namespace App\Http\Requests\Match;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_id' => ['sometimes', 'required', 'uuid', 'exists:events,id'],
            'match_number' => ['sometimes', 'required', 'integer', 'min:1'],
            'home_participant_id' => ['nullable', 'uuid', 'exists:participants,id'],
            'away_participant_id' => ['nullable', 'uuid', 'exists:participants,id'],
            'venue' => ['nullable', 'string', 'max:255'],
            'scheduled_at' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['scheduled', 'in_progress', 'completed', 'cancelled'])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
