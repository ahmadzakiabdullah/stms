## 2025-02-28 - Missing ARIA Labels on Navigation Icons
**Learning:** Icon-only navigation buttons in AuthenticatedLayout (Mobile Menu, Notifications, User Menu) lacked ARIA labels, creating accessibility gaps for screen readers.
**Action:** Always ensure any icon-only button uses an `aria-label` describing its specific functionality.

## 2026-10-03 - Missing ARIA Labels on Icon-Only Buttons
**Learning:** Found several icon-only buttons in the EventParticipants table that used `title` for tooltips but lacked `aria-label` for screen readers. Using `title` alone is insufficient for reliable accessibility across different assistive technologies.
**Action:** When adding `title` to icon-only interactive elements (like the Shadcn UI Button component with `size="icon"` or native buttons), ensure an identical `aria-label` is also provided, utilizing the `t()` translation function for localization.
