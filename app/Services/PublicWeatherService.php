<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PublicWeatherService
{
    public function current(): ?array
    {
        return Cache::remember('public-weather:durian-tunggal', now()->addMinutes(10), function (): ?array {
            try {
                $response = Http::timeout(3)->get('https://api.open-meteo.com/v1/forecast', [
                    'latitude' => 2.3139,
                    'longitude' => 102.2802,
                    'current' => 'temperature_2m',
                    'timezone' => 'Asia/Kuala_Lumpur',
                ]);

                if (! $response->successful() || ! is_numeric($response->json('current.temperature_2m'))) {
                    return null;
                }

                return [
                    'location' => 'Durian Tunggal',
                    'temperature' => (int) round((float) $response->json('current.temperature_2m')),
                ];
            } catch (\Throwable) {
                return null;
            }
        });
    }
}
