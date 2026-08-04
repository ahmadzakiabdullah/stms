# Dashboard

## Current implementation

The application has one role-aware entry point at `/dashboard`:

- **Super Admin / Org Admin** — operational overview for registration readiness, competition setup, matches, users, and tenant-scoped administration.
- **Admin Sport** — competition workspace with direct actions for assigned-sport matches, results, and rankings.
- **Staff** — reporting and notification entry points.
- **Faculty Representative** — dedicated registration and squad-management page (`Faculty/Dashboard`).
- **Dean** — redirected to the verification workspace (`/dean`).

## Administrator dashboard hierarchy

The generic administrator dashboard intentionally avoids duplicating full module tables. Information is ordered by urgency:

1. Role and organization context
2. Pending-registration attention banner
3. Four operational KPIs: active sessions, events, team registrations, and matches
4. Registration-readiness pipeline
5. Role-safe quick actions
6. Upcoming events and registrations by sport
7. Compact secondary totals and recent configuration records

Detailed registration, fixture, result, ranking, and analytics tables remain on their dedicated pages.

## Data contract

`DashboardController` supplies tenant-scoped stats, upcoming events, registration pipeline counts, registrations by sport, recent sessions/tournaments, and detailed registration data retained for controller tests and future drill-downs. Dashboard queries are cached for 60 seconds per user and filter combination.

The frontend uses shadcn/ui cards, badges, and buttons; Lucide icons; responsive Tailwind layouts; semantic headings; and English interface text.

## Quality gates

Dashboard changes must pass PHPUnit dashboard and query-budget tests, `npm run typecheck`, the Vite production build, and the Playwright/axe dashboard journey when browser execution is available.