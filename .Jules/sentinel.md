## 2026-10-22 - Fix Composer Audit Failures
**Vulnerability:** Vulnerable packages (`enshrined/svg-sanitize`, `league/commonmark`, and `symfony/http-foundation`) were causing the `dependency-audit` CI check to fail due to multiple security advisories (e.g., CVE-2026-107380, CVE-2026-108082).
**Learning:** When addressing `composer audit` failures, it is essential to update the packages in `composer.json` without introducing incompatible major versions (e.g., maintaining `symfony/http-foundation:"^7.0"` instead of upgrading to v8 if it breaks compatibility).
**Prevention:** Regularly run `composer audit --locked` locally to identify and resolve vulnerable dependencies before committing and submitting PRs.
## 2026-10-22 - Fix NPM Audit Failures
**Vulnerability:** Vulnerable packages (e.g., `vite`, `shell-quote`, `braces`) were causing the `dependency-audit` CI check to fail due to multiple security advisories.
**Learning:** When addressing `npm audit` or `pnpm audit` failures, it is essential to update the packages in `package.json` without introducing incompatible major versions. In this project, `pnpm` is the strict package manager boundary ("Never do: Use npm or yarn (only pnpm)").
**Prevention:** Regularly run `pnpm audit` locally to identify and resolve vulnerable dependencies before committing and submitting PRs.
