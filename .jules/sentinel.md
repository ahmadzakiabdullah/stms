## 2024-05-24 - [Avoid Temporary File Race Conditions]
**Vulnerability:** A static temp file path `storage_path('app/temp/squad-template.csv')` was being written to and downloaded simultaneously by `FacultyDashboardController::downloadTemplate`. This introduces a severe race condition for concurrent users who might overwrite, corrupt, or accidentally delete another user's temp file.
**Learning:** The application does not isolate temporary files by user ID or request UUID, making hardcoded disk writes highly dangerous in a concurrent environment.
**Prevention:** Use `response()->streamDownload()` instead of generating disk files. This bypasses disk I/O, prevents race conditions, and removes the need to secure a temporary storage directory.

## 2026-08-03 - [Rate Limit Password Reset Endpoints]
**Vulnerability:** The `password.email` (forgot password) and `password.store` (reset password) endpoints lacked rate limiting, allowing potential attackers to spam users with emails or brute-force reset tokens.
**Learning:** In Laravel, sensitive auth endpoints should always be protected by rate limiters to prevent abuse. Since these endpoints were missing from the standard auth scaffolding's rate limiting, they were vulnerable.
**Prevention:** Always apply the `throttle` middleware (e.g., `throttle:6,1`) to endpoints that send emails or handle sensitive authentication state.
