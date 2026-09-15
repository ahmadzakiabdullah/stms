<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class EventParticipant extends Model
{
    use HasUuids, SoftDeletes, BelongsToOrganization, LogsActivity;

    protected $table = 'event_participants';

    protected $fillable = [
        'organization_id',
        'event_id',
        'participant_id',
        'registration_date',
        'status',
        'seed_number',
        'pool_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'registration_date' => 'datetime',
            'seed_number' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function pool(): BelongsTo
    {
        return $this->belongsTo(Pool::class, 'pool_id');
    }

    public function squadMembers(): HasMany
    {
        return $this->hasMany(SquadMember::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
}
