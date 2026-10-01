# Public Page Inventory and UI/UX Audit

Audit date: 28 September 2026  
Scope: guest accessible website routes only. Admin, staff, authenticated user interfaces, backend workflows and APIs are excluded.

## Public page inventory

| Page / route | Template | Purpose and primary user | Primary / secondary action | Current UX finding | Priority |
|---|---|---|---|---|---|
| Home `/` | `Public/Index` | First-time visitors; explain the competition and surface current fixtures, results and medals | Open schedule / browse sports | Rich information and useful live data; shared decorative style is busier than an institutional portal needs | P1 |
| Schedule & results `/schedule` | `Public/Schedule` | Spectators checking fixtures and scores | Filter/search matches / print schedule | Search, filters, counts, list/calendar and empty states exist; dense controls need continued mobile/accessibility QA | P1 |
| Sports `/sports` | `Public/Directory` | Participants finding events and quotas | Browse sport / view venue directory | Responsive directory with catalogue icons; shares the global card and hero patterns | P2 |
| Faculties `/faculties` | `Public/Directory` | Participants identifying competing faculties | Browse faculty / open schedule | Reuses directory template and logo fallback | P2 |
| Venues `/venues` | `Public/Directory` | Visitors locating competition venues | Open venue map / view schedule | Venue maps and accessible map dialog exist; shares global visual patterns | P2 |
| Athletes `/athletes` | `Public/Athletes` | Spectators browsing confirmed entrants | Search/browse roster / open athlete | Directory and profile links exist; confirm search/result feedback across breakpoints | P2 |
| Athlete profile `/athletes/{squadMember}` | `Public/Athlete` | Spectators checking one athlete’s official participation | Return to athletes / open schedule | Detail view uses shared hero; ensure identity and context stay prominent | P2 |
| News `/news` | `Public/Info` | Visitors seeking announcements | Open relevant resource / contact secretariat | Informational template; content depends on current configured portal records | P2 |
| Downloads `/downloads` | `Public/Info` | Participants retrieving official documents | Download a document / read general information | Official links exist; make file purpose and download affordance clear | P2 |
| FAQ `/faq` | `Public/Info` | Visitors resolving common questions | Expand an answer / contact secretariat | Accordion interaction exists; shared typography and spacing apply | P2 |
| About `/about` | `Public/Info` | Visitors understanding the event | Read event details / open schedule | Informational template; shares layout and hero | P2 |
| General information `/general-information` | `Public/Info` | Participants learning rules and requirements | Open forms/documents / review dates | Grouped information and official documents exist; long content benefits from scanning hierarchy | P1 |
| Main committee `/jawatankuasa-induk` | `Public/Committee` | Visitors identifying event governance | Review committee / contact | Public committee listing | P2 |
| Student committee `/jawatankuasa-pelaksana` | `Public/StudentCommittee` | Visitors finding the implementation team | Review committee / contact | Public committee listing | P2 |
| Game chairpersons `/pengerusi-permainan` | `Public/GameChairpersons` | Participants locating sport leads | Find a chairperson / view sports | Public list; long lists need clear scan patterns | P2 |
| Important dates `/tarikh-penting` | `Public/ImportantDates` | Participants tracking deadlines and competition dates | Review dates / open schedule | Desktop table and stacked mobile/tablet list share the same published date records | P1 |
| Contact `/contact-us` | `Public/Contact` | Participants and visitors contacting the secretariat | Use contact details / open official UTeM or social link | Contact channels and 23-secretariat directory; desktop table and stacked mobile/tablet entries | P1 |
| Login `/login` | `GuestLayout` and authentication pages | Existing users entering the management portal | Sign in | Public header/footer are not used; authentication redesign is explicitly out of scope | Excluded |

`/matches`, `/results` and `/live` are legacy redirects to `/schedule`; `/portal` and `/index.php` redirect to `/`. `/sitemap.xml` and `/robots.txt` are machine-readable public endpoints rather than visual pages. Public file and storage routes serve downloads/assets. Authenticated management routes are excluded.

## Shared implementation map

- Route/controller: `routes/web.php`, `PublicPortalController` and `PublicPortalService`.
- Public layout and metadata: `resources/js/Layouts/PublicLayout.tsx`.
- Shared navigation: `PublicHeader`, `PublicDesktopNav`, `PublicMobileMenu`, `PublicAnnouncementBar`, `PublicLoginButton`, `LocaleSwitcher`.
- Shared page patterns: `PublicPageHero`, `PublicSectionHeading`, `PublicEmptyState`, `PublicErrorState`, `PublicLoadingState`, fixture/match cards, `PublicFooter`, `SafeImage`.
- Design tokens/theme: `resources/js/lib/publicTheme.ts`; shared styles: `resources/css/app.css`.
- Public React pages: 11 templates under `resources/js/Pages/Public`.

## Audit findings and implementation slice

Already present: route-specific title/description/social metadata, canonical URLs, sitemap/robots endpoints, skip link, visible focus treatment, responsive navigation, 44px mobile menu control, reduced-motion handling for existing motion, safe images, schedule empty/error states, and purpose-built venue map dialog.

Highest value shared-layer improvements: reduce purely decorative motion and visual effects; standardize card surfaces around restrained borders and modest radii; simplify the shared page hero; make the header’s active navigation and mobile menu unambiguous; and complete footer link groups while keeping the header compact. These updates do not change data, route contracts, or page workflows.

## Change record

This inventory is the no-edit audit baseline required before implementation.

## Shared refinement — 28 September 2026

- Standardized the public page hero on a compact dark surface with one restrained accent shape.
- Removed homepage orbital/grid decoration and the animated cosmic backdrop; preserved a static, decorative accent and the reduced-motion friendly interaction model.
- Simplified shared cards to a white surface, modest radius, light border and restrained hover elevation.
- Expanded the footer into Competition, Information and Contact route groups; the copyright year now follows the current year.
- Marked the matching Competition and Information navigation groups and individual menu links active on their public routes.
- Added stacked contact directory entries for mobile/tablet while preserving the semantic desktop table; telephone links have a 44px minimum target.
- Kept route contracts, controller/service behavior, content data and page actions unchanged.

The remaining high-priority verification item is manual visual review at 200% browser zoom; the project’s existing viewport, reduced-motion, touch-target and keyboard/axe checks remain applicable.

## Homepage and schedule refinement — 28 September 2026

- Homepage now presents the event name, primary schedule/sports actions, dates and welcome mascot before the official banner, so visitors can identify the event and act before scrolling past a poster image.
- Homepage mascot sizing is more restrained, while the official banner remains available below the primary introduction.
- Shared public section headings use a consistent, more restrained size across directories, information pages and homepage sections.
- Schedule hero is shorter to bring filters and fixtures closer to the top. Match grouping, event dates and displayed times use `Asia/Kuala_Lumpur` consistently; active-filter result counts are announced to assistive technology.
- Downloads now presents the six official SAF 20 PDFs already published with General Information; FAQ answers link directly to Schedule & Results or Contact instead of repeating generic guidance.
- Shared header, navigation, footer, theme tokens, hero and breadcrumb cover all 11 public React templates listed above. Homepage keeps its competition actions, mascot and banner within the shared hero composition. Committee pages retain their logos and document-style content inside the body card. Existing responsive/search improvements remain in place for directory, date and contact pages.

## Public content width alignment — 28 September 2026

- All page-level content containers align to the same `max-w-7xl` width used by the public header and hero.
- General Information and About keep long-form copy constrained to a comfortable reading width inside their full-width surfaces; committee, contact and athlete profile content no longer stop at a narrower outer container.

## Hero/header clearance fix — 28 September 2026

The header previously sat over page content, while heroes used guessed top padding; that caused eyebrow text to meet/overlap the navigation and required route-specific padding increases. The header now occupies normal document flow, and hero top spacing is modest and consistent. All routes using `PublicPageHero` inherit the fix; homepage and both committee pages were adjusted separately because they render custom heroes. Breadcrumbs were added to shared hero pages and committee pages, with an Athletes parent link on the athlete profile.

Directory and content-list improvements: Faculty and venue directories now have search, visible result counts, clear-search actions and no-match states. Important Dates switches from the wide table to readable stacked entries below desktop width. Responsive browser assertions now check that page headings start below the header and that the date table/list switch follows viewport width.

## Game chairperson cards — 28 September 2026

- Added a compact SAF 20 mascot to every sport card on /pengerusi-permainan, covering all 23 listed sports and rotating through existing mascot poses.
- Kept card numbers, titles and chairperson details in their existing roles; mascots are decorative and hidden from assistive technology.

## Homepage mascot — 28 September 2026

- Increased the `pose-welcome.webp` mascot to 560px on desktop, with responsive sizes on smaller screens, so it reads as the primary SAF 20 mascot in the homepage hero.
