## 2024-05-24 - [Avoid Temporary File Race Conditions]
**Vulnerability:** A static temp file path `storage_path('app/temp/squad-template.csv')` was being written to and downloaded simultaneously by `FacultyDashboardController::downloadTemplate`. This introduces a severe race condition for concurrent users who might overwrite, corrupt, or accidentally delete another user's temp file.
**Learning:** The application does not isolate temporary files by user ID or request UUID, making hardcoded disk writes highly dangerous in a concurrent environment.
**Prevention:** Use `response()->streamDownload()` instead of generating disk files. This bypasses disk I/O, prevents race conditions, and removes the need to secure a temporary storage directory.

## 2026-08-02 - [Missing Rate Limit on Sensitive Auth Endpoints]
**Vulnerability:** The password reset and forgot password endpoints lacked rate limit throttling, making them susceptible to brute-force token guessing and email enumeration/spamming attacks.
**Learning:** In Laravel, we must manually apply the `throttle` middleware to auth routes. Only `verify-email` was previously protected.
**Prevention:** Apply `throttle:6,1` (or similar) middleware on all `POST` endpoints related to authentication, password recovery, and email sending.
