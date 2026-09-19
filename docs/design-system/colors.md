# Colors

## Approach

Colors are defined using **CSS custom properties** in `resources/css/app.css`. Tailwind CSS references these variables and dark-mode variants, tetapi audit tidak menemui theme-toggle pengguna. Public portal juga menerima enam warna tema daripada setting tenant; nilai tersebut perlu disahkan sebelum digunakan sebagai CSS.

## Theme Variables

### Light Mode (default)

```css
:root {
  --background: oklch(1 0 0);
  --foreground: oklch(0.145 0 0);
  --card: oklch(1 0 0);
  --card-foreground: oklch(0.145 0 0);
  --popover: oklch(1 0 0);
  --popover-foreground: oklch(0.145 0 0);
  --primary: oklch(0.205 0 0);
  --primary-foreground: oklch(0.985 0 0);
  --secondary: oklch(0.97 0 0);
  --secondary-foreground: oklch(0.205 0 0);
  --muted: oklch(0.97 0 0);
  --muted-foreground: oklch(0.5 0 0);
  --accent: oklch(0.97 0 0);
  --accent-foreground: oklch(0.205 0 0);
  --destructive: oklch(0.577 0.245 27.325);
  --border: oklch(0.922 0 0);
  --input: oklch(0.922 0 0);
  --ring: oklch(0.708 0 0);
  --radius: 0.625rem;
}
```

### Dark Mode

```css
.dark {
  --background: oklch(0.145 0 0);
  --foreground: oklch(0.985 0 0);
  --card: oklch(0.205 0 0);
  --card-foreground: oklch(0.985 0 0);
  --popover: oklch(0.205 0 0);
  --popover-foreground: oklch(0.985 0 0);
  --primary: oklch(0.922 0 0);
  --primary-foreground: oklch(0.205 0 0);
  --secondary: oklch(0.269 0 0);
  --secondary-foreground: oklch(0.985 0 0);
  --muted: oklch(0.269 0 0);
  --muted-foreground: oklch(0.708 0 0);
  --accent: oklch(0.269 0 0);
  --accent-foreground: oklch(0.985 0 0);
  --destructive: oklch(0.704 0.191 22.216);
  --border: oklch(1 0 0 / 10%);
  --input: oklch(1 0 0 / 15%);
  --ring: oklch(0.556 0 0);
}
```

## Usage in Tailwind

CSS variables use the OKLCH color space and are consumed via Tailwind's `var()` function:

```html
<div class="bg-background text-foreground">
<div class="bg-card text-card-foreground">
<div class="bg-muted text-muted-foreground">
```

## Color Categories

| Variable | Purpose |
|----------|---------|
| `background` / `foreground` | Page background and default text |
| `card` / `card-foreground` | Card/surface backgrounds |
| `popover` / `popover-foreground` | Dropdown/popover backgrounds |
| `muted` / `muted-foreground` | Subtle backgrounds, secondary text |
| `primary` / `primary-foreground` | Primary actions and accents |
| `secondary` / `secondary-foreground` | Secondary actions |
| `accent` / `accent-foreground` | Highlighted interactive states |
| `destructive` / `destructive-foreground` | Destructive actions |
| `border` | Dividers and borders |
| `input` | Form input borders |
| `ring` | Focus ring indicators |

## Public Portal Theme (tenant-configurable)

The public portal (`/`, `/sports`, `/schedule`, `/contact-us`, etc.) uses a separate six-colour token set injected from tenant settings. Audit 19 September 2026 verified every combination used in `resources/js/Pages/Public/**` against WCAG AA (4.5:1 for text, 3:1 for large text/UI):

| Token | Role | Example usage |
|---|---|---|
| `--public-dark` | Hero/sidebar background | `PublicPageHero`, contact aside |
| `--public-primary` | Primary actions & accents | Sport card CTA, filter focus rings |
| `--public-highlight` | CTA on dark surfaces | "View schedule" button in hero |
| `--public-accent` | Eyebrow labels on dark | Section eyebrows |
| `--public-dark-border` | Borders on light surfaces | Card borders |
| `--public-dark-faint` | Secondary text on light | Captions, meta text (AA ≥ 4.5:1 on white) |

Contrast rules enforced in code:

- `text-red-700` (not `text-red-600`) for red text on light backgrounds — applied to live strips and clear-filter hover states (audit fixed remaining `text-red-600` occurrences in `Schedule.tsx` and `Athletes.tsx`, 19 September 2026).
- Text on `--public-dark` surfaces uses `white`, `white/65`, `white/75`, or `--public-highlight` — all ≥ 4.5:1 against the dark gradient.
- Badge chips use `--public-primary` text on `--public-primary-soft` background; tenant themes must keep the soft variant light enough for AA.

## Dates and timezone (public pages)

All `Intl.DateTimeFormat`/`toLocaleDateString` calls in public pages (`Index`, `Schedule`, `Athletes`, `Athlete`) and the shared helpers `formatDate`/`formatDateTime` in `resources/js/lib/i18n.ts` explicitly set `timeZone: 'Asia/Kuala_Lumpur'`. This guarantees fixture times render in Malaysian time regardless of the visitor's browser timezone. Audited and applied 19 September 2026.
