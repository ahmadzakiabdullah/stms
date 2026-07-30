<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
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
        $organization = $this->route('organization');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('organizations', 'slug')->ignore($organization)->whereNull('deleted_at'),
            ],
            'organization_type' => ['required', 'string', Rule::in(['national', 'state', 'university', 'school', 'private'])],
            'parent_id' => ['nullable', 'uuid', 'exists:organizations,id', 'not_in:'.($organization?->id ?? '')],
            'is_active' => ['boolean'],
        ];
    }
}
