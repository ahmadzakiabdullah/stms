# Performance

This document outlines the performance characteristics of the STMS application and provides recommendations for optimization.

## Current State

The application is generally performant for small to medium-scale use cases. The code quality is high, and the database queries observed during the audit were efficient. The use of modern frameworks (Laravel 13, React 18) provides a solid foundation.

## Key Performance Considerations

### 1. Cache and Queue Drivers

-   **Current Implementation**: Drivers are environment-controlled. Production examples and Docker use Redis; tests use isolated array/synchronous drivers.
-   **Risk**: This presents the most significant performance bottleneck, especially under high load. Using the primary application database for frequent, short-lived cache operations and for managing background jobs adds significant overhead to the database server. This can lead to slower response times and potential database contention issues as user traffic grows.
-   **Recommendation**: For all production and staging environments, it is strongly recommended to use an in-memory data store like **Redis**. Redis is purpose-built for high-throughput caching and queuing, and offloading this work from the main database will significantly improve application performance and scalability.

### 2. Database Indexing

-   **Current Implementation**: The database migrations show good use of indexes for foreign keys and composite unique keys.
-   **Recommendation**: As the application grows, continue to monitor slow queries. Use tools like Laravel Telescope or a dedicated APM to identify queries that would benefit from additional indexing. Pay special attention to columns that are frequently used in `WHERE` clauses, `JOIN`s, and `ORDER BY` operations.

### 3. Frontend Performance

-   **Current Implementation**: The frontend uses Vite for fast builds and has a modern stack. The use of Inertia.js avoids the overhead of a full client-side routing solution while providing a responsive SPA-like experience.
-   **Recommendation**: Continue to leverage code splitting, lazy loading of components where appropriate, and ensure that image assets are optimized for the web.

## Action Plan

1.  **Deployment Verification**: Confirm production is actually using Redis for `CACHE_STORE` and `QUEUE_CONNECTION`; repository examples alone do not prove runtime configuration.
2.  **Ongoing**: Implement a monitoring solution (like Laravel Telescope in development or a commercial APM in production) to proactively identify performance bottlenecks in database queries and application code.
# Automated Baselines

- `PerformanceBaselineTest` limits the dashboard request to 40 database queries with representative domain data.
- `tests/performance/smoke.js` defines a k6 smoke baseline of less than 1% request failures and p95 below 750 ms for `/health`.
- `npm run build:budget` limits every emitted JavaScript chunk to 400 KB and CSS asset to 100 KB uncompressed.

Run k6 against a non-production or explicitly approved environment:

```bash
k6 run -e BASE_URL=https://staging.example.test -e VUS=10 -e DURATION=30s tests/performance/smoke.js
```

Authenticated dashboard/ranking/export load scenarios remain deployment follow-up because they require controlled credentials and representative protected data.
