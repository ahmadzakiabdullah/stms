<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PublicWeatherService
{
    public function current(): ?array
    {
        $key = 'public-weather:melaka-current';
        $lastGoodKey = 'public-weather:melaka-current:last-good';

        if (Cache::has($key)) {
            $cached = Cache::get($key);

            $normalizedCurrent = $this->normalizeWeather($cached, false);

            if ($normalizedCurrent !== null) {
                return $normalizedCurrent;
            }

            $lastGood = Cache::get($lastGoodKey);

            return $this->normalizeWeather($lastGood, true);
        }

        try {
            try {
                $response = $this->requestCurrent(false);
            } catch (\Throwable $e) {
                if (! str_contains($e->getMessage(), 'cURL error 77')) {
                    throw $e;
                }

                // Fallback for environments with broken CA bundle path.
                $response = $this->requestCurrent(true);
            }

            if (! $response->successful() || ! is_numeric($response->json('current.temperature_2m'))) {
                throw new \RuntimeException('Open-Meteo response tidak sah: HTTP '.$response->status());
            }

            $data = [
                'location' => 'Durian Tunggal',
                'temperature' => (int) round((float) $response->json('current.temperature_2m')),
                'observed_at' => now()->toIso8601String(),
                'is_stale' => false,
            ];

            Cache::put($key, $data, now()->addMinutes(10));
            Cache::put($lastGoodKey, $data, now()->addHours(6));

            return $data;
        } catch (\Throwable $e) {
            Log::warning('Gagal mengambil data cuaca', ['error' => $e->getMessage()]);

            Cache::put($key, null, now()->addMinutes(2));

            $lastGood = Cache::get($lastGoodKey);

            $normalizedLastGood = $this->normalizeWeather($lastGood, true);

            if ($normalizedLastGood !== null) {
                return $normalizedLastGood;
            }

            return null;
        }
    }

    private function requestCurrent(bool $withoutVerifying): Response
    {
        $request = Http::timeout(10)
            ->retry(2, 100);

        if ($withoutVerifying) {
            $request = $request->withoutVerifying();
        }

        return $request->get('https://api.open-meteo.com/v1/forecast', [
            'latitude' => 2.3139,
            'longitude' => 102.2802,
            'current' => 'temperature_2m',
            'timezone' => 'Asia/Kuala_Lumpur',
        ]);
    }

    private function isValidWeather(mixed $value): bool
    {
        return is_array($value)
            && isset($value['location'])
            && is_string($value['location'])
            && is_numeric($value['temperature'] ?? null);
    }

    private function normalizeWeather(mixed $value, bool $isStale): ?array
    {
        if (! $this->isValidWeather($value)) {
            return null;
        }

        return [
            'location' => trim($value['location']) !== '' ? $value['location'] : 'Melaka',
            'temperature' => (int) round((float) $value['temperature']),
            'observed_at' => isset($value['observed_at']) && is_string($value['observed_at'])
                ? $value['observed_at']
                : null,
            'is_stale' => $isStale,
        ];
    }
}
