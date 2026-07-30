<?php

namespace App\Http\Controllers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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
            if ($fallback) {
                return $fallback();
            }

            return collect();
        }
    }
}
