# TMP Housekeeping Audit (Non-Destructive)

> **Historical housekeeping snapshot.** This document only records the non-destructive `tmp/` review on 8 August; it is not a current full-project or release audit. See the [17 August audit](2026-08-17-full-project-and-production-audit.md).

Date: 2026-08-08

## Scope

- Reviewed `tmp/` contents only.
- No files were deleted or moved.

## Inventory Summary

- Total files: 9
- Total size: 0.24 MB
- Files older than 30 days: 0
- Files older than 90 days: 0

## Notable Artifacts

1. `tmp/pdfs/pengesahan-faix-1.png`
   - Size: 232.5 KB
   - Last modified: 2026-08-04
   - Note: Main disk contributor in `tmp/`.

2. `tmp/hardening-drafts-invalid/*.php` (8 files)
   - Combined size: approximately 12.1 KB
   - Last modified: 2026-08-07
   - Note: Appears to be draft/invalid code artifacts from hardening work.

## Risk Assessment

- Current disk risk: low (very small footprint).
- Operational clutter risk: medium (draft files can confuse future reviews if kept indefinitely).

## Recommended Next Actions

1. Keep all current `tmp/` files for now because they are recent.
2. Apply an age-based cleanup rule (manual or scripted): remove `tmp/` files older than 30 days unless explicitly archived.
3. For draft code artifacts, either:
   - move to a dated archive note under `docs/audits/`, or
   - delete once superseded and no longer referenced.

## Suggested Monthly Check Command

```powershell
Get-ChildItem tmp -Recurse -File |
  Where-Object { (Get-Date) - $_.LastWriteTime -gt [TimeSpan]::FromDays(30) } |
  Select-Object FullName, LastWriteTime, Length
```
