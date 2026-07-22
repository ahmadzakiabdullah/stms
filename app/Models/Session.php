<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToOrganization;

class Session extends Model
{
    use HasFactory, HasUuids, SoftDeletes, BelongsToOrganization;

    protected $table = 'event_sessions';  // IMPORTANT: our domain "Session" (like SUKMA event), NOT Laravel's 'sessions' table used for login session storage.

    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'description',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function tournaments()
    {
        return $this->hasMany(Tournament::class);
    }

    public function events()
    {
        return $this->hasManyThrough(Event::class, Tournament::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
