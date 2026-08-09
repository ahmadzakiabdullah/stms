## 2024-05-24 - [Avoid Temporary File Race Conditions]
**Vulnerability:** A static temp file path `storage_path('app/temp/squad-template.csv')` was being written to and downloaded simultaneously by `FacultyDashboardController::downloadTemplate`. This introduces a severe race condition for concurrent users who might overwrite, corrupt, or accidentally delete another user's temp file.
**Learning:** The application does not isolate temporary files by user ID or request UUID, making hardcoded disk writes highly dangerous in a concurrent environment.
**Prevention:** Use `response()->streamDownload()` instead of generating disk files. This bypasses disk I/O, prevents race conditions, and removes the need to secure a temporary storage directory.

## 2026-08-09 - [Missing Rate Limiting on Password Reset Endpoint]
**Vulnerability:** The POST endpoint for forgot-password in `routes/auth.php` was missing rate limiting, making it vulnerable to email spamming, DoS, and potential user enumeration.
**Learning:** Endpoints that trigger external actions (like sending emails) are prime targets for abuse and must be rate-limited by default. While login requests had a dedicated request class with rate limiting (`LoginRequest`), standalone routes sending emails were overlooked.
**Prevention:** Always verify that endpoints performing sensitive operations or triggering external systems (e.g., sending emails for password resets) are protected by a throttle middleware (like `throttle:6,1`) to prevent spamming and user enumeration.
