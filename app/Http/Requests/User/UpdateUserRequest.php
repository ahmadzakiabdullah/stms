<?php

namespace App\Http\Requests\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        return $target instanceof User
            && ($this->user()?->can('update', $target) ?? false);
    }

    public function rules(): array
    {
        $user = $this->route('user');
        $actor = $this->user();
        $isSuperAdmin = $actor?->hasRole('super-admin') ?? false;
        $organizationId = $actor?->organization_id;
        $superAdminRoleId = $isSuperAdmin ? null : Role::query()
            ->where('name', 'super-admin')
            ->where('guard_name', 'web')
            ->value('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'lowercase', 'alpha_dash', 'min:3', 'max:64', Rule::unique('users', 'username')->ignore($user)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => ['nullable', 'string', Password::defaults(), 'confirmed'],
            'organization_id' => [
                $isSuperAdmin ? 'nullable' : 'required',
                'uuid',
                $isSuperAdmin
                    ? Rule::exists('organizations', 'id')
                    : Rule::in([$organizationId]),
            ],
            'participant_id' => [
                'nullable',
                'uuid',
                Rule::exists('participants', 'id')->when(
                    ! $isSuperAdmin,
                    fn ($query) => $query->where('organization_id', $organizationId),
                ),
            ],
            'is_active' => ['boolean'],
            'roles' => ['array'],
            'roles.*' => [
                'integer',
                Rule::exists('roles', 'id'),
                Rule::notIn(array_filter([$superAdminRoleId])),
            ],
            'sports' => ['array'],
            'sports.*' => [
                'uuid',
                Rule::exists('sports', 'id')->when(
                    ! $isSuperAdmin,
                    fn ($query) => $query->where('organization_id', $organizationId),
                ),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->user() && ! $this->user()->hasRole('super-admin') && ! $this->filled('organization_id')) {
            $this->merge(['organization_id' => $this->user()->organization_id]);
        }
    }
}
