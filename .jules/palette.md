## 2025-02-28 - Improper Link/Button Nesting in Pagination
**Learning:** Nesting a Radix UI/shadcn `Button` inside an Inertia.js `Link` leads to invalid HTML and accessibility issues because screen readers get confused by multiple interactive roles.
**Action:** When combining `Link` components with UI button components, always apply the `asChild` prop to the `Button` and nest the `Link` inside it (e.g., `<Button asChild><Link href="...">...</Link></Button>`).
