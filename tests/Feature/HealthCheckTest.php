<?php

namespace Tests\Feature;

use App\Services\ReleasePreflightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis;
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

    public function test_release_preflight_reports_unsafe_state_without_mutating_runtime(): void
    {
        config([
            'app.debug' => true,
            'app.production_config_enforce' => false,
            'app.backup.enabled' => false,
            'app.health.monitor_enabled' => false,
            'app.health.token' => null,
            'app.public_org_slug' => null,
            'app.public_session_slug' => null,
            'cache.default' => 'array',
            'queue.default' => 'sync',
            'session.driver' => 'array',
            'mail.default' => 'log',
        ]);

        $this->artisan('stms:release-preflight', ['--json' => true])
            ->expectsOutputToContain('"status": "error"')
            ->assertFailed();
    }

    public function test_release_preflight_passes_safe_connectivity_and_fresh_backup_checks(): void
    {
        $backupPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'stms-preflight-'.bin2hex(random_bytes(6));
        File::ensureDirectoryExists($backupPath);
        File::put($backupPath.DIRECTORY_SEPARATOR.'stms-20260818-120000.zip', 'encrypted-backup-fixture');

        try {
            $this->app['env'] = 'production';
            config([
                'app.debug' => false,
                'app.production_config_enforce' => true,
                'app.csp_report_only' => false,
                'app.email_verification_required' => true,
                'app.timezone' => 'Asia/Kuala_Lumpur',
                'app.backup.enabled' => true,
                'app.backup.path' => $backupPath,
                'app.backup.encryption_key' => str_repeat('a', 32),
                'app.health.monitor_enabled' => true,
                'app.health.token' => 'release-health-token',
                'app.public_org_slug' => 'utem',
                'app.public_session_slug' => 'saf-2026',
                'session.secure' => true,
                'session.driver' => 'redis',
                'session.connection' => 'default',
                'queue.default' => 'redis',
                'queue.connections.redis.connection' => 'default',
                'cache.default' => 'redis',
                'cache.stores.redis.connection' => 'cache',
                'mail.default' => 'smtp',
                'mail.mailers.smtp.transport' => 'smtp',
                'mail.mailers.smtp.host' => 'smtp.example.test',
                'mail.mailers.smtp.port' => 587,
                'mail.from.address' => 'noreply@example.test',
            ]);

            $connection = \Mockery::mock();
            $connection->shouldReceive('command')->with('ping')->twice()->andReturn('PONG');
            Redis::shouldReceive('connection')->with('cache')->once()->andReturn($connection);
            Redis::shouldReceive('connection')->with('default')->once()->andReturn($connection);

            $this->artisan('stms:release-preflight', ['--json' => true])
                ->expectsOutputToContain('"status": "ok"')
                ->assertSuccessful();
        } finally {
            File::deleteDirectory($backupPath);
        }
    }

    public function test_release_preflight_rejects_invalid_backup_age_option(): void
    {
        $this->artisan('stms:release-preflight', ['--max-backup-age-hours' => 0])
            ->expectsOutput('The maximum backup age must be an integer between 1 and 168 hours.')
            ->assertExitCode(2);
    }

    public function test_release_preflight_rejects_log_transport_inside_failover_mailer(): void
    {
        config([
            'cache.default' => 'array',
            'queue.default' => 'sync',
            'session.driver' => 'array',
            'mail.default' => 'failover',
            'mail.mailers.failover.transport' => 'failover',
            'mail.mailers.failover.mailers' => ['smtp', 'log'],
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp.example.test',
            'mail.mailers.smtp.port' => 587,
            'mail.mailers.log.transport' => 'log',
            'mail.from.address' => 'noreply@example.test',
        ]);

        $result = app(ReleasePreflightService::class)->check();

        $this->assertSame('error', $result['checks']['mail']['status']);
    }
}
