<?php

namespace App\Http\Requests\Event;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class BatchDestroyEventsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && Gate::allows('viewAny', Event::class);
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['required', 'uuid', 'distinct', Rule::exists('events', 'id')],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }
}
