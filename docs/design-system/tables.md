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
<Pagination meta={items.meta} />
```

## TanStack Table (Available)

**TanStack Table** (`@tanstack/react-table` v8) is available as a dependency and the designated upgrade path for complex data tables. It provides:

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
