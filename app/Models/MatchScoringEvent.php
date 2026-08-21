<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class MatchScoringEvent extends Model
{
    use BelongsToOrganization, HasFactory, HasUuids, LogsActivity;

    protected $fillable = [
        'organization_id', 'result_id', 'match_id', 'participant_id', 'squad_member_id',
        'event_type', 'period', 'minute', 'second', 'points', 'notes',
    ];

    protected function casts(): array
    {
        return ['period' => 'integer', 'minute' => 'integer', 'second' => 'integer', 'points' => 'integer'];
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(Result::class);
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(Fixture::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function squadMember(): BelongsTo
    {
        return $this->belongsTo(SquadMember::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty();
    }
}
