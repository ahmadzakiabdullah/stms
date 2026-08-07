<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Event extends Model
{
    use BelongsToOrganization, HasFactory, HasUuids, LogsActivity, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'tournament_id',
        'sport_id',
        'sport_category_id',
        'name',
        'slug',
        'description',
        'start_date',
        'end_date',
        'registration_deadline',
        'format',
        'pool_size',
        'qualifiers_per_pool',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'registration_deadline' => 'datetime',
        'is_active' => 'boolean',
        'pool_size' => 'integer',
        'qualifiers_per_pool' => 'integer',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function sport()
    {
        return $this->belongsTo(Sport::class);
    }

    public function sportCategory()
    {
        return $this->belongsTo(SportCategory::class, 'sport_category_id');
    }

    // Future relation (Match model not yet implemented)
    public function matches(): HasMany
    {
        return $this->hasMany(Fixture::class);
    }

    public function pools(): HasMany
    {
        return $this->hasMany(Pool::class);
    }

    public function drawVersions(): HasMany
    {
        return $this->hasMany(DrawVersion::class)->latest('version');
    }

    public function eventParticipants()
    {
        return $this->hasMany(EventParticipant::class);
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
