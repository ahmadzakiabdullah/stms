## 2024-05-24 - [Avoid Temporary File Race Conditions]
**Vulnerability:** A static temp file path `storage_path('app/temp/squad-template.csv')` was being written to and downloaded simultaneously by `FacultyDashboardController::downloadTemplate`. This introduces a severe race condition for concurrent users who might overwrite, corrupt, or accidentally delete another user's temp file.
**Learning:** The application does not isolate temporary files by user ID or request UUID, making hardcoded disk writes highly dangerous in a concurrent environment.
**Prevention:** Use `response()->streamDownload()` instead of generating disk files. This bypasses disk I/O, prevents race conditions, and removes the need to secure a temporary storage directory.

## 2024-05-25 - [Missing Rate Limiting on Password Reset Endpoint]
**Vulnerability:** The `forgot-password` endpoint in `routes/auth.php` lacked rate limiting, allowing an attacker to spam email addresses with reset requests (DoS against email provider and user annoyance) and perform user enumeration via timing attacks or brute-force requests.
**Learning:** Default Breeze/Fortify authentication scaffolding might not always include rate limiting on all email notification endpoints depending on the Laravel version and initial setup.
**Prevention:** Always verify that endpoints triggering external systems (like sending emails or SMS) or performing sensitive operations are protected by a throttle middleware (like `throttle:6,1`).
