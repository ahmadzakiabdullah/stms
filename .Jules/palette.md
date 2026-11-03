## 2025-02-28 - Missing ARIA Labels on Navigation Icons
**Learning:** Icon-only navigation buttons in AuthenticatedLayout (Mobile Menu, Notifications, User Menu) lacked ARIA labels, creating accessibility gaps for screen readers.
**Action:** Always ensure any icon-only button uses an `aria-label` describing its specific functionality.
## 2025-03-04 - ARIA Labels missing on Event Participants index buttons
**Learning:** Icon-only buttons used for various actions (grid/table view, squad manage, approve, reject, withdraw, unregister) in `EventParticipants/Index.tsx` lacked `aria-label` attributes, creating a poor experience for screen reader users.
**Action:** When creating tables or lists with inline action buttons (especially those without text), always ensure they have an `aria-label` or visually hidden text to describe their function.
