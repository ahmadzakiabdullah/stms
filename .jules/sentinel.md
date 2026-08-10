## 2024-05-24 - [Avoid Temporary File Race Conditions]
**Vulnerability:** A static temp file path `storage_path('app/temp/squad-template.csv')` was being written to and downloaded simultaneously by `FacultyDashboardController::downloadTemplate`. This introduces a severe race condition for concurrent users who might overwrite, corrupt, or accidentally delete another user's temp file.
**Learning:** The application does not isolate temporary files by user ID or request UUID, making hardcoded disk writes highly dangerous in a concurrent environment.
**Prevention:** Use `response()->streamDownload()` instead of generating disk files. This bypasses disk I/O, prevents race conditions, and removes the need to secure a temporary storage directory.

## 2024-05-18 - Missing Rate Limiting on Sensitive Auth Routes
**Vulnerability:** The `/register`, `/forgot-password`, and `/reset-password` POST endpoints in `routes/auth.php` lacked rate-limiting middleware, exposing the application to brute-force attacks, credential stuffing, and email spam.
**Learning:** Default Breeze/Laravel scaffolding doesn't always apply the `throttle` middleware to all sensitive authentication endpoints by default (or it was inadvertently removed).
**Prevention:** Always verify that endpoints performing sensitive operations or triggering external systems (e.g., sending emails) are protected by a throttle middleware (like `throttle:6,1`) to prevent abuse.
