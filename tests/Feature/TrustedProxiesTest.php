<?php

namespace Tests\Feature;

use App\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Tests\TestCase;

class TrustedProxiesTest extends TestCase
{
    public function test_configured_proxy_allowlist_is_resolved_after_the_application_boots(): void
    {
        config()->set('app.trusted_proxies', ['10.1.2.10']);

        $request = Request::create('/', 'GET', server: [
            'REMOTE_ADDR' => '10.1.2.10',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.25',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ]);

        (new TrustProxies)->handle($request, function (Request $request): void {
            $this->assertSame('203.0.113.25', $request->getClientIp());
            $this->assertTrue($request->isSecure());
        });
    }
}
