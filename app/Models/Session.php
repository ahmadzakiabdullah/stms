<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Session extends Model
{
    use BelongsToOrganization, HasFactory, HasUuids, LogsActivity, SoftDeletes;

    protected $table = 'event_sessions';  // IMPORTANT: our domain "Session" (like SUKMA event), NOT Laravel's 'sessions' table used for login session storage.

    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'description',
        'logo_path',
        'inverse_logo_path',
        'start_date',
        'end_date',
        'event_registration_start_date',
        'event_registration_deadline',
        'squad_registration_start_date',
        'squad_registration_deadline',
        'is_active',
        'ranking_strategy',
        'ranking_rules',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'event_registration_start_date' => 'date',
        'event_registration_deadline' => 'date',
        'squad_registration_start_date' => 'date',
        'squad_registration_deadline' => 'date',
        'is_active' => 'boolean',
        'ranking_rules' => 'array',
    ];

    protected $appends = ['logo_url', 'inverse_logo_url'];

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function getInverseLogoUrlAttribute(): ?string
    {
        return $this->inverse_logo_path ? Storage::disk('public')->url($this->inverse_logo_path) : null;
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function tournaments()
    {
        return $this->hasMany(Tournament::class);
    }

    public function sessionSports()
    {
        return $this->hasMany(SessionSport::class);
    }

    public function events()
    {
        return $this->hasManyThrough(Event::class, Tournament::class);
    }

    public function documents()
    {
        return $this->hasMany(SportDocument::class)->whereNull('sport_id')->orderBy('sort_order')->orderBy('title');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
