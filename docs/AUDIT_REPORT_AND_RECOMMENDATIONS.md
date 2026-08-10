# STMS - Full System Audit Report

## 1. Security & Multi-Tenancy
**Status:** Strong, but with minor areas for refinement.
- **Tenant Context:** The `BelongsToOrganization` global scope and the `TenantContext` service correctly handle tenant lifecycle isolation across HTTP and Queue requests. The system reliably fails closed if an unbound context attempts a tenant-scoped operation.
- **Authorization:** Granular Spatie role-based access control (RBAC) is well implemented. Policies consistently differentiate between `super-admin` (global view/modify) and `org-admin` (tenant-scoped view/modify) permissions.
- **Headers:** Content Security Policy (CSP) headers are well-defined and configured securely (using `CSP_REPORT_ONLY` depending on the environment).
- **Recommendation:** No critical vulnerabilities found. Maintain the rigorous use of `TenantContext` and Gate checks for any new domain models.

## 2. Code Quality & Architecture
**Status:** Excellent.
- **Patterns:** The codebase heavily utilizes the Service and Action patterns as described in `CLAUDE.md`. Controllers remain "thin" and business logic resides in Services (e.g. `EventService`, `SportCategoryService`).
- **Domain Modeling:** Consistent use of UUIDs as primary keys, foreign key constraints, and soft deletes.
- **Recommendation:** The Dashboard payload construction (in `DashboardController`) relies on large, complex closures with nested queries. Extracting these into a dedicated `DashboardService` (similar to how `FacultyDashboardService` is structured) would improve maintainability and controller slimness.

## 3. Performance & Database Queries
**Status:** Good, but queries can be optimized.
- **Eager Loading:** Generally, relations and counts are eager loaded (`with`, `withCount`) effectively across the system.
- **N+1 Avoidance:** In `FacultyDashboardService`, the system calculates `$reg->squadMembers->where(...)->count()` inside a loop. Since `squadMembers` is eager loaded, this is an in-memory collection operation. However, using Laravel's `->countBy(...)` could slightly improve processing speed on large datasets.
- **Indexes:** Security indexing (`database/migrations/2026_08_06_000001_add_security_indexes.php`) covers most critical high-traffic query paths (e.g. multi-tenant lookups).
- **Recommendation:** Refactor loop-based collection counting (`where()->count()`) to `countBy()` in dashboard services to reduce time complexity.

## 4. Frontend UX/UI & Accessibility
**Status:** Good, with one known accessibility violation.
- **Component Standard:** The application strictly follows the `shadcn/ui` integration.
- **Semantic HTML:** Links inside buttons correctly use the `<Button asChild><Link ... /></Button>` pattern, satisfying both visual UI guidelines and semantic accessibility limits.
- **Accessibility Violations:** E2E testing (Playwright axe-checks) discovered a color contrast violation on the `Error.tsx` / error reporting pages involving Shiki code block highlight lines. Specifically, a background color of `#ffccd3` (rose-200) conflicts with text color `#737373` and `#267f99` providing insufficient contrast.
- **Recommendation:** Adjust the color contrast for error page code highlighting (dark/light mode variants) to satisfy WCAG AA contrast ratio of 4.5:1.

## 5. Dependencies
**Status:** One vulnerability identified.
- **NPM Packages:** An audit revealed a high severity vulnerability in the `nanoid` dependency (`<3.3.17`).
- **Recommendation:** Run `npm audit fix` or selectively upgrade `nanoid` and review the resulting lockfile to ensure no unintended packages were modified.

## Summary of Actionable Recommendations:
1. **Fix NPM Vulnerability:** Address the high-severity `nanoid` vulnerability via `npm update` or `npm audit fix`.
2. **Improve Error Page Contrast:** Fix the Shiki syntax highlighting background colors (`bg-rose-200`, etc) on the Error component to ensure proper contrast.
3. **Refactor DashboardController:** Move the inline data aggregation logic into a dedicated Service.
4. **Optimize Collection Counts:** Refactor repetitive `where()->count()` collection methods to use `countBy()`.

## 6. Testing
**Status:** Tests pass with one minor exception.
- **Failing test:** `tests/Feature/SecurityHeadersTest.php` contains an assertion asserting that `unsafe-inline` should not be present in `script-src` and `style-src` on production CSPs. The `SecurityHeaders.php` middleware actually implements `script-src 'self' 'unsafe-inline';` when `CSP_REPORT_ONLY` is true (which is the default in the test setup).
- **Recommendation:** Fix the CSP configuration in `app/Http/Middleware/SecurityHeaders.php` to remove `'unsafe-inline'` where possible, or adjust the test expectations if `'unsafe-inline'` is intentionally required by the frontend framework during report-only mode.
