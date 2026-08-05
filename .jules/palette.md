
## 2024-05-15 - ARIA Labels for Table Checkboxes
**Learning:** Table checkboxes used for batch actions (e.g. "Select All" and per-row selection) without associated `<label>` text are inaccessible to screen readers.
**Action:** Always add `aria-label="Select all [items]"` and `aria-label={\`Select \${item.name}\`}` to unlabelled checkboxes in data tables.
