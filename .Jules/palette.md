## 2025-02-28 - Missing ARIA Labels on Navigation Icons
**Learning:** Icon-only navigation buttons in AuthenticatedLayout (Mobile Menu, Notifications, User Menu) lacked ARIA labels, creating accessibility gaps for screen readers.
**Action:** Always ensure any icon-only button uses an `aria-label` describing its specific functionality.
## 2025-02-28 - Consistent Mobile Menu Controls
**Learning:** The mobile menu toggle button on the Welcome page used a raw `<button>` without Shadcn UI styling or localized ARIA labels, creating visual inconsistencies and a11y gaps.
**Action:** Always replace raw native buttons with the design system's `<Button>` component (e.g., `variant="outline" size="icon"`) and ensure `aria-label` attributes are correctly localized using `t()`.
