<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class CheckProductionSmoke extends Command
{
    protected $signature = 'stms:production-smoke
        {--url= : Base production URL, for example https://saf.utem.edu.my}
        {--health-token= : Optional X-Health-Token value for /health}
        {--json : Output machine-readable JSON}';

    protected $description = 'Run read-only HTTP smoke checks against a deployed STMS site';

    public function handle(): int
    {
        $baseUrl = $this->normalizeBaseUrl($this->option('url') ?: config('app.url'));

        if (! filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            $this->error('A valid --url or APP_URL is required.');

            return self::INVALID;
        }

        $checks = [
            'homepage' => $this->check('GET /', $baseUrl.'/'),
            'up' => $this->check('GET /up', $baseUrl.'/up'),
        ];

        $healthToken = trim((string) $this->option('health-token'));

        if ($healthToken !== '') {
            $checks['health'] = $this->check('GET /health', $baseUrl.'/health', [
                'X-Health-Token' => $healthToken,
            ]);
        } else {
            $checks['health'] = [
                'status' => 'skipped',
                'message' => 'No --health-token provided.',
                'url' => $baseUrl.'/health',
            ];
        }

        $requiredStatuses = collect($checks)
            ->reject(fn (array $check) => $check['status'] === 'skipped')
            ->pluck('status');

        $result = [
            'status' => $requiredStatuses->every(fn (string $status) => $status === 'ok') ? 'ok' : 'error',
            'base_url' => $baseUrl,
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ];

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(
                ['Check', 'Status', 'HTTP', 'Message'],
                collect($checks)->map(fn (array $check, string $name) => [
                    $name,
                    strtoupper($check['status']),
                    $check['http_status'] ?? '-',
                    $check['message'],
                ])->values()->all()
            );
        }

        return $result['status'] === 'ok' ? self::SUCCESS : self::FAILURE;
    }

    /** @param array<string, string> $headers */
    private function check(string $label, string $url, array $headers = []): array
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders($headers)
                ->get($url);

            return [
                'status' => $response->ok() ? 'ok' : 'error',
                'message' => $response->ok()
                    ? "{$label} returned HTTP 200."
                    : "{$label} returned HTTP {$response->status()}.",
                'url' => $url,
                'http_status' => $response->status(),
                'bytes' => strlen($response->body()),
            ];
        } catch (ConnectionException $exception) {
            return [
                'status' => 'error',
                'message' => "{$label} failed: {$exception->getMessage()}",
                'url' => $url,
            ];
        }
    }

    private function normalizeBaseUrl(string $url): string
    {
        $url = trim($url);

        if (preg_match('/\[[^\]]+\]\((https?:\/\/[^)]+)\)/', $url, $matches) === 1) {
            $url = $matches[1];
        }

        return rtrim($url, '/');
    }
}
