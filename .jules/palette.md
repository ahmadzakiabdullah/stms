## 2024-08-30 - [Button asChild for Links]
**Learning:** When using Radix UI / Shadcn `<Button>` components to wrap Inertia `<Link>` components, wrapping a `<Button>` directly inside a `<Link>` results in invalid nested interactive HTML elements and screen reader issues, especially when implementing disabled states for links.
**Action:** Always use the `<Button asChild>` prop and place the `<Link>` inside it. For visually disabled states, apply `aria-disabled={true}` and `tabIndex={-1}` to the inner `<Link>` explicitly since anchor tags don't support the `disabled` attribute natively.
