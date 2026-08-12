## 2024-05-24 - Missing ARIA Labels on Icon-only Action Buttons
**Learning:** Icon-only action buttons (e.g., buttons containing only a `<Trash2>` or `<Pencil>` icon) frequently appear in data tables across the application (like `Faculty/Dashboard.tsx` and `Sports/Index.tsx`) without accessible names, making their purpose unclear to screen reader users.
**Action:** When working on UI pages with lists or tables of items, actively check for inline action buttons and ensure they have descriptive `aria-label`s like "Remove squad member" or "Edit category".
