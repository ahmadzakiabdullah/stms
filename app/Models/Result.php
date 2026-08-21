<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Result extends Model
{
    use BelongsToOrganization, HasFactory, HasUuids, LogsActivity, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'match_id',
        'score_home',
        'score_away',
        'winner_participant_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'score_home' => 'integer',
            'score_away' => 'integer',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(Fixture::class);
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'winner_participant_id');
    }

    public function scoringEvents(): HasMany
    {
        return $this->hasMany(MatchScoringEvent::class)->orderBy('period')->orderBy('minute')->orderBy('second')->orderBy('created_at');
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
}
