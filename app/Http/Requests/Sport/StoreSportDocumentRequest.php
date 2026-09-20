<?php

namespace App\Http\Requests\Sport;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSportDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('sport')) ?? false;
    }

    public function rules(): array
    {
        $sport = $this->route('sport');

        return [
            'title' => ['required', 'string', 'max:255'],
            'session_id' => [
                'required',
                'uuid',
                Rule::exists('event_sessions', 'id')->where('organization_id', $sport?->organization_id),
            ],
            'document' => ['required', 'file', 'mimes:pdf,md,markdown', 'max:10240'],
            'is_published' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
