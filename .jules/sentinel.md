## 2024-05-24 - [Avoid Temporary File Race Conditions]
**Vulnerability:** A static temp file path `storage_path('app/temp/squad-template.csv')` was being written to and downloaded simultaneously by `FacultyDashboardController::downloadTemplate`. This introduces a severe race condition for concurrent users who might overwrite, corrupt, or accidentally delete another user's temp file.
**Learning:** The application does not isolate temporary files by user ID or request UUID, making hardcoded disk writes highly dangerous in a concurrent environment.
**Prevention:** Use `response()->streamDownload()` instead of generating disk files. This bypasses disk I/O, prevents race conditions, and removes the need to secure a temporary storage directory.
## 2024-08-17 - Add rate limiting to forgot password endpoint
**Vulnerability:** The `POST /forgot-password` endpoint did not have rate limiting configured.
**Learning:** External systems and sensitive operations, such as triggering an email, should always be protected with rate limiting to prevent spam, email server overload, and enumeration attacks. While other verification endpoints used throttling, `forgot-password` missed it.
**Prevention:** Always verify that endpoints performing sensitive operations or triggering external systems (like sending emails for password resets) are protected by a throttle middleware (like `throttle:6,1`) to prevent spamming and user enumeration.
