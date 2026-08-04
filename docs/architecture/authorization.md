# Authorization Architecture

## Current implementation

STMS uses Spatie Laravel Permission, Laravel Policies/Gates, Form Requests, tenant global scopes, and route-model binding. Authorization is deny-by-default at the backend; sidebar visibility is a usability layer and never replaces policy checks.

## Operational roles

| Role | Current responsibility |
|---|---|
| `super-admin` | Cross-organization administration, full setup, competition, reporting, roles and audit access |
| `org-admin` | Tenant-scoped setup, registration, competition, users, settings, reports and audit access |
| `admin-sport` | Matches and results for sports assigned through `sport_user` |
| `staff` | Operational reporting and notifications according to granted permissions |
| `faculty-representative` | Own-faculty event registration, squad management, printable forms and notifications |
| `dean` | Own-faculty registration verification, confirmation forms and notifications |

Legacy policy references to `sport-coordinator` or `tournament-manager` are compatibility hooks; they are not created by the current production bootstrap.

## Permissions and policies

`database/seeders/DatabaseSeeder.php` creates the production bootstrap roles and granular permissions. `SAF2026DataSeeder.php` adds the dean role/permissions and guarded demo users only when demo seeding is explicitly allowed.

Policies are registered in `AppServiceProvider`. Tenant-aware model policies check organization ownership, with the super-admin exception where intended. `admin-sport` mutations additionally call `User::canManageSport()`.

## Request flow

1. Authentication/verification middleware validates the session.
2. Tenant global scopes constrain model queries.
3. Form Requests validate mutation payloads and perform request-level authorization where configured.
4. Controllers call `Gate::authorize()` or model policies.
5. Policies enforce role/permission, organization ownership, and assigned-sport restrictions.
6. Services/Actions execute the authorized mutation.

Cross-tenant scoped records normally resolve as 404; visible same-tenant records without permission return 403.

## Navigation alignment

The sidebar reads `auth.user.roles` and uses the explicit matrix in `AuthenticatedLayout.tsx`. The authoritative matrix is documented in `docs/design-system/navigation.md`. Every new menu item must name its allowed roles and link only to a policy-authorized route.

## Testing requirements

Every protected feature requires allowed-role, denied-role, cross-tenant, and (where relevant) assigned-sport tests. Authorization regressions must be represented in Feature tests before release.