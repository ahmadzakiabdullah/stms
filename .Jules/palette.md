## 2025-02-28 - Missing ARIA Labels on Navigation Icons
**Learning:** Icon-only navigation buttons in AuthenticatedLayout (Mobile Menu, Notifications, User Menu) lacked ARIA labels, creating accessibility gaps for screen readers.
**Action:** Always ensure any icon-only button uses an `aria-label` describing its specific functionality.
## 2025-02-28 - Avoid Redundant aria-label on sr-only elements
**Learning:** Adding an `aria-label` to a button that already contains a `<span className="sr-only">...</span>` creates redundant announcements for screen readers, as the `aria-label` simply overrides the already accessible hidden text.
**Action:** Before adding an `aria-label`, check if the component already handles accessibility via visually hidden (`sr-only`) text elements.
