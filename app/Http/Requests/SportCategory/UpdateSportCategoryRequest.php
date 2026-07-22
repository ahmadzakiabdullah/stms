<?php

namespace App\Http\Requests\SportCategory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSportCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $category = $this->route('sportCategory');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('sport_categories', 'slug')
                    ->ignore($category)
                    ->where('sport_id', $category?->sport_id),
            ],
            'quota_mode' => ['nullable', Rule::in(['gender_based', 'open_total', 'mixed_total'])],
            'max_athletes_total' => ['nullable', 'integer', 'min:0'],
            'max_male_athletes' => ['nullable', 'integer', 'min:0'],
            'max_female_athletes' => ['nullable', 'integer', 'min:0'],
            'min_male_athletes' => ['nullable', 'integer', 'min:0'],
            'min_female_athletes' => ['nullable', 'integer', 'min:0'],
            'max_officials' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $quotaMode = $this->input('quota_mode', 'gender_based');
            $total = $this->integer('max_athletes_total');
            $minMale = $this->integer('min_male_athletes');
            $minFemale = $this->integer('min_female_athletes');

            if (in_array($quotaMode, ['open_total', 'mixed_total'], true) && $total <= 0) {
                $validator->errors()->add('max_athletes_total', 'Max total athletes is required for total-based quota modes.');
            }

            if ($total > 0 && ($minMale + $minFemale) > $total) {
                $validator->errors()->add('max_athletes_total', 'Minimum male and female athletes cannot exceed max total athletes.');
            }
        });
    }
}
