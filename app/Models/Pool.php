<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Pool extends Model
{
    use HasFactory, HasUuids, SoftDeletes, BelongsToOrganization, LogsActivity;

    protected $fillable = ['organization_id', 'event_id', 'name', 'sort_order'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function eventParticipants(): HasMany
    {
        return $this->hasMany(EventParticipant::class, 'pool_id');
    }

    public function fixtures(): HasMany
    {
        return $this->hasMany(Fixture::class, 'pool_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
}
