<?php

namespace App\Services\Concerns;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

trait ExecutesResilientQueries
{
    protected function safePaginatedQuery(\Closure $callback, ?\Closure $fallback = null): LengthAwarePaginator
    {
        try {
            return $callback();
        } catch (\Throwable $exception) {
            $this->logQueryFallback('paginated_query', $exception);

            return $fallback
                ? $fallback()
                : new LengthAwarePaginator([], 0, 15, 1, ['path' => request()->url()]);
        }
    }

    protected function safeCollectionQuery(\Closure $callback, ?\Closure $fallback = null): Collection
    {
        try {
            return $callback();
        } catch (\Throwable $exception) {
            $this->logQueryFallback('collection_query', $exception);

            return $fallback ? $fallback() : collect();
        }
    }

    private function logQueryFallback(string $operation, \Throwable $exception): void
    {
        $user = request()->user();

        Log::warning('Index data query fallback activated.', [
            'operation' => $operation,
            'exception' => $exception,
            'correlation_id' => request()->attributes->get('correlation_id'),
            'user_id' => $user?->uuid,
            'organization_id' => $user?->organization_id,
            'route' => request()->path(),
        ]);
    }
}
