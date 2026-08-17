<?php

namespace App\Support;

use LogicException;

final class ProductionConfiguration
{
    public static function validate(): void
    {
        $checks = [
            'APP_DEBUG=false' => ! config('app.debug'),
            'PRODUCTION_CONFIG_ENFORCE=true' => config('app.production_config_enforce') === true,
            'CSP_REPORT_ONLY=false' => config('app.csp_report_only') === false,
            'EMAIL_VERIFICATION_REQUIRED=true' => config('app.email_verification_required') === true,
            'APP_TIMEZONE=Asia/Kuala_Lumpur' => config('app.timezone') === 'Asia/Kuala_Lumpur',
            'SESSION_SECURE_COOKIE=true' => config('session.secure') === true,
            'SESSION_DRIVER=redis' => config('session.driver') === 'redis',
            'QUEUE_CONNECTION=redis' => config('queue.default') === 'redis',
            'CACHE_STORE=redis' => config('cache.default') === 'redis',
            'MAIL_MAILER is not log' => config('mail.default') !== 'log',
        ];

        $invalid = array_keys(array_filter($checks, static fn (bool $valid): bool => ! $valid));

        if ($invalid !== []) {
            throw new LogicException(
                'Unsafe production configuration detected: '.implode(', ', $invalid).'.'
            );
        }
    }
}
