## 2025-02-28 - XSS and DoS in svg-sanitize
Vulnerability: `enshrined/svg-sanitize` versions <=0.22.0 contained vulnerabilities that could lead to Cross-Site Scripting (XSS) via DTD Entity/HTML5 Named Character Reference Collisions, as well as multiple Denial of Service (DoS) vulnerabilities related to DTD attribute declarations and nested `<use>` tags. (CVE-2026-107380, CVE-2026-107381, CVE-2026-107379).
Learning: Keeping dependencies up to date is crucial to mitigating security risks.
Prevention: Consistently audit dependencies and run `composer audit --locked` in CI checks to identify vulnerabilities early.
