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
            'pool_id' => ['nullable', 'uuid', 'exists:pools,id'],
            'round' => ['nullable', 'integer', 'min:1'],
            'match_number' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                Rule::unique('matches', 'match_number')
                    ->where(fn ($query) => $query
                        ->where('event_id', $this->input('event_id'))
                        ->whereNull('deleted_at'))
                    ->ignore($this->route('match')?->id),
            ],
            'home_participant_id' => ['nullable', 'uuid', 'exists:participants,id'],
            'away_participant_id' => ['nullable', 'uuid', 'different:home_participant_id', 'exists:participants,id'],
            'venue' => ['nullable', 'string', 'max:255'],
            'scheduled_at' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['scheduled', 'in_progress', 'completed', 'cancelled'])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
