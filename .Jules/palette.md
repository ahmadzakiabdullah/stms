## 2025-02-28 - Missing ARIA Labels on Navigation Icons
**Learning:** Icon-only navigation buttons in AuthenticatedLayout (Mobile Menu, Notifications, User Menu) lacked ARIA labels, creating accessibility gaps for screen readers.
**Action:** Always ensure any icon-only button uses an `aria-label` describing its specific functionality.
## 2026-10-22 - Missing ARIA Labels on Icon-only Action Buttons
**Learning:** Icon-only action buttons (e.g., Approve, Reject, Grid/Table view) in complex pages like `EventParticipants/Index.tsx` lack ARIA labels, presenting critical accessibility blockers for screen reader users despite having visual `title` attributes.
**Action:** Audit and enforce `aria-label` additions to all interactive icon-only components across major data tables and lists, ensuring translations (e.g., using `t()`) are utilized.
