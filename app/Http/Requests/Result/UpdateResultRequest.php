<?php

namespace App\Http\Requests\Result;

use Illuminate\Foundation\Http\FormRequest;

class UpdateResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'match_id' => ['sometimes', 'required', 'uuid', 'exists:matches,id'],
            'score_home' => ['nullable', 'integer', 'min:0'],
            'score_away' => ['nullable', 'integer', 'min:0'],
            'winner_participant_id' => ['nullable', 'uuid', 'exists:participants,id'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
