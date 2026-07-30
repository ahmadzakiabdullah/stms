<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class SportCategory extends Model
{
    use BelongsToOrganization, HasFactory, HasUuids, LogsActivity, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'sport_id',
        'name',
        'slug',
        'quota_mode',
        'max_athletes_total',
        'max_male_athletes',
        'max_female_athletes',
        'min_male_athletes',
        'min_female_athletes',
        'max_officials',
    ];

    protected function casts(): array
    {
        return [
            'max_athletes_total' => 'integer',
            'max_male_athletes' => 'integer',
            'max_female_athletes' => 'integer',
            'min_male_athletes' => 'integer',
            'min_female_athletes' => 'integer',
            'max_officials' => 'integer',
        ];
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function sport()
    {
        return $this->belongsTo(Sport::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function scopeActive($query)
    {
        return $query->whereHas('sport', fn ($q) => $q->where('is_active', true));
    }

    public function allowedAthleteRoles(): array
    {
        $male = $this->max_male_athletes;
        $female = $this->max_female_athletes;

        if ($male !== null && $female !== null) {
            if ($male > 0 && $female > 0) {
                return ['athlete_male', 'athlete_female'];
            }
            if ($male > 0) {
                return ['athlete_male'];
            }
            if ($female > 0) {
                return ['athlete_female'];
            }

            return [];
        }

        return $this->inferGenderRoles();
    }

    public function usesTotalAthleteQuota(): bool
    {
        return in_array($this->quota_mode, ['open_total', 'mixed_total'], true)
            && $this->max_athletes_total !== null;
    }

    public function athleteQuotaLabel(): string
    {
        if (! $this->usesTotalAthleteQuota()) {
            return 'Gender based';
        }

        $label = "{$this->max_athletes_total} total";

        if ($this->min_male_athletes || $this->min_female_athletes) {
            $minMale = $this->min_male_athletes ?? 0;
            $minFemale = $this->min_female_athletes ?? 0;
            $label .= " (min M {$minMale}, min F {$minFemale})";
        }

        return $label;
    }

    private function inferGenderRoles(): array
    {
        $name = mb_strtolower($this->name);

        $hasMixed = preg_match('/\bmixed\b/', $name) || preg_match('/\bopen\b/', $name) || preg_match('/\bcampuran\b/', $name);
        if ($hasMixed) {
            return ['athlete_male', 'athlete_female'];
        }

        $onlyMen = preg_match('/\bmen\'?s?\b/', $name) || preg_match('/\bmale\b/', $name) || $name === 'men';
        $onlyWomen = preg_match('/\bwomen\'?s?\b/', $name) || preg_match('/\bfemale\b/', $name) || $name === 'women';

        if ($onlyMen && $onlyWomen) {
            return ['athlete_male', 'athlete_female'];
        }
        if ($onlyMen) {
            return ['athlete_male'];
        }
        if ($onlyWomen) {
            return ['athlete_female'];
        }

        return ['athlete_male', 'athlete_female'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
}
