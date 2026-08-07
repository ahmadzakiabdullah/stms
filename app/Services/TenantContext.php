<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use LogicException;

/**
 * Request-scoped tenant context.
 *
 * State lives on a container-scoped instance (bound in AppServiceProvider) so it
 * is naturally reset per request — including under Long-running servers such as
 * Octane and queue workers — instead of leaking through process-global statics.
 *
 * The static methods are a thin facade over the container instance and retained
 * for backward compatibility.
 */
class TenantContext
{
    private ?string $organizationId = null;

    private ?string $context = null;

    /**
     * Explicitly recorded "deliberately unscoped" state (super-admin, guest,
     * console bootstrap) so a missing organization cannot be confused with an
     * intent-to-bypass that was never audited.
     */
    private bool $bypass = false;

    private ?string $bypassReason = null;

    public static function instance(): self
    {
        return app(static::class);
    }

    public static function setOrganizationId(?string $organizationId): void
    {
        $ctx = static::instance();
        $ctx->organizationId = $organizationId;
        $ctx->bypass = false;
        $ctx->bypassReason = null;
    }

    public static function getOrganizationId(): ?string
    {
        $ctx = static::instance();

        if ($ctx->bypass) {
            return null;
        }

        if ($ctx->organizationId !== null) {
            return $ctx->organizationId;
        }

        $user = Auth::user();

        if ($user && ! $user->hasRole('super-admin')) {
            return $user->organization_id;
        }

        return null;
    }

    public static function getOrganization(): ?Organization
    {
        $orgId = self::getOrganizationId();

        if ($orgId === null) {
            return null;
        }

        return Organization::find($orgId);
    }

    public static function isSuperAdmin(): bool
    {
        $user = Auth::user();

        return $user && $user->hasRole('super-admin');
    }

    public static function hasOrganization(): bool
    {
        return self::getOrganizationId() !== null;
    }

    /**
     * Record an explicit, auditable opt-out from tenant scoping.
     */
    public static function setBypass(string $reason): void
    {
        $ctx = static::instance();
        $ctx->organizationId = null;
        $ctx->bypass = true;
        $ctx->bypassReason = $reason;
    }

    public static function isBypassing(): bool
    {
        return static::instance()->bypass;
    }

    public static function bypassReason(): ?string
    {
        return static::instance()->bypassReason;
    }

    /**
     * Whether the context has been initialised (either bound to an organization
     * or explicitly bypassed) by the current request/pipeline.
     */
    public static function isInitialized(): bool
    {
        $ctx = static::instance();

        return $ctx->bypass || $ctx->organizationId !== null;
    }

    /**
     * Fail-closed helper for operations that absolutely require a tenant.
     * Throws unless an organization is resolvable (and no explicit bypass is
     * in effect), instead of silently running an unscoped query.
     */
    public static function requireOrganization(): string
    {
        $ctx = static::instance();

        if ($ctx->bypass) {
            throw new LogicException('Tenant-scoped operation ran while tenant context is explicitly bypassed ('.($ctx->bypassReason ?? 'unknown').').');
        }

        $organizationId = $ctx->organizationId;

        if ($organizationId === null) {
            $user = Auth::user();

            if ($user && ! $user->hasRole('super-admin')) {
                $organizationId = $user->organization_id;
            }
        }

        if ($organizationId === null) {
            throw new LogicException('Tenant required but no organization_id is available for the current request.');
        }

        return $organizationId;
    }

    public static function setContext(?string $context): void
    {
        static::instance()->context = $context;
    }

    public static function getContext(): ?string
    {
        return static::instance()->context;
    }

    public static function isConsole(): bool
    {
        return static::instance()->context === 'console';
    }

    public static function isQueue(): bool
    {
        return static::instance()->context === 'queue';
    }

    public static function reset(): void
    {
        $ctx = static::instance();
        $ctx->organizationId = null;
        $ctx->context = null;
        $ctx->bypass = false;
        $ctx->bypassReason = null;
    }
}
