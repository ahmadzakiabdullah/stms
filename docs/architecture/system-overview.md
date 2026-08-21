# System Overview

> Current route inventory is 148 application routes and 43 Inertia pages. Public athlete profiles and scorer events are implemented; `/schedule` is the canonical public match/results view and `/results/manage` is the authenticated result workflow.

> **Current implementation update — 21 August 2026:** Public athlete profiles, configurable individual scoring events and participant-grouped public scorer display are implemented. See `CURRENT_STATE.md` for release status.

> Architecture baseline above is refreshed for the 21 August 2026 working tree; older audit references below remain historical evidence.

## Architecture Style

STMS ialah **modern monolith**:

```text
Browser
  -> Laravel web routes + middleware
  -> Form Request / Policy / Controller
  -> Action / Service
  -> Eloquent + MySQL
  -> Inertia response
  -> React + TypeScript UI
```

Tiada REST API aktif. Laravel merender nama Inertia page dan props; React mengurus UI tanpa API layer berasingan.

## Technology

- PHP `^8.4`, Laravel `13.23.0`
- React `18.3.1`, Inertia React `2.3.25`, TypeScript `5.9.3`
- Tailwind CSS `3.4.19`, local shadcn/Radix components, Lucide
- Vite `8.0.16`
- MySQL, Spatie Permission dan Spatie Activity Log
- Cache/queue/session adalah environment-controlled; audited workspace currently uses database/database/file, while production target is Redis/Redis/Redis

## Domain Shape

Canonical ownership:

```text
Organization
├─ Session
│  ├─ Tournament
│  │  ├─ Event ─ Match/Fixture ─ Result
│  │  └─ tournament_sport ─ Sport
│  └─ Participant
└─ Sport ─ SportCategory
```

`Event` menghubungkan Tournament + Sport + SportCategory. Ia bukan strict single-parent chain pada schema. Tenant-aware rows turut menyimpan `organization_id` untuk isolation dan query efficiency.

## Backend Patterns

- **Policies/Gates** ialah authorization authority.
- **Form Requests** mengurus validation dan sebahagian request authorization.
- **Actions** menyelaras single-purpose mutations.
- **Services** mengandungi business rules/orchestration.
- **TenantContext + BelongsToOrganization** menambah organization scope.
- **Queued notifications** membawa tenant context melalui middleware.

Sensitive index/read actions kini memanggil policy/gate yang sesuai. Dashboard, EventParticipant dan Event index query/payload assembly telah dipindahkan kepada service khusus; DrawController masih mengandungi orchestration yang besar tetapi bukan release blocker semasa. Rujuk `TODOS.md`.

## Frontend

- 43 Inertia pages, semuanya `.tsx`.
- Shared layouts/components masih mempunyai compatibility `.jsx` files.
- React Hook Form + Zod digunakan pada forms yang telah dimigrasi; auth dan beberapa pages menggunakan Inertia `useForm`.
- TanStack Table terpasang tetapi tidak digunakan dalam source semasa.
- Authenticated navigation ialah role-aware presentation layer; backend Policy mesti kekal authoritative.

## Public Portal

Current canonical public workflows: `/schedule` renders the schedule/results directory; `/athletes` and `/athletes/{id}` render confirmed rosters and official athlete performance; `/matches`, `/results` and `/live` redirect to `/schedule`. Authenticated result management is `/results/manage`, including individual scorer events for configured sports.

`PublicPortalController` merender:

- `/` dan alias `/portal` -> `Public/Index` (homepage dengan anchor sections Sports, Schedule, Results dan Medal standings).
- `/matches` -> `Public/Matches` — semua jadual perlawanan dan keputusan terkini.
- `/sports`, `/schedule`, `/results`, `/faculties`, `/venues` dan `/live` -> `Public/Directory` (seksyen disahkan di controller).
- `/news`, `/downloads`, `/faq` dan `/about` -> `Public/Info`.
- `/contact-us` -> `Public/Contact`.
- `/sitemap.xml` -> sitemap public.

Navigator dan footer kongsi disediakan oleh `PublicHeader`/`PublicFooter`. `/manage/matches` dan `/manage/sports` ialah halaman pengurusan dalaman yang dilindungi auth; `/matches` dan `/sports` kekal awam. `/sports-programme`, `/medal-tally` dan `/schedules` tidak wujud.

`PublicPortalService` memilih organization/session melalui `PUBLIC_ORG_SLUG` + `PUBLIC_SESSION_SLUG`, menggunakan explicit organization predicates, cache dua minit dan query fixture upcoming/completed berasingan.

## Route and Runtime Summary

- 148 application routes termasuk sitemap; authenticated route group kekal dilindungi auth/verified middleware.
- Email verification ditentukan ketika route bootstrap melalui `EMAIL_VERIFICATION_REQUIRED`.
- `/health` boleh dilindungi token dan menyamar sebagai 404; `/up` ialah Laravel liveness asas.
- Runtime workspace audit: production env, debug off, database cache/queue, file session, email verification off, CSP report-only, enforcement off.

## Current Risks

1. Runtime production baseline tidak sama dengan secure example.
2. DB least privilege dan mail configuration belum dibuktikan.
3. Commit `4b04c46` lulus connected CI, tetapi runtime cutover dan authenticated post-deploy evidence masih belum tersedia.
4. Workspace, public production dan historical seeder data tidak sama.

Source: [full audit 17 August 2026](../audits/2026-08-17-full-project-and-production-audit.md).
