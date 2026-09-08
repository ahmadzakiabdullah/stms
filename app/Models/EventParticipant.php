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

class EventParticipant extends Model
{
    use BelongsToOrganization, HasFactory, HasUuids, LogsActivity, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_WITHDRAWN = 'withdrawn';

    public const STATUS_DISQUALIFIED = 'disqualified';

    public const ALLOWED_TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_CONFIRMED, self::STATUS_REJECTED, self::STATUS_WITHDRAWN, self::STATUS_DISQUALIFIED],
        self::STATUS_CONFIRMED => [self::STATUS_WITHDRAWN, self::STATUS_DISQUALIFIED],
        self::STATUS_REJECTED => [self::STATUS_CONFIRMED, self::STATUS_WITHDRAWN],
        self::STATUS_WITHDRAWN => [],
        self::STATUS_DISQUALIFIED => [],
    ];

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

    public function canTransitionTo(string $status): bool
    {
        if ($status === $this->status) {
            return false;
        }

        return in_array($status, self::ALLOWED_TRANSITIONS[$this->status] ?? [], true);
    }
}
