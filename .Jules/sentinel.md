## 2026-11-05 - Path Traversal in Logo Uploads
**Vulnerability:** Path traversal in `UpdateParticipantRequest` where existing logo paths could contain `../` sequences, bypassing the `starts_with:logos/` validation rule.
**Learning:** Laravel's `starts_with` validation rule only checks the beginning of a string and does not normalize paths or prevent directory traversal sequences later in the string (e.g., `logos/../../etc/passwd`).
**Prevention:** Always combine prefix/directory validation with a specific check against traversal sequences (e.g., using `not_regex:/\.\./` in Laravel FormRequests) when handling user-provided file paths.
