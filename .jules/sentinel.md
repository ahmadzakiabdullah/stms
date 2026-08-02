## 2024-05-24 - [Avoid Temporary File Race Conditions]
**Vulnerability:** A static temp file path `storage_path('app/temp/squad-template.csv')` was being written to and downloaded simultaneously by `FacultyDashboardController::downloadTemplate`. This introduces a severe race condition for concurrent users who might overwrite, corrupt, or accidentally delete another user's temp file.
**Learning:** The application does not isolate temporary files by user ID or request UUID, making hardcoded disk writes highly dangerous in a concurrent environment.
**Prevention:** Use `response()->streamDownload()` instead of generating disk files. This bypasses disk I/O, prevents race conditions, and removes the need to secure a temporary storage directory.
## 2025-02-09 - [Rate Limit Missing on Forgot Password Endpoint]
**Vulnerability:** Missing rate limiting on the POST `/forgot-password` endpoint in `routes/auth.php`.
**Learning:** This exposes the application to user enumeration and email spamming attacks, as an attacker could repeatedly submit email addresses.
**Prevention:** Always apply the `throttle` middleware to endpoints that trigger external systems like sending emails.
