<?php

namespace App\Http\Controllers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

abstract class Controller
{
    /**
     * Run a paginated query safely.
     * On failure (missing table/column on prod when migrations not run),
     * return an empty paginator so the page can still render.
     */
    protected function safePaginatedQuery(\Closure $callback, ?\Closure $fallback = null): LengthAwarePaginator
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            $this->logFallback('paginated_query', $e);
            if ($fallback) {
                return $fallback();
            }

            return new LengthAwarePaginator([], 0, 15, 1, [
                'path' => request()->url(),
            ]);
        }
    }

    /**
     * Run a collection query safely.
     * Returns empty collection on failure.
     */
    protected function safeCollectionQuery(\Closure $callback, ?\Closure $fallback = null): Collection
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            $this->logFallback('collection_query', $e);
            if ($fallback) {
                return $fallback();
            }

            return collect();
        }
    }

    protected function logFallback(string $operation, \Throwable $exception, array $context = []): void
    {
        $user = request()->user();

        Log::warning('Controller fallback activated.', array_merge([
            'operation' => $operation,
            'exception' => $exception,
            'correlation_id' => request()->attributes->get('correlation_id'),
            'user_id' => $user?->uuid,
            'organization_id' => $user?->organization_id,
            'route' => request()->path(),
        ], $context));
    }
}
