<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // For M1 bootstrap: allow authenticated users to create (later tighten with Policy)
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if (empty($this->parent_id)) {
            $this->merge(['parent_id' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('organizations', 'slug')],
            'organization_type' => ['required', 'string', Rule::in(['national', 'state', 'university', 'school', 'private'])],
            'parent_id' => ['nullable', 'uuid', 'exists:organizations,id'],
            'is_active' => ['boolean'],
        ];
    }
}
