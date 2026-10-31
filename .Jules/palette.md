## 2025-02-28 - Missing ARIA Labels on Navigation Icons
**Learning:** Icon-only navigation buttons in AuthenticatedLayout (Mobile Menu, Notifications, User Menu) lacked ARIA labels, creating accessibility gaps for screen readers.
**Action:** Always ensure any icon-only button uses an `aria-label` describing its specific functionality.

## 2025-03-01 - Missing ARIA Labels on Event Participants Icons
**Learning:** Icon-only action buttons (Grid view, Table view, Approve, Reject, Withdraw, Unregister) in the `EventParticipants/Index` table lacked ARIA labels, creating accessibility gaps for screen readers. Some text buttons like "Cancel" or "Clear selection" also benefit from explicit `aria-label`s.
**Action:** Always ensure any icon-only button (or button acting mostly via icon) uses an `aria-label` describing its specific functionality to support accessibility.
