<?php

namespace App\Http\Requests\Match;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organizationId = $this->user()?->organization_id;
        $isSuperAdmin = $this->user()?->hasRole('super-admin');
        $tenant = fn ($query) => $isSuperAdmin ? $query : $query->where('organization_id', $organizationId);

        return [
            'event_id' => ['required', 'uuid', Rule::exists('events', 'id')->where($tenant)],
            'pool_id' => ['nullable', 'uuid', Rule::exists('pools', 'id')->where($tenant)],
            'round' => ['nullable', 'integer', 'min:1'],
            'match_number' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('matches', 'match_number')->where(fn ($query) => $query
                    ->where('event_id', $this->input('event_id'))
                    ->whereNull('deleted_at')),
            ],
            'home_participant_id' => ['nullable', 'uuid', Rule::exists('participants', 'id')->where($tenant)],
            'away_participant_id' => ['nullable', 'uuid', 'different:home_participant_id', Rule::exists('participants', 'id')->where($tenant)],
            'venue' => ['nullable', 'string', 'max:255'],
            'scheduled_at' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['scheduled', 'in_progress', 'completed', 'cancelled'])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
