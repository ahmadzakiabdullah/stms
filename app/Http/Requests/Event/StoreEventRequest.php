<?php

namespace App\Http\Requests\Event;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if (empty($this->organization_id)) {
            $this->merge([
                'organization_id' => $this->user()->organization_id,
            ]);
        }

        if (is_array($this->venues)) {
            $this->merge([
                'venues' => array_values(array_filter(array_map('trim', $this->venues), fn ($venue) => $venue !== '')),
            ]);
        }
    }

    public function rules(): array
    {
        $user = $this->user();
        $isSuper = $user && $user->hasRole('super-admin');
        $organizationRule = $isSuper ? Rule::exists('organizations', 'id') : Rule::in([$user?->organization_id]);

        return [
            'organization_id' => ['required', 'uuid', $organizationRule],
            'tournament_id' => [
                'required',
                'uuid',
                Rule::exists('tournaments', 'id')->where(function ($query) use ($isSuper, $user) {
                    if (! $isSuper) {
                        $query->where('organization_id', $user->organization_id);
                    }
                }),
            ],
            'sport_id' => [
                'required',
                'uuid',
                Rule::exists('sports', 'id')->where(function ($query) use ($isSuper, $user) {
                    if (! $isSuper) {
                        $query->where('organization_id', $user->organization_id);
                    }
                }),
            ],
            'sport_category_id' => [
                'required',
                'uuid',
                Rule::exists('sport_categories', 'id')->where(function ($query) use ($isSuper, $user) {
                    if (! $isSuper) {
                        $query->where('organization_id', $user->organization_id);
                    }
                }),
                Rule::unique('events', 'sport_category_id')
                    ->where('tournament_id', $this->tournament_id)
                    ->where('sport_id', $this->sport_id)
                    ->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('events', 'slug')->where('organization_id', $user?->organization_id)->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'venues' => ['nullable', 'array', 'max:20'],
            'venues.*' => ['required', 'string', 'max:255', 'distinct'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'registration_deadline' => ['nullable', 'date', 'after:now'],
            'is_active' => ['boolean'],
            'format' => ['nullable', 'string', 'in:league,group_knockout,knockout'],
            'pool_size' => ['nullable', 'integer', 'min:2', 'max:32'],
        ];
    }
}
