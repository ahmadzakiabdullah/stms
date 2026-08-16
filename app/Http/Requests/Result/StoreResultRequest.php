<?php

namespace App\Http\Requests\Result;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreResultRequest extends FormRequest
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
            'match_id' => ['required', 'uuid', Rule::exists('matches', 'id')->where($tenant)],
            'score_home' => ['nullable', 'integer', 'min:0'],
            'score_away' => ['nullable', 'integer', 'min:0'],
            'winner_participant_id' => ['nullable', 'uuid', Rule::exists('participants', 'id')->where($tenant)],
            'notes' => ['nullable', 'string'],
        ];
    }
}
