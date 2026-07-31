<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SystemHealthService
{
    public function check(): array
    {
        $components = [
            'database' => $this->database(),
            'cache' => $this->cache(),
            'queue' => $this->queue(),
            'disk' => $this->disk(),
        ];

        return [
            'status' => collect($components)->every(fn (array $component) => $component['status'] === 'ok') ? 'ok' : 'degraded',
            'components' => $components,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    private function database(): array
    {
        return $this->measure('database', function (): array {
            DB::select('SELECT 1');

            return [];
        });
    }

    private function cache(): array
    {
        return $this->measure('cache', function (): array {
            $key = 'health:'.bin2hex(random_bytes(8));
            Cache::put($key, 'ok', 10);
            if (Cache::get($key) !== 'ok') {
                throw new \RuntimeException('Cache read-after-write failed.');
            }
            Cache::forget($key);

            return [];
        });
    }

    private function queue(): array
    {
        return $this->measure('queue', function (): array {
            $pending = Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0;
            $failed = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
            $healthy = $pending <= config('app.health.max_pending_jobs')
                && $failed <= config('app.health.max_failed_jobs');

            return ['status' => $healthy ? 'ok' : 'error', 'pending' => $pending, 'failed' => $failed];
        });
    }

    private function disk(): array
    {
        return $this->measure('disk', function (): array {
            $freeBytes = disk_free_space(storage_path());
            if ($freeBytes === false) {
                throw new \RuntimeException('Disk space is unavailable.');
            }

            $freeMb = (int) floor($freeBytes / 1024 / 1024);

            return [
                'status' => $freeMb >= config('app.health.min_disk_free_mb') ? 'ok' : 'error',
                'free_mb' => $freeMb,
            ];
        });
    }

    private function measure(string $component, callable $callback): array
    {
        $started = hrtime(true);

        try {
            $details = $callback();

            return array_merge([
                'status' => 'ok',
                'latency_ms' => round((hrtime(true) - $started) / 1_000_000, 2),
            ], $details);
        } catch (Throwable $exception) {
            Log::error('Health component failed', ['component' => $component, 'exception' => $exception]);

            return [
                'status' => 'error',
                'latency_ms' => round((hrtime(true) - $started) / 1_000_000, 2),
            ];
        }
    }
}
