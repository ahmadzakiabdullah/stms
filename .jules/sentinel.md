## 2024-05-24 - [Avoid Temporary File Race Conditions]
**Vulnerability:** A static temp file path `storage_path('app/temp/squad-template.csv')` was being written to and downloaded simultaneously by `FacultyDashboardController::downloadTemplate`. This introduces a severe race condition for concurrent users who might overwrite, corrupt, or accidentally delete another user's temp file.
**Learning:** The application does not isolate temporary files by user ID or request UUID, making hardcoded disk writes highly dangerous in a concurrent environment.
**Prevention:** Use `response()->streamDownload()` instead of generating disk files. This bypasses disk I/O, prevents race conditions, and removes the need to secure a temporary storage directory.
## 2025-02-14 - Missing Rate Limiting on Password Reset Endpoints
**Vulnerability:** The POST `/forgot-password` endpoint lacked rate limiting middleware (`throttle`).
**Learning:** Endpoints that trigger sensitive external actions (like sending emails) are vulnerable to abuse (spam, enumeration, or cost induction) if they can be called infinitely. In Laravel, applying `throttle` to API and Auth routes is not always automatic for manually defined routes.
**Prevention:** Always verify that endpoints performing sensitive operations or triggering external systems (e.g., sending emails for password resets) are protected by a throttle middleware (like `throttle:6,1`) to prevent spamming and user enumeration.
