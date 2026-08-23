## 2024-05-24 - Missing ARIA Labels on Icon-only Buttons
**Learning:** Icon-only buttons without `aria-label` attributes are inaccessible to screen reader users because they cannot announce the purpose of the action. This is a common pattern for UI tools like view mode toggles or inline row actions.
**Action:** Always verify that every icon-only `<button>` contains a localized `aria-label` attribute (using `t()` when available) that clearly describes its function.
