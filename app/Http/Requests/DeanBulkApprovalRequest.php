<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeanBulkApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view-dean-dashboard') === true;
    }

    public function rules(): array
    {
        return [
            'event_participant_ids' => ['required', 'array', 'min:1'],
            'event_participant_ids.*' => ['required', 'uuid', Rule::exists('event_participants', 'id')],
        ];
    }
}
