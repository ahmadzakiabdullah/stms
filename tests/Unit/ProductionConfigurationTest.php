<?php

namespace Tests\Unit;

use App\Support\ProductionConfiguration;
use LogicException;
use Tests\TestCase;

class ProductionConfigurationTest extends TestCase
{
    public function test_safe_production_configuration_passes(): void
    {
        config([
            'app.debug' => false,
            'app.production_config_enforce' => true,
            'app.csp_report_only' => false,
            'app.email_verification_required' => true,
            'app.timezone' => 'Asia/Kuala_Lumpur',
            'session.secure' => true,
            'session.driver' => 'redis',
            'queue.default' => 'redis',
            'cache.default' => 'redis',
            'mail.default' => 'smtp',
        ]);

        ProductionConfiguration::validate();

        $this->assertTrue(true);
    }

    public function test_unsafe_production_configuration_is_rejected(): void
    {
        config([
            'app.debug' => true,
            'app.production_config_enforce' => false,
            'app.csp_report_only' => true,
            'app.email_verification_required' => false,
            'app.timezone' => 'UTC',
            'session.secure' => false,
            'session.driver' => 'file',
            'queue.default' => 'sync',
            'cache.default' => 'file',
            'mail.default' => 'log',
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Unsafe production configuration detected');

        ProductionConfiguration::validate();
    }

    public function test_invalid_checks_can_be_reported_without_throwing(): void
    {
        config([
            'app.debug' => true,
            'app.production_config_enforce' => false,
            'app.csp_report_only' => true,
            'app.email_verification_required' => false,
            'app.timezone' => 'UTC',
            'session.secure' => false,
            'session.driver' => 'file',
            'queue.default' => 'sync',
            'cache.default' => 'file',
            'mail.default' => 'log',
        ]);

        $this->assertSame([
            'APP_DEBUG=false',
            'PRODUCTION_CONFIG_ENFORCE=true',
            'CSP_REPORT_ONLY=false',
            'EMAIL_VERIFICATION_REQUIRED=true',
            'APP_TIMEZONE=Asia/Kuala_Lumpur',
            'SESSION_SECURE_COOKIE=true',
            'SESSION_DRIVER=redis',
            'QUEUE_CONNECTION=redis',
            'CACHE_STORE=redis',
            'MAIL_MAILER is not log',
        ], ProductionConfiguration::invalidChecks());
    }
}
