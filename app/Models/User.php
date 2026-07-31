<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'organization_id', 'participant_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use BelongsToOrganization, HasFactory, HasRoles, HasUuids, Notifiable, SoftDeletes;

    // UUID primary key support (after migration 2026_06_21_000001)
    // Primary key is now uuid for consistency with other models.
    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    // Note: The trait skips its own table to avoid auth issues. List scoping is manual in controller.

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public function sports()
    {
        return $this->belongsToMany(Sport::class, 'sport_user', 'user_id', 'sport_id')
            ->withPivot('organization_id')
            ->withTimestamps();
    }

    /**
     * Whether the user may manage matches/results for the given sport.
     * Admin-sport users are limited to the sports assigned to them.
     */
    public function canManageSport(string $sportId): bool
    {
        if ($this->hasRole('super-admin') || $this->hasRole('org-admin')) {
            return true;
        }

        if ($this->hasRole('admin-sport')) {
            return $this->sports()->whereKey($sportId)->exists();
        }

        return $this->hasPermissionTo('manage_matches')
            || $this->hasPermissionTo('manage_results');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
}
