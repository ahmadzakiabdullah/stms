# System Overview

> Current architecture as audited on 17 Ogos 2026.

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

- 38 Inertia pages, semuanya `.tsx`.
- Shared layouts/components masih mempunyai compatibility `.jsx` files.
- React Hook Form + Zod digunakan pada forms yang telah dimigrasi; auth dan beberapa pages menggunakan Inertia `useForm`.
- TanStack Table terpasang tetapi tidak digunakan dalam source semasa.
- Authenticated navigation ialah role-aware presentation layer; backend Policy mesti kekal authoritative.

## Public Portal

`PublicPortalController` merender:

- `/` dan IIS compatibility route `/portal` -> `Public/Index`
- `/contact-us` -> `Public/Contact`

Homepage menggabungkan Sports, Schedule, Results dan Medal standings sebagai anchor sections. Standalone `/sports-programme`, `/medal-tally` dan `/schedules` tidak wujud.

`PublicPortalService` memilih organization/session melalui `PUBLIC_ORG_SLUG` + `PUBLIC_SESSION_SLUG`, menggunakan explicit organization predicates, cache dua minit dan query fixture upcoming/completed berasingan.

## Route and Runtime Summary

- 126 application routes termasuk sitemap; authenticated route group kekal dilindungi auth/verified middleware.
- Email verification ditentukan ketika route bootstrap melalui `EMAIL_VERIFICATION_REQUIRED`.
- `/health` boleh dilindungi token dan menyamar sebagai 404; `/up` ialah Laravel liveness asas.
- Runtime workspace audit: production env, debug off, database cache/queue, file session, email verification off, CSP report-only, enforcement off.

## Current Risks

1. Runtime production baseline tidak sama dengan secure example.
2. DB least privilege dan mail configuration belum dibuktikan.
3. Commit `e535e4b` lulus connected CI, tetapi post-deploy evidence masih belum tersedia.
4. Workspace, public production dan historical seeder data tidak sama.

Source: [full audit 17 August 2026](../audits/2026-08-17-full-project-and-production-audit.md).
