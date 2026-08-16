<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestCorrelation
{
    public function handle(Request $request, Closure $next): Response
    {
        $correlationId = (string) ($request->header('X-Request-ID') ?: Str::uuid());
        $request->attributes->set('correlation_id', $correlationId);

        Log::withContext([
            'correlation_id' => $correlationId,
            'request_method' => $request->method(),
            'request_path' => $request->path(),
        ]);

        $response = $next($request);
        $response->headers->set('X-Request-ID', $correlationId);

        return $response;
    }
}
