## 2025-02-28 - Missing ARIA Labels on Navigation Icons
**Learning:** Icon-only navigation buttons in AuthenticatedLayout (Mobile Menu, Notifications, User Menu) lacked ARIA labels, creating accessibility gaps for screen readers.
**Action:** Always ensure any icon-only button uses an `aria-label` describing its specific functionality.
## 2026-09-13 - Missing ARIA Labels on Event Participants Icons
**Learning:** Several action buttons in the Event Participants index, such as Approve, Reject, Withdraw, and Unregister, as well as View mode buttons, used icon-only representations with a `title` attribute but lacked an `aria-label`. Additionally, many titles lacked localization via `t()`.
**Action:** Always provide localized `aria-label` attributes to icon-only buttons in React interfaces for optimal screen reader accessibility.
