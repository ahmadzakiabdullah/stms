<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SessionSportCategory extends Model
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = [
        'organization_id', 'session_sport_id', 'sport_category_id', 'name', 'quota_mode',
        'max_athletes_total', 'max_male_athletes', 'max_female_athletes', 'max_officials',
        'competition_system', 'venue', 'start_date', 'end_date',
    ];

    protected $casts = [
        'max_athletes_total' => 'integer', 'max_male_athletes' => 'integer',
        'max_female_athletes' => 'integer', 'max_officials' => 'integer',
        'start_date' => 'date', 'end_date' => 'date',
    ];

    public function sessionSport() { return $this->belongsTo(SessionSport::class); }
    public function category() { return $this->belongsTo(SportCategory::class, 'sport_category_id'); }
}
