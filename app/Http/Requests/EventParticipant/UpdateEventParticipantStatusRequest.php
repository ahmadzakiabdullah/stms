<?php

namespace App\Http\Requests\EventParticipant;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventParticipantStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['status' => ['required', 'string', 'in:confirmed,rejected']];
    }
}
