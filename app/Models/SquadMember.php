<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SquadMember extends Model
{
    use HasFactory, HasUuids, BelongsToOrganization;

    protected $fillable = [
        'event_participant_id',
        'organization_id',
        'name',
        'role',
        'identification_no',
        'phone',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function eventParticipant()
    {
        return $this->belongsTo(EventParticipant::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
