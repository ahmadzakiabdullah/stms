<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fixture extends Model
{
    use HasFactory, HasUuids, SoftDeletes, LogsActivity, BelongsToOrganization;

    protected $table = 'matches';

    protected $fillable = [
        'organization_id',
        'event_id',
        'pool_id',
        'round',
        'match_number',
        'home_participant_id',
        'away_participant_id',
        'venue',
        'scheduled_at',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'match_number' => 'integer',
            'round' => 'integer',
            'scheduled_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function pool(): BelongsTo
    {
        return $this->belongsTo(Pool::class, 'pool_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function homeParticipant(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'home_participant_id');
    }

    public function awayParticipant(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'away_participant_id');
    }

    public function result(): HasOne
    {
        return $this->hasOne(Result::class, 'match_id');
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
