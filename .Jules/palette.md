## 2025-02-28 - Missing ARIA Labels on Navigation Icons
**Learning:** Icon-only navigation buttons in AuthenticatedLayout (Mobile Menu, Notifications, User Menu) lacked ARIA labels, creating accessibility gaps for screen readers.
**Action:** Always ensure any icon-only button uses an `aria-label` describing its specific functionality.
## 2023-10-28 - Nested interactive elements in pagination
**Learning:** Using `Link` inside a `Button` created invalid HTML by nesting interactive elements (an anchor inside a button), preventing proper keyboard navigation and confusing screen readers. Additionally, disabled links lacked appropriate visual and interactive restrictions beyond styling.
**Action:** Always use the `asChild` prop on Radix/shadcn `Button` elements when wrapping routing components like `Link`. For visually disabled `Link` elements, explicitly apply `aria-disabled={true}` and `tabIndex={-1}` instead of just relying on pointer-events and opacity, since anchor tags do not natively support the `disabled` attribute.
