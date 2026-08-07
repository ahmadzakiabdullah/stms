<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'username', 'email', 'password', 'organization_id', 'participant_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use BelongsToOrganization, HasFactory, HasRoles, HasUuids, Notifiable, SoftDeletes;

    // UUID primary key support (after migration 2026_06_21_000001)
    // Primary key is now uuid for consistency with other models.
    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    // Note: The trait skips its own table to avoid auth issues. List scoping is manual in controller.

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if ($user->username || ! $user->email) {
                return;
            }

            $base = Str::of(Str::before($user->email, '@'))
                ->lower()->replaceMatches('/[^a-z0-9_-]+/', '_')->trim('_-')->limit(48, '')->value();
            $base = $base !== '' ? $base : 'user';
            $candidate = $base;
            $suffix = 1;

            while (static::withTrashed()->where('username', $candidate)->exists()) {
                $candidate = $base.'_'.$suffix++;
            }

            $user->username = $candidate;
        });
    }

    public function resolveRouteBindingQuery($query, $value, $field = null): Builder
    {
        $query = $query instanceof Model ? $query->newQuery() : $query;
        $actor = Auth::user();

        if ($actor && ! $actor->hasRole('super-admin')) {
            $query->where($query->getModel()->qualifyColumn('organization_id'), $actor->organization_id);
        }

        return $query->where($field ?? $this->getRouteKeyName(), $value);
    }

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
