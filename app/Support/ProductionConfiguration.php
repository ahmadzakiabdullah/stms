<?php

namespace App\Support;

use LogicException;

final class ProductionConfiguration
{
    public static function validate(): void
    {
        $checks = [
            'APP_DEBUG=false' => ! config('app.debug'),
            'CSP_REPORT_ONLY=false' => config('app.csp_report_only') === false,
            'SESSION_SECURE_COOKIE=true' => config('session.secure') === true,
            'QUEUE_CONNECTION is not sync' => config('queue.default') !== 'sync',
            'CACHE_STORE is not file' => config('cache.default') !== 'file',
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
