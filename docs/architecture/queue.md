# Queue Architecture

This document outlines the architecture for background job processing in the STMS application.

## Overview

Queues are used to defer the processing of time-consuming tasks, such as sending emails, generating reports, or processing file exports, to a background process. This allows the application to respond to user requests much faster.

## Current Implementation

-   **Driver**: Environment-controlled. Production examples and Docker use Redis; local/test environments may use database or synchronous queues.
-   **Worker**: A queue worker process is needed to pull jobs from the table and execute them. The `composer dev` script includes a `php artisan queue:listen` command for local development. For production, a more robust worker setup using Supervisor is required.

## Weaknesses & Risks

Similar to the caching driver, using the `database` for queues is a performance risk.

-   **Database Load**: It adds constant read/write pressure to the database as workers poll for new jobs.
-   **Reliability**: While functional, it is not as robust or high-throughput as dedicated queueing systems.

## Recommended Strategy

For production environments, a dedicated queueing service should be used.

-   **Recommended Driver**: **Redis** (`redis`)
-   **Configuration**:
    -   Set `QUEUE_CONNECTION=redis` in the `.env` file.
    -   Ensure a Redis server is available. The project's `docker-compose.yml` already includes a Redis service.
-   **Production Worker**:
    -   Use a process manager like **Supervisor** to run `php artisan queue:work redis --sleep=3 --tries=3 --timeout=90`. The included Docker configuration runs this worker and Laravel Scheduler.
    -   For higher availability, consider running multiple workers.

## Types of Jobs

The following tasks are good candidates for being moved to a queued job:

-   All email notifications.
-   Generating and exporting PDF or Excel files (`FixtureExport`, `ResultExport`, etc.).
-   Any long-running data processing tasks.
-   Webhook dispatching.
