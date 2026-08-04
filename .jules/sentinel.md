## 2024-05-24 - [Avoid Temporary File Race Conditions]
**Vulnerability:** A static temp file path `storage_path('app/temp/squad-template.csv')` was being written to and downloaded simultaneously by `FacultyDashboardController::downloadTemplate`. This introduces a severe race condition for concurrent users who might overwrite, corrupt, or accidentally delete another user's temp file.
**Learning:** The application does not isolate temporary files by user ID or request UUID, making hardcoded disk writes highly dangerous in a concurrent environment.
**Prevention:** Use `response()->streamDownload()` instead of generating disk files. This bypasses disk I/O, prevents race conditions, and removes the need to secure a temporary storage directory.
## 2024-08-04 - [Missing Rate Limiting on External System Triggers]
**Vulnerability:** The `forgot-password` POST route triggered an external system (sending emails) without rate limiting.
**Learning:** Endpoints triggering external systems must have rate limiting to prevent abuse like spamming and user enumeration.
**Prevention:** Always ensure `throttle` middleware is applied to endpoints performing sensitive operations or triggering external systems (like `throttle:6,1` for emails).
