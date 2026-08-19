## 2024-05-24 - [Avoid Temporary File Race Conditions]
**Vulnerability:** A static temp file path `storage_path('app/temp/squad-template.csv')` was being written to and downloaded simultaneously by `FacultyDashboardController::downloadTemplate`. This introduces a severe race condition for concurrent users who might overwrite, corrupt, or accidentally delete another user's temp file.
**Learning:** The application does not isolate temporary files by user ID or request UUID, making hardcoded disk writes highly dangerous in a concurrent environment.
**Prevention:** Use `response()->streamDownload()` instead of generating disk files. This bypasses disk I/O, prevents race conditions, and removes the need to secure a temporary storage directory.
## 2025-02-23 - Missing Rate Limiting on Password Reset Endpoint
**Vulnerability:** The `/forgot-password` POST route was missing rate limiting middleware, allowing unbounded requests.
**Learning:** Endpoints that trigger actions in external systems (such as sending emails) need rate limiting just like authentication attempts. Without it, an attacker can spam an arbitrary number of emails to an inbox or try to enumerate users by observing variations in response time.
**Prevention:** Ensure that all endpoints that trigger external systems or consume disproportionate compute are covered by the appropriate throttle middleware (like `throttle:6,1`).
