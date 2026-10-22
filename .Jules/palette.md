## 2025-02-28 - Missing ARIA Labels on Navigation Icons
**Learning:** Icon-only navigation buttons in AuthenticatedLayout (Mobile Menu, Notifications, User Menu) lacked ARIA labels, creating accessibility gaps for screen readers.
**Action:** Always ensure any icon-only button uses an `aria-label` describing its specific functionality.

## 2023-10-27 - Missing ARIA Labels on List/Grid Toggles and Data Table Actions
**Learning:** Toggle view buttons and individual row action buttons (Approve, Reject, Withdraw, Unregister) were using icons without accessible names (`aria-label`), making their purpose unclear to screen reader users navigating the table or grid.
**Action:** Consistently apply `aria-label` to all icon-only buttons in data visualizations and data tables, specifically including contextual information for row actions (e.g., "Unregister John Doe - Men's 100m").
