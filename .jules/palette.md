## 2026-08-17 - Missing ARIA Labels on Icon-only Buttons
**Learning:** Found several icon-only buttons using Radix/Lucide icons (like `Eye`, `Pencil`, `Trash2`) across Inertia table views (e.g., `Participants/Index`, `Sports/Index`) that lacked `aria-label` attributes. This pattern is common in repeated row actions.
**Action:** Always verify icon-only buttons within table rows or lists for missing `aria-label`s and ensure they use the `t()` translation function for localization context.
