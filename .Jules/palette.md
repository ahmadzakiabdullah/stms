## 2025-02-28 - Missing ARIA Labels on Navigation Icons
**Learning:** Icon-only navigation buttons in AuthenticatedLayout (Mobile Menu, Notifications, User Menu) lacked ARIA labels, creating accessibility gaps for screen readers.
**Action:** Always ensure any icon-only button uses an `aria-label` describing its specific functionality.

## 2026-09-24 - Fix Invalid Nested Elements in Pagination
**Learning:** Nesting a generic `<Button>` component inside an Inertia `<Link>` results in invalid HTML (nested interactive elements: `<button>` inside `<a>`), leading to accessibility and DOM issues. Radix UI/shadcn components typically support an `asChild` prop to solve this.
**Action:** Use the `asChild` prop on the UI component (e.g., `<Button asChild><Link>...</Link></Button>`) to merge the styling of the button onto the standard anchor tag, preventing invalid nesting. Furthermore, properly handle visually disabled states on anchor tags by explicitly applying `aria-disabled={true}` and `tabIndex={-1}` to the inner `<Link>`, since they lack a native `disabled` attribute.
