## 2026-08-07 - Ensure package-lockfiles aren't generated accidentally
**Learning:** During test runs which require `pnpm install`, new lockfiles like `pnpm-lock.yaml` might be generated if not careful. Including them in micro-UX PRs violates the "Keep changes under 50 lines" boundary constraint heavily.
**Action:** Always make sure `git diff --name-only` is clean from lockfiles or generated files before committing UX changes.
