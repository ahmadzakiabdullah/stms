
## 2024-05-23 - Prevent Path Traversal in Form Requests
**Vulnerability:** A path traversal vulnerability existed in the `UpdateParticipantRequest` where user input could bypass the `starts_with` validation by using `..` sequences, potentially leading to arbitrary file deletion or access when updating logos.
**Learning:** In Laravel, `starts_with` does not prevent path traversal if the user supplies `logos/../../etc/passwd`. It is insufficient for paths interacting with the filesystem.
**Prevention:** Always use the `not_regex:/\.\./` rule in Form Requests for any file path inputs to completely block directory traversal sequences, especially since Laravel has no native `doesnt_contain` rule.
