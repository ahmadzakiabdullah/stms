## 2024-05-24 - [Avoid Temporary File Race Conditions]
**Vulnerability:** A static temp file path `storage_path('app/temp/squad-template.csv')` was being written to and downloaded simultaneously by `FacultyDashboardController::downloadTemplate`. This introduces a severe race condition for concurrent users who might overwrite, corrupt, or accidentally delete another user's temp file.
**Learning:** The application does not isolate temporary files by user ID or request UUID, making hardcoded disk writes highly dangerous in a concurrent environment.
**Prevention:** Use `response()->streamDownload()` instead of generating disk files. This bypasses disk I/O, prevents race conditions, and removes the need to secure a temporary storage directory.
## 2024-05-24 - [Missing Rate Limiting on Password Reset Route]
**Vulnerability:** The `forgot-password` POST route was missing rate limiting, making it vulnerable to email spamming and user enumeration attacks.
**Learning:** Security middleware needs to be explicitly added to authentication routes to protect sensitive endpoints from abuse.
**Prevention:** Ensure that all endpoints handling sensitive operations, such as sending emails for password resets or sending OTPs, are protected by a throttle middleware (like `throttle:6,1`) to prevent spamming and enumeration attacks.
