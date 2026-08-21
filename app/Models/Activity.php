<?php

namespace App\Models;

use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    protected static function booted(): void
    {
        static::creating(function (self $activity): void {
            $properties = $activity->properties?->toArray() ?? [];
            $audit = is_array($properties['audit'] ?? null) ? $properties['audit'] : [];

            $audit += array_filter([
                'actor_id' => $activity->causer_id,
                'action' => $activity->event,
                'subject_id' => $activity->subject_id,
                'subject_type' => $activity->subject_type,
                'organization_id' => self::resolveOrganizationId($activity),
                'correlation_id' => self::resolveCorrelationId(),
            ], static fn (mixed $value): bool => $value !== null && $value !== '');

            $properties['audit'] = $audit;
            $activity->properties = collect($properties);
        });
    }

    private static function resolveOrganizationId(self $activity): ?string
    {
        $subject = $activity->subject;

        if ($subject instanceof Model && isset($subject->organization_id)) {
            return (string) $subject->organization_id;
        }

        $causer = $activity->causer;

        if ($causer instanceof Model && isset($causer->organization_id)) {
            return (string) $causer->organization_id;
        }

        return TenantContext::getOrganizationId();
    }

    private static function resolveCorrelationId(): ?string
    {
        if (! app()->bound('request')) {
            return null;
        }

        return Arr::get(request()->attributes->all(), 'correlation_id');
    }
}
