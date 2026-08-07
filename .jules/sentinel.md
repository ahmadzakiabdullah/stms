## 2024-05-24 - [Avoid Temporary File Race Conditions]
**Vulnerability:** A static temp file path `storage_path('app/temp/squad-template.csv')` was being written to and downloaded simultaneously by `FacultyDashboardController::downloadTemplate`. This introduces a severe race condition for concurrent users who might overwrite, corrupt, or accidentally delete another user's temp file.
**Learning:** The application does not isolate temporary files by user ID or request UUID, making hardcoded disk writes highly dangerous in a concurrent environment.
**Prevention:** Use `response()->streamDownload()` instead of generating disk files. This bypasses disk I/O, prevents race conditions, and removes the need to secure a temporary storage directory.
## 2025-02-23 - Handle npm audit failures in CI
**Vulnerability:** Dependencies with known high vulnerabilities causing CI to fail via `npm audit --audit-level=high` (e.g. nanoid GHSA-2v37-7h3g-55p8).
**Learning:** The CI pipeline enforces `npm audit` which may fail due to dependency updates.
**Prevention:** Regularly run `npm audit` and `npm audit fix` to bump vulnerable dependencies in `package-lock.json` and ensure CI passes.
