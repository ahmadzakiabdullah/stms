<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->get('/health');

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'components' => [
                'database' => ['status', 'latency_ms'],
                'cache' => ['status', 'latency_ms'],
                'queue' => ['status', 'latency_ms', 'pending', 'failed'],
                'disk' => ['status', 'latency_ms', 'free_mb'],
            ],
            'timestamp',
        ]);
        $response->assertJson(['status' => 'ok']);
    }

    public function test_health_endpoint_does_not_require_auth(): void
    {
        $response = $this->get('/health');

        $response->assertOk();
    }

    public function test_production_health_endpoint_requires_token(): void
    {
        $this->app['env'] = 'production';
        config(['app.health.token' => 'test-health-token']);

        $this->get('/health')->assertNotFound();
        $this->withHeader('X-Health-Token', 'test-health-token')->get('/health')->assertOk();
    }
}
