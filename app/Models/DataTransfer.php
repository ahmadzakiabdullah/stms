<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataTransfer extends Model
{
    use BelongsToOrganization, HasFactory, HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_COMPLETED_WITH_ERRORS = 'completed_with_errors';

    public const STATUS_FAILED = 'failed';

    public const TYPE_EXPORT_FIXTURES = 'export_fixtures';

    public const TYPE_EXPORT_RESULTS = 'export_results';

    public const TYPE_EXPORT_RANKINGS = 'export_rankings';

    public const TYPE_EXPORT_MEDALS = 'export_medals';

    public const TYPE_IMPORT_PARTICIPANTS = 'import_participants';

    public const TYPE_IMPORT_EVENT_PARTICIPANTS = 'import_event_participants';

    protected $fillable = [
        'organization_id',
        'requested_by',
        'type',
        'status',
        'progress',
        'processed',
        'total',
        'source_path',
        'output_path',
        'output_name',
        'payload',
        'failure_report',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'failure_report' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by', 'uuid');
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_COMPLETED_WITH_ERRORS,
            self::STATUS_FAILED,
        ], true);
    }

    public function isSuccessful(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_COMPLETED_WITH_ERRORS], true);
    }
}
