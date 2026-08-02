## 2024-05-24 - [Avoid Temporary File Race Conditions]
**Vulnerability:** A static temp file path `storage_path('app/temp/squad-template.csv')` was being written to and downloaded simultaneously by `FacultyDashboardController::downloadTemplate`. This introduces a severe race condition for concurrent users who might overwrite, corrupt, or accidentally delete another user's temp file.
**Learning:** The application does not isolate temporary files by user ID or request UUID, making hardcoded disk writes highly dangerous in a concurrent environment.
**Prevention:** Use `response()->streamDownload()` instead of generating disk files. This bypasses disk I/O, prevents race conditions, and removes the need to secure a temporary storage directory.
## 2024-05-24 - [Add Rate Limiting to Password Reset Endpoint]
**Vulnerability:** The `/forgot-password` endpoint lacked rate limiting, potentially allowing an attacker to spam email addresses or attempt user enumeration via brute force requests.
**Learning:** Default Breeze routes usually need specific security reviews. Sometimes routes created early in a project don't have rate limiting applied.
**Prevention:** Always verify that endpoints performing sensitive operations or triggering external systems (like sending emails) are protected by a throttle middleware.
