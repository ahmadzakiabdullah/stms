# Caching Strategy

This document describes the caching strategy for the STMS application.

## Overview

Caching is used to store the results of expensive operations and reduce the number of database queries, leading to faster response times. Laravel's cache facade provides a unified API for various caching backends.

## Current Implementation

-   **Driver**: The application currently uses the `database` driver for caching. This means that cached data is stored in a table within the main application database.
-   **Usage**: The audit found that caching is used in several places, including the main dashboard, to cache counts and other aggregated data.

## Weaknesses & Risks

Using the `database` driver for caching is simple to set up but is not a scalable solution.

-   **Performance Bottleneck**: It adds extra load to the very database we are trying to protect from excessive queries.
-   **Scalability**: It does not perform well under high load compared to in-memory solutions.

## Recommended Strategy

For any production or staging environment, the caching driver should be switched to an in-memory store.

-   **Recommended Driver**: **Redis** (`redis`)
-   **Configuration**:
    -   Set `CACHE_STORE=redis` in the `.env` file.
    -   Ensure a Redis server is available and configured in `config/database.php` and the relevant `.env` variables (`REDIS_HOST`, `REDIS_PORT`, etc.). The project's `docker-compose.yml` already includes a Redis service.

## What to Cache

The following are good candidates for caching:

-   **Aggregated Data**: Counts, sums, and other statistics that are displayed on dashboards and don't need to be real-time to the second.
-   **Configuration**: Application settings that are stored in the database.
-   **Permissions**: Resolved role and permission sets from the Spatie package.
-   **Complex Queries**: The results of any database query that is slow and whose results are not highly volatile.

When implementing caching, always have a clear cache invalidation strategy. For example, when a tournament's details are updated, any cached data related to that tournament should be cleared.
