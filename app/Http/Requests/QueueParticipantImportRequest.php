<?php

namespace App\Http\Requests;

use App\Models\Participant;
use Illuminate\Foundation\Http\FormRequest;

class QueueParticipantImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Participant::class) ?? false;
    }

    public function rules(): array
    {
        return ['token' => ['required', 'uuid']];
    }
}
