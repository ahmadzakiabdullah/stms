## 2025-02-28 - Missing ARIA Labels on Navigation Icons
**Learning:** Icon-only navigation buttons in AuthenticatedLayout (Mobile Menu, Notifications, User Menu) lacked ARIA labels, creating accessibility gaps for screen readers.
**Action:** Always ensure any icon-only button uses an `aria-label` describing its specific functionality.
## 2025-02-28 - Missing ARIA Labels on Icon-only CRUD Buttons
**Learning:** Icon-only CRUD buttons (like Edit, Delete Category, and Delete Document) in resource management pages (`Sports/Index.tsx`) lacked `aria-label`s, presenting an accessibility barrier for screen readers.
**Action:** When implementing or refactoring tables with icon-only action buttons, always explicitly provide `aria-label`s using the localization function `t()`.
