# STMS - Sports Tournament Management System

**STMS** is an enterprise-grade, multi-tenant platform designed to manage sports tournaments for organizations of various scales — from schools and universities to national sports associations and international multi-sport events.

## Current Focus (MVP)

This workspace (`saf/portal`) is a specific implementation/context of the STMS platform (currently carrying "Portal SAF" / UTeM branding in the UI).

The project is currently focused on delivering a **Minimum Viable Product (MVP)** with the following core features:

- Organization & User Management with RBAC
- Session Management
- Configurable Sports, Categories & Events
- Tournament Management
- Participant Registration
- Match Scheduling & Result Entry
- Basic Ranking Engine

## SAF 2026 Seed Data

`DatabaseSeeder` seeds the SAF sport master list, and `SAF2026DataSeeder` then seeds the SAF 2026 operational data:

- 1 organization: Universiti Teknikal Malaysia Melaka
- 1 session: Sukan Antara Fakulti 2026
- 2 tournaments: Fasa 1 and Fasa 2
- 24 sports
- 30 categories/events with athlete and official quota fields
- 8 faculties with faculty representative and dean users

## Tech Stack

- **Backend**: PHP 8.3, Laravel 13
- **Frontend**: React 18, TypeScript, Vite
- **UI Framework**: Inertia.js
- **Styling**: Tailwind CSS with shadcn/ui components
- **Forms**: React Hook Form with Zod for validation
- **Tables**: TanStack Table
- **Database**: MySQL 8 (using SQLite for local development)
- **Primary Keys**: UUIDs
- **Authorization**: Spatie Laravel Permission
- **Multi-Tenancy**: Single database with `organization_id` scoping
- **Cache Driver**: `database` (Recommended: `redis` for production)
- **Queue Driver**: `database` (Recommended: `redis` for production)

## Documentation

- [CLAUDE.md](./CLAUDE.md)
- [AGENTS.md](./AGENTS.md)
- [CURRENT_STATE.md](./CURRENT_STATE.md) ← **Read this for honest current implementation status**
- [ROADMAP.md](./ROADMAP.md)
- [TODOS.md](./TODOS.md)
- [Architecture Overview](./docs/architecture/system-overview.md)
- [Domain Model](./docs/architecture/domain-model.md)
- [Architecture Decision Records](./docs/adr/)

## Development Principles

- Follow SOLID principles and Laravel best practices
- Prefer configuration over hardcoding
- All data must be scoped to `organization_id`
- Use Service Layer + Action Classes pattern
- Write tests for every feature

## License

MIT License
