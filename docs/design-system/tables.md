# Tables

## Current Implementation

Data tables currently use **simple list views** with Laravel's built-in pagination. Rows are rendered as repeating card or table-row elements within a `<table>` or `<div>` layout, with pagination controls at the bottom.

```tsx
// Current pattern
<Table>
  <TableHeader>
    <TableRow>
      <TableHead>Name</TableHead>
      <TableHead>Status</TableHead>
    </TableRow>
  </TableHeader>
  <TableBody>
    {items.data.map(item => (
      <TableRow key={item.id}>
        <TableCell>{item.name}</TableCell>
        <TableCell>{item.status}</TableCell>
      </TableRow>
    ))}
  </TableBody>
</Table>
<Pagination paginator={items} />
```

`Pagination` here is the shared `resources/js/components/Pagination.tsx` (default export), which takes the Laravel paginator (`paginator`, or legacy `links`). The shadcn primitives in `resources/js/components/ui/pagination.tsx` are lower-level building blocks (used directly by the public Athletes page).

## TanStack Table (Available)

**TanStack Table** (`@tanstack/react-table` v8) is installed as a dependency but not imported by any page yet (12 November 2026); it is the designated upgrade path for complex data tables. It provides:

- Headless UI — full control over rendering
- Sorting, filtering, and pagination
- Column visibility toggling
- Row selection
- Server-side data fetching via Inertia

The shadcn/ui `<Table />` components are designed to work directly with TanStack Table, making the migration straightforward. The planned integration wraps TanStack Table's hooks with the same CSS-variable-based styling:

```tsx
const table = useReactTable({
  data,
  columns,
  getCoreRowModel: getCoreRowModel(),
});
```
