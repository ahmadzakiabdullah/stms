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
}
