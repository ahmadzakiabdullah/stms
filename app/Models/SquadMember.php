<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class SquadMember extends Model
{
    use BelongsToOrganization, HasFactory, HasUuids, LogsActivity;

    protected $fillable = [
        'event_participant_id',
        'organization_id',
        'name',
        'matrix_no',
        'role',
        'identification_no',
        'phone',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function eventParticipant()
    {
        return $this->belongsTo(EventParticipant::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public const OFFICIAL_ROLES = ['assistant_manager', 'manager', 'coach', 'physio'];

    public const ATHLETE_ROLES = ['athlete_male', 'athlete_female'];

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByRaw("CASE role WHEN 'manager' THEN 0 WHEN 'assistant_manager' THEN 1 WHEN 'coach' THEN 2 WHEN 'physio' THEN 3 ELSE 4 END")
            ->orderBy('created_at')
            ->orderBy('name');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
}
