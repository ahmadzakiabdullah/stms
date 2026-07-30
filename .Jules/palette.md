## 2025-02-28 - Missing ARIA Labels on Navigation Icons
**Learning:** Icon-only navigation buttons in AuthenticatedLayout (Mobile Menu, Notifications, User Menu) lacked ARIA labels, creating accessibility gaps for screen readers.
**Action:** Always ensure any icon-only button uses an `aria-label` describing its specific functionality.
## 2026-07-30 - [ARIA Labels for Icon Buttons]
**Learning:** Icon-only buttons (like Eye, Pencil, Trash2 from lucide-react) frequently lack accessible names. This breaks screen-reader support since visual icons don't convey meaning.
**Action:** Always verify icon-only buttons include descriptive `aria-label`s.
