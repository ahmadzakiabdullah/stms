<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestCorrelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_id_is_generated_and_returned(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $this->assertNotEmpty($response->headers->get('X-Request-ID'));
    }

    public function test_existing_request_id_is_preserved(): void
    {
        $response = $this->withHeader('X-Request-ID', 'audit-request-123')->get('/');

        $response->assertOk()->assertHeader('X-Request-ID', 'audit-request-123');
    }
}
