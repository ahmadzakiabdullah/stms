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
            'database',
            'cache',
            'timestamp',
        ]);
        $response->assertJson(['status' => 'ok']);
    }

    public function test_health_endpoint_does_not_require_auth(): void
    {
        $response = $this->get('/health');

        $response->assertOk();
    }
}
