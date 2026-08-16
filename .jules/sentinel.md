## 2024-05-24 - [Avoid Temporary File Race Conditions]
**Vulnerability:** A static temp file path `storage_path('app/temp/squad-template.csv')` was being written to and downloaded simultaneously by `FacultyDashboardController::downloadTemplate`. This introduces a severe race condition for concurrent users who might overwrite, corrupt, or accidentally delete another user's temp file.
**Learning:** The application does not isolate temporary files by user ID or request UUID, making hardcoded disk writes highly dangerous in a concurrent environment.
**Prevention:** Use `response()->streamDownload()` instead of generating disk files. This bypasses disk I/O, prevents race conditions, and removes the need to secure a temporary storage directory.

## 2024-06-25 - [Rate Limit Authentication Endpoints]
**Vulnerability:** Core authentication POST endpoints (`/forgot-password`, `/reset-password`, `/register`) lacked rate limiting.
**Learning:** This exposes the application to email bombing (sending mass password reset links to victims) and user enumeration (brute-forcing emails to see if they are registered).
**Prevention:** Always verify that endpoints triggering external systems (e.g., sending emails) or handling unauthenticated user data are protected by a throttle middleware (`throttle:6,1`).
