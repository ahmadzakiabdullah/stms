<?php

namespace App\Http\Requests\Participant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class ParticipantImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $user = $this->user();

        return [
            'file' => ['required', File::types(['csv', 'xlsx', 'xls', 'txt'])->max('5mb')],
            'session_id' => ['nullable', 'uuid', Rule::exists('event_sessions', 'id')->where('organization_id', $user?->organization_id)],
        ];
    }
}
