<?php

namespace App\Http\Requests\Event;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $event = $this->route('event');
        $user = $this->user();
        $isSuper = $user && $user->hasRole('super-admin');

        return [
            'tournament_id' => [
                'required',
                'uuid',
                Rule::exists('tournaments', 'id')->where(function ($query) use ($isSuper, $user) {
                    if (!$isSuper) {
                        $query->where('organization_id', $user->organization_id);
                    }
                }),
            ],
            'sport_id' => [
                'required',
                'uuid',
                Rule::exists('sports', 'id')->where(function ($query) use ($isSuper, $user) {
                    if (!$isSuper) {
                        $query->where('organization_id', $user->organization_id);
                    }
                }),
            ],
            'sport_category_id' => [
                'required',
                'uuid',
                Rule::exists('sport_categories', 'id')->where(function ($query) use ($isSuper, $user) {
                    if (!$isSuper) {
                        $query->where('organization_id', $user->organization_id);
                    }
                }),
                Rule::unique('events', 'sport_category_id')
                    ->where('tournament_id', $this->tournament_id)
                    ->where('sport_id', $this->sport_id)
                    ->whereNull('deleted_at')
                    ->ignore($event),
            ],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('events', 'slug')
                    ->where('organization_id', $event?->organization_id ?? $user?->organization_id)
                    ->ignore($event),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'registration_deadline' => ['nullable', 'date'],
            'is_active' => ['boolean'],
            'format' => ['nullable', 'string', 'in:league,group_knockout,knockout'],
            'pool_size' => ['nullable', 'integer', 'min:2', 'max:32'],
        ];
    }
}
