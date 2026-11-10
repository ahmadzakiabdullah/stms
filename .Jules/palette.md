## 2025-02-28 - Missing ARIA Labels on Navigation Icons
**Learning:** Icon-only navigation buttons in AuthenticatedLayout (Mobile Menu, Notifications, User Menu) lacked ARIA labels, creating accessibility gaps for screen readers.
**Action:** Always ensure any icon-only button uses an `aria-label` describing its specific functionality.
## 2025-02-28 - Nested interactive elements in Pagination
**Learning:** The Pagination component nested Inertia `<Link>` tags inside shadcn `<Button>` tags, resulting in invalid HTML (interactive elements nested) and poor screen reader experience for disabled states.
**Action:** Always use the `asChild` prop on shadcn `Button` elements when wrapping links (`<Button asChild><Link>...</Link></Button>`). Furthermore, handle visually disabled link states by explicitly applying `aria-disabled={true}` and `tabIndex={-1}` to the inner anchor element.
