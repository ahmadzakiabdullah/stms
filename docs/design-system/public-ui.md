# Public UI/UX Specification

## Scope

Dokumen ini ialah rujukan semasa untuk public portal STMS. Public portal ialah pengalaman discoverability dan maklumat pertandingan; ia berbeza daripada authenticated operations workspace.

## Canonical public journeys

- Homepage → Schedule & Results → filter/search → fixture or result information.
- Homepage → Athletes & Teams → search/filter → athlete or roster profile.
- Homepage → Competition → Sports, Faculties or Venues.
- Homepage → Information → News, Downloads, FAQ or About.
- Homepage/Footer → Contact → official address, email, phone and social links.

## Navigation

Primary public navigation:

- Home
- Schedule & Results
- Athletes & Teams
- Competition: Sports, Faculties, Venues
- Information: News, Downloads, FAQ, About
- Contact
- Login

`/schedule` ialah canonical public schedule/results route. `/matches`, `/results` dan `/live` ialah legacy redirects.

## Component policy

Public components are composition layers over local shadcn/ui primitives and Tailwind utilities. Prefer:

- `Button` for actions and links.
- `Input` and `Select` for search and filters.
- `Tabs` for schedule/result views.
- `Sheet` for mobile schedule filters and navigation.
- `Badge` for match status and compact metadata.
- `Card`, `Skeleton`, `Alert` and shared public empty/error/loading states for content states.

Do not add a UI framework, CSS module, styled-components or new custom stylesheet. Existing tenant theme values must remain controlled and contrast-checked.

## Required states

Public data-driven pages must provide meaningful loading, empty and error states. Schedule and athlete pages expose optional error handling and stale-data notices. Error messages must not replace backend authorization or validation.

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
