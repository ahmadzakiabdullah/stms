<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QueueExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('export-data') ?? false;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:fixtures,results,rankings,medals'],
            'event_id' => ['nullable', 'uuid'],
            'tournament_id' => ['nullable', 'uuid', 'required_if:type,rankings'],
            'session_id' => ['nullable', 'uuid', 'required_if:type,medals'],
            'idempotency_key' => ['nullable', 'string', 'max:150'],
        ];
    }
}
