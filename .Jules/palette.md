## 2025-02-28 - Missing ARIA Labels on Navigation Icons
**Learning:** Icon-only navigation buttons in AuthenticatedLayout (Mobile Menu, Notifications, User Menu) lacked ARIA labels, creating accessibility gaps for screen readers.
**Action:** Always ensure any icon-only button uses an `aria-label` describing its specific functionality.

## 2024-05-18 - Icon-Only Button Accessibility Pattern
**Learning:** Found several native `<button>` tags acting as icon-only actions (like Grid/Table toggles and approve/reject actions) without accessible names. Replacing them directly with Shadcn `<Button>` was sometimes inappropriate due to highly specific dynamic Tailwind classes for active states.
**Action:** When adding accessible names to custom-styled icon buttons, retain the native `<button>` tag and add `aria-label={t('Action Name')}` rather than forcing a standard component that breaks styling.
