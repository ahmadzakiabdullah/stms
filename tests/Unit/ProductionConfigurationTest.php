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
            'app.csp_report_only' => false,
            'session.secure' => true,
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
            'app.csp_report_only' => true,
            'session.secure' => false,
            'queue.default' => 'sync',
            'cache.default' => 'file',
            'mail.default' => 'log',
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Unsafe production configuration detected');

        ProductionConfiguration::validate();
    }
}
