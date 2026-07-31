<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Participant extends Model
{
    use BelongsToOrganization, HasFactory, \Illuminate\Database\Eloquent\Concerns\HasUuids, LogsActivity, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'session_id',
        'name',
        'slug',
        'email',
        'phone',
        'participant_type',
        'team_name',
        'status',
        'notes',
        'logo_path',
        'is_active',
    ];

    protected $appends = ['logo_url'];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function users()
    {
        return $this->hasMany(User::class, 'participant_id', 'id');
    }

    public function eventParticipants(): HasMany
    {
        return $this->hasMany(EventParticipant::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? asset('storage/'.$this->logo_path) : null;
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
