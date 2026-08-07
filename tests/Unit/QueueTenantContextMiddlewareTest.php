<?php

namespace Tests\Unit;

use App\Contracts\TenantAwareJob;
use App\Queue\Middleware\SetTenantContext;
use App\Services\TenantContext;
use LogicException;
use RuntimeException;
use Tests\TestCase;

class QueueTenantContextMiddlewareTest extends TestCase
{
    protected function tearDown(): void
    {
        TenantContext::reset();
        parent::tearDown();
    }

    public function test_it_sets_and_always_clears_the_job_tenant(): void
    {
        $job = new class implements TenantAwareJob
        {
            public function tenantOrganizationId(): string
            {
                return 'organization-a';
            }
        };

        (new SetTenantContext)->handle($job, function () use ($job): void {
            $this->assertSame($job->tenantOrganizationId(), TenantContext::requireOrganization());
            $this->assertTrue(TenantContext::isQueue());
        });

        $this->assertFalse(TenantContext::isInitialized());
    }

    public function test_it_clears_context_when_a_job_throws(): void
    {
        $job = new class implements TenantAwareJob
        {
            public function tenantOrganizationId(): string
            {
                return 'organization-a';
            }
        };

        try {
            (new SetTenantContext)->handle($job, fn () => throw new RuntimeException('boom'));
            $this->fail('Expected job exception.');
        } catch (RuntimeException) {
            $this->assertFalse(TenantContext::isInitialized());
        }
    }

    public function test_it_fails_closed_for_a_non_tenant_aware_job(): void
    {
        $this->expectException(LogicException::class);
        (new SetTenantContext)->handle(new \stdClass, fn () => null);
    }
}
