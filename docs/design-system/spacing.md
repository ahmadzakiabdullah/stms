# Spacing

## Tailwind Spacing Scale

STMS uses the default Tailwind CSS spacing scale, where `1` unit = `0.25rem` (4px). The full scale is available:

| Class | Value | Rem |
|-------|-------|-----|
| `p-0` | 0 | 0 |
| `p-1` | 4px | 0.25rem |
| `p-2` | 8px | 0.5rem |
| `p-3` | 12px | 0.75rem |
| `p-4` | 16px | 1rem |
| `p-5` | 20px | 1.25rem |
| `p-6` | 24px | 1.5rem |
| `p-8` | 32px | 2rem |
| `p-10` | 40px | 2.5rem |
| `p-12` | 48px | 3rem |
| `p-16` | 64px | 4rem |

## Common Spacing Patterns

- **Page padding**: `p-6` or `p-8` on the main content wrapper
- **Card padding**: `p-6` on card components
- **Stack spacing**: `space-y-4` or `space-y-6` between stacked elements
- **Form fields**: `space-y-2` between label + input, `gap-4` in grid layouts
- **Section separation**: `mb-8` or `mb-10` between sections

## shadcn/ui Defaults

shadcn/ui components follow consistent spacing:

| Component | Padding |
|-----------|---------|
| Button | `px-4 py-2` (default), `px-3 py-1` (sm), `px-8 py-3` (lg) |
| Card | `p-6` with `gap-2` between header/content/footer |
| Dialog | `p-6` |
| Input | `px-3 py-2` |

## Grids

Use Tailwind's grid utilities for layout grids:

```html
<div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
```
