## 2025-02-28 - XSS and DoS in svg-sanitize
Vulnerability: `enshrined/svg-sanitize` versions <=0.22.0 contained vulnerabilities that could lead to Cross-Site Scripting (XSS) via DTD Entity/HTML5 Named Character Reference Collisions, as well as multiple Denial of Service (DoS) vulnerabilities related to DTD attribute declarations and nested `<use>` tags. (CVE-2026-107380, CVE-2026-107381, CVE-2026-107379).
Learning: Keeping dependencies up to date is crucial to mitigating security risks.
Prevention: Consistently audit dependencies and run `composer audit --locked` in CI checks to identify vulnerabilities early.

## 2025-02-28 - Multiple Vulnerabilities in Vite and Zod
Vulnerability: `vite` versions < 8.1.6 contained vulnerabilities that could lead to Cross-Origin Script Execution, Arbitrary Command Execution via Editor CLI flags on Windows, and serving arbitrary files outside `server.fs.allow` on non-Windows environments. `zod` versions < 4.6.6 could exhaust memory while validating oversized constrained collections (DoS).
Learning: Development dependencies and core validation libraries can be attack vectors (e.g. DoS during validation, or issues in development tools that get exposed).
Prevention: Keep `vite`, `zod`, and other key npm dependencies up to date, and use `npm audit` in CI pipelines to block merges that introduce known vulnerabilities.
