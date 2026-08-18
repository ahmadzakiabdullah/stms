<?php

namespace App\Services;

use App\Support\ProductionConfiguration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis;
use Throwable;

final class ReleasePreflightService
{
    /**
     * @return array{status: string, checks: array<string, array<string, mixed>>, timestamp: string}
     */
    public function check(int $maxBackupAgeHours = 24): array
    {
        $checks = [
            'environment' => $this->environment(),
            'configuration' => $this->configuration(),
            'database' => $this->database(),
            'redis' => $this->redis(),
            'mail' => $this->mail(),
            'backup' => $this->backup($maxBackupAgeHours),
            'monitoring' => $this->monitoring(),
            'public_portal' => $this->publicPortal(),
        ];

        return [
            'status' => collect($checks)->every(fn (array $check) => $check['status'] === 'ok') ? 'ok' : 'error',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    private function environment(): array
    {
        return app()->environment('production')
            ? $this->ok('APP_ENV is production.')
            : $this->error('APP_ENV must be production for release evidence.');
    }

    private function configuration(): array
    {
        $invalid = ProductionConfiguration::invalidChecks();

        return $invalid === []
            ? $this->ok('Production configuration guard checks passed.')
            : $this->error('Unsafe production configuration remains.', ['invalid' => $invalid]);
    }

    private function database(): array
    {
        return $this->attempt('Database SELECT 1 succeeded.', function (): array {
            DB::select('SELECT 1');

            return ['connection' => DB::getDefaultConnection()];
        });
    }

    private function redis(): array
    {
        if (
            config('cache.default') !== 'redis'
            || config('queue.default') !== 'redis'
            || config('session.driver') !== 'redis'
        ) {
            return $this->error('Cache, queue and session must all use Redis.');
        }

        $connections = array_values(array_unique(array_filter([
            config('cache.stores.redis.connection', 'cache'),
            config('queue.connections.redis.connection', 'default'),
            config('session.connection') ?: 'default',
        ], 'is_string')));

        return $this->attempt('Redis PING succeeded for all configured connections.', function () use ($connections): array {
            foreach ($connections as $connection) {
                Redis::connection($connection)->command('ping');
            }

            return ['connections' => $connections];
        });
    }

    private function mail(): array
    {
        $mailer = config('mail.default');
        $mailers = is_string($mailer) ? $this->leafMailers($mailer) : null;

        if (! is_string($mailer) || $mailers === null || $mailers === []) {
            return $this->error('Default mailer must use a real delivery transport.');
        }

        foreach ($mailers as $leafMailer => $transport) {
            if (in_array($transport, ['array', 'log'], true)) {
                return $this->error('Default mailer chain must not contain log or array transports.');
            }

            if ($transport === 'smtp') {
                $host = config("mail.mailers.{$leafMailer}.host");
                $port = (int) config("mail.mailers.{$leafMailer}.port");
                $from = config('mail.from.address');

                if (! is_string($host) || trim($host) === '' || $port <= 0 || ! is_string($from) || trim($from) === '') {
                    return $this->error('SMTP host, port and from address must be configured.');
                }
            }
        }

        return $this->ok('Mailer configuration is complete; delivery remains an operator test.', [
            'mailer' => $mailer,
            'transports' => array_values(array_unique($mailers)),
        ]);
    }

    /**
     * @param  list<string>  $visited
     * @return array<string, string>|null
     */
    private function leafMailers(string $mailer, array $visited = []): ?array
    {
        if (in_array($mailer, $visited, true)) {
            return null;
        }

        $transport = config("mail.mailers.{$mailer}.transport");
        if (! is_string($transport) || trim($transport) === '') {
            return null;
        }

        if (! in_array($transport, ['failover', 'roundrobin'], true)) {
            return [$mailer => $transport];
        }

        $children = config("mail.mailers.{$mailer}.mailers");
        if (! is_array($children) || $children === []) {
            return null;
        }

        $leaves = [];
        foreach ($children as $child) {
            if (! is_string($child) || ($resolved = $this->leafMailers($child, [...$visited, $mailer])) === null) {
                return null;
            }

            $leaves = array_merge($leaves, $resolved);
        }

        return $leaves;
    }

    private function backup(int $maxBackupAgeHours): array
    {
        if (! config('app.backup.enabled')) {
            return $this->error('Scheduled backups must be enabled.');
        }

        if (strlen((string) config('app.backup.encryption_key')) < 32) {
            return $this->error('Backup encryption key must contain at least 32 characters.');
        }

        $configuredPath = config('app.backup.path');
        if (! is_string($configuredPath) || ! File::isDirectory($configuredPath)) {
            return $this->error('Configured backup path is not an accessible directory.');
        }

        $backupPath = realpath($configuredPath);
        $applicationPath = realpath(base_path());
        if ($backupPath === false || $applicationPath === false || $this->isWithin($backupPath, $applicationPath)) {
            return $this->error('Backup path must resolve outside the application repository.');
        }

        $archives = File::glob(rtrim($backupPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'stms-*.zip');
        if ($archives === []) {
            return $this->error('No STMS backup archive exists in the configured off-repository path.');
        }

        usort($archives, fn (string $left, string $right) => File::lastModified($right) <=> File::lastModified($left));
        $latest = $archives[0];
        $ageHours = round((now()->getTimestamp() - File::lastModified($latest)) / 3600, 2);

        if ($ageHours < 0 || $ageHours > $maxBackupAgeHours || File::size($latest) <= 0) {
            return $this->error('Latest backup is empty, future-dated or older than the approved threshold.', [
                'archive' => basename($latest),
                'age_hours' => $ageHours,
                'max_age_hours' => $maxBackupAgeHours,
            ]);
        }

        return $this->ok('Recent off-repository backup found; isolated restore evidence is still required.', [
            'archive' => basename($latest),
            'age_hours' => $ageHours,
            'size_bytes' => File::size($latest),
            'sha256' => hash_file('sha256', $latest),
        ]);
    }

    private function monitoring(): array
    {
        if (! config('app.health.monitor_enabled') || trim((string) config('app.health.token')) === '') {
            return $this->error('Health monitoring and a non-empty health endpoint token are required.');
        }

        return $this->ok('Internal health monitoring is enabled; external alert receipt remains operator evidence.');
    }

    private function publicPortal(): array
    {
        if (trim((string) config('app.public_org_slug')) === '' || trim((string) config('app.public_session_slug')) === '') {
            return $this->error('PUBLIC_ORG_SLUG and PUBLIC_SESSION_SLUG are required.');
        }

        return $this->ok('Public organization and session selectors are configured.');
    }

    private function attempt(string $successMessage, \Closure $callback): array
    {
        try {
            return $this->ok($successMessage, $callback());
        } catch (Throwable) {
            return $this->error('Connectivity check failed; inspect protected application logs.');
        }
    }

    private function isWithin(string $path, string $parent): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $path = strtolower($path);
            $parent = strtolower($parent);
        }

        return $path === $parent || str_starts_with($path, rtrim($parent, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR);
    }

    private function ok(string $message, array $details = []): array
    {
        return ['status' => 'ok', 'message' => $message, 'details' => $details];
    }

    private function error(string $message, array $details = []): array
    {
        return ['status' => 'error', 'message' => $message, 'details' => $details];
    }
}
