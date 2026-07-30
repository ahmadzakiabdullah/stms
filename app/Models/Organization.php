<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Organization extends Model
{
    use HasFactory, HasUuids, LogsActivity, SoftDeletes;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'organization_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function sessions()
    {
        return $this->hasMany(Session::class);
    }

    public function latestSession()
    {
        return $this->hasOne(Session::class)->latestOfMany('start_date');
    }

    public function getLatestSessionAttribute()
    {
        return $this->latestSession;
    }

    public function tournaments()
    {
        return $this->hasManyThrough(
            Tournament::class,
            Session::class,
            'organization_id', // Foreign key on sessions table
            'session_id',      // Foreign key on tournaments table
            'id',              // Local key on organizations table
            'id'               // Local key on sessions table
        );
    }

    public function events()
    {
        return $this->hasManyThrough(
            Event::class,
            Tournament::class,
            'organization_id',
            'tournament_id',
            'id',
            'id'
        );
    }

    /**
     * Scope to active organizations.
     */
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
