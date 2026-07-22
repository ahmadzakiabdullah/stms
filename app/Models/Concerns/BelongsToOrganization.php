<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Automatic multi-tenant scoping for models that belong to an organization.
 *
 * Applies a global scope that filters by the authenticated user's organization_id,
 * unless the user has the 'super-admin' role.
 *
 * Usage:
 *   use App\Models\Concerns\BelongsToOrganization;
 *
 *   class Sport extends Model
 *   {
 *       use BelongsToOrganization;
 *   }
 */
trait BelongsToOrganization
{
    /**
     * Boot the trait and register the global organization scope.
     */
    protected static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope('organization', function (Builder $query) {
            // Never apply to the users table — it interferes with Laravel's authentication
            // (Auth::user() is not reliably available and can cause recursion or auth failures).
            if ($query->getModel()->getTable() === 'users') {
                return;
            }

            $user = Auth::user();

            if ($user && !$user->hasRole('super-admin') && !empty($user->organization_id)) {
                $table = $query->getModel()->getTable();

                // Use qualified column name to avoid ambiguity in joins
                $query->where("{$table}.organization_id", $user->organization_id);
            }
        });
    }

    /**
     * Remove the organization global scope for the current query.
     * Useful for super-admins or special cross-org operations.
     */
    public function scopeWithoutOrganizationScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('organization');
    }

    /**
     * Scope to records belonging to a specific organization (explicit).
     */
    public function scopeForOrganization(Builder $query, $organizationId): Builder
    {
        return $query->withoutGlobalScope('organization')
            ->where($this->getTable() . '.organization_id', $organizationId);
    }
}
