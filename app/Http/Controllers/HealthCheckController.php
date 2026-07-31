<?php

namespace App\Http\Controllers;

use App\Services\SystemHealthService;
use Illuminate\Http\JsonResponse;

class HealthCheckController extends Controller
{
    public function __invoke(SystemHealthService $health): JsonResponse
    {
        $data = $health->check();

        $httpStatus = $data['status'] === 'ok' ? 200 : 503;

        return response()->json($data, $httpStatus);
    }
}
