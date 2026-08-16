## 2026-08-16 - Fix Nested Interactive Elements with Radix UI and Inertia.js
**Learning:** Combining Inertia.js `<Link>` components with Radix UI/shadcn components (like `<Button>`) by wrapping the button inside the link produces invalid HTML (nested interactive elements) which degrades screen reader accessibility.
**Action:** Use the `asChild` prop on the Radix UI component (e.g., `<Button asChild><Link href="...">...</Link></Button>`) to pass rendering responsibility to the Inertia link, preventing invalid HTML and ensuring proper screen reader accessibility.
