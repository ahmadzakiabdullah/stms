# Public UI/UX Specification

## Scope

Dokumen ini ialah rujukan semasa untuk public portal STMS. Public portal ialah pengalaman discoverability dan maklumat pertandingan; ia berbeza daripada authenticated operations workspace.

## Canonical public journeys

- Homepage → Schedule & Results → filter/search → fixture or result information.
- Homepage → Athletes & Teams → search/filter → athlete or roster profile.
- Homepage → Competition → Sports, Faculties or Venues.
- Homepage → Information → General Information, committees, game chairpersons or important dates.
- Homepage/Footer → Contact → official address, email, phone and social links.

## Navigation

Primary public navigation:

- Home
- Schedule & Results
- Athletes & Teams
- Competition: Sports, Faculties, Venues
- Information: General Information, Jawatankuasa Induk, Jawatankuasa Pelaksana, Pengerusi Permainan, Tarikh Penting
- Contact
- Login

`/schedule` ialah canonical public schedule/results route. `/matches`, `/results` dan `/live` ialah legacy redirects.

## Component policy

Public components are composition layers over local shadcn/ui primitives and Tailwind utilities. Prefer:

- `Button` for actions and links.
- `Input` and `Select` for search and filters.
- `Tabs` for schedule/result views.

Public content cards should use the shared `public-card` class so Venue, Sports, Faculty, Athlete, Committee, Contact and Fixture cards retain the same rounded 3xl surface, soft shadow, hover lift, border highlight and decorative accent.

All public UI labels and supplied information pages should support the shared `en` and `ms` locales. Official personal names, sport names and venue names remain unchanged unless an approved localized label is available.
- `Sheet` for mobile schedule filters and navigation.
- `Badge` for match status and compact metadata.
- `Card`, `Skeleton`, `Alert` and shared public empty/error/loading states for content states.

Public filter/search interactions use the shared `Input`, `Select` and `Button` primitives. Public tabs and pagination use the shared `Tabs` and `Pagination` wrappers, while FAQ and roster disclosures use `Accordion`; page-specific styling is limited to class composition and tenant theme tokens.

Do not add a UI framework, CSS module, styled-components or new custom stylesheet. Existing tenant theme values must remain controlled and contrast-checked.

## Required states

Public data-driven pages must provide meaningful loading, empty, error and stale-data states. The shared `PublicLayout` announces Inertia navigation loading and exposes `aria-busy` on the public content root; pages use the shared empty/error/loading/stale components for content and refresh states. Retry actions must call an Inertia reload when the page exposes an error.

Permission state is intentionally not rendered as an in-page public state: these routes are anonymous by design. Route-level authorization remains enforced by the backend; a denied or invalid public URL is handled by the normal HTTP 403/404 response and is never hidden behind an empty state.

## Responsive and accessibility baseline

- Minimum supported width: 320px.
- Required checks: 320, 375, 390, 768, 1024, 1280 and 1440px.
- Interactive targets should be at least 44px on mobile.
- Every public page provides a keyboard skip link through `PublicLayout`.
- Status is communicated through text and structure, not color alone.
- Keyboard focus, reduced motion, labels, live regions and semantic headings must be preserved.

## SEO and performance baseline

- Use page-specific titles and canonical URLs for indexable public routes.
- Preserve sitemap and self-hosted fonts.
- Keep server-side pagination for growing directories.
- Avoid blocking critical content with weather or below-fold sections.
- Run `npm run build`, `npm run build:budget`, typecheck and public Playwright smoke tests before release.

## Current verification evidence

- TypeScript check: passed in the local Windows workspace.
- Vite production build: passed in the local Windows workspace.
- Bundle budget: passed with JS ≤ 400,000 bytes and CSS ≤ 120,000 bytes.
- Public home/contact keyboard and accessibility smoke: passed on desktop and mobile runs.
- Public horizontal-overflow and locale-switching test: passed on desktop and mobile runs.

## Open work

- Complete route-by-route metadata/Open Graph audit.
- Measure LCP, INP and CLS after deployment.
- Complete full public axe/keyboard audit for schedule filters, dropdowns, pagination and roster disclosure.
- Review and consolidate historical UI/UX documents without removing business or security requirements.
