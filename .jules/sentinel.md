## 2024-05-24 - [Avoid Temporary File Race Conditions]
**Vulnerability:** A static temp file path `storage_path('app/temp/squad-template.csv')` was being written to and downloaded simultaneously by `FacultyDashboardController::downloadTemplate`. This introduces a severe race condition for concurrent users who might overwrite, corrupt, or accidentally delete another user's temp file.
**Learning:** The application does not isolate temporary files by user ID or request UUID, making hardcoded disk writes highly dangerous in a concurrent environment.
**Prevention:** Use `response()->streamDownload()` instead of generating disk files. This bypasses disk I/O, prevents race conditions, and removes the need to secure a temporary storage directory.

## 2025-08-08 - [Missing Rate Limiting on Password Reset Endpoint]
**Vulnerability:** The password reset request endpoint (`/forgot-password` POST route) was missing rate limiting, allowing an attacker to spam emails and potentially enumerate valid users.
**Learning:** Sensitive endpoints that trigger external actions (like sending emails) must always have explicit rate limiters attached in the route definitions.
**Prevention:** Always add a `throttle` middleware (e.g., `->middleware('throttle:6,1')`) to endpoints handling password resets, email sending, or other sensitive external triggers.
