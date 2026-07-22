# Typography

## Font Family

STMS uses **Geist Variable** as the primary sans-serif typeface, served via the `@fontsource-variable/geist` npm package (imported in `resources/css/app.css`). **Figtree** is listed as a fallback.

The font stack is set in `tailwind.config.js`:

```js
fontFamily: {
  sans: ['Geist Variable', 'Figtree', ...defaultTheme.fontFamily.sans],
},
```

## Scale

Typography is styled entirely through **Tailwind utility classes** — no custom typography components are needed. Common patterns:

| Element | Tailwind Classes |
|---------|-----------------|
| Page title | `text-3xl font-bold tracking-tight` |
| Section heading | `text-2xl font-semibold` |
| Card title | `text-lg font-semibold` |
| Body text | `text-sm leading-7 text-muted-foreground` |
| Small / label | `text-xs font-medium` |
| Muted helper | `text-sm text-muted-foreground` |

## Line Height

Default line heights from Tailwind are used throughout:

- `leading-tight` (1.25) — headings
- `leading-normal` (1.5) — body text
- `leading-relaxed` (1.625) — prose content

## Responsive

Font sizes scale responsively using Tailwind's breakpoint prefixes where needed, though the default `text-sm`/`text-base` pairing works well across mobile and desktop.
