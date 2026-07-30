<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthCheckController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $database = 'ok';
        $cache = 'ok';

        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $database = 'error: '.$e->getMessage();
        }

        try {
            Cache::store(config('cache.default'))->get('health_check');
        } catch (\Exception $e) {
            $cache = 'error: '.$e->getMessage();
        }

        $data = [
            'status' => ($database === 'ok' && $cache === 'ok') ? 'ok' : 'degraded',
            'database' => $database,
            'cache' => $cache,
            'timestamp' => now()->toIso8601String(),
        ];

        $httpStatus = $data['status'] === 'ok' ? 200 : 503;

        return response()->json($data, $httpStatus);
    }
}
