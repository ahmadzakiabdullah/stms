# Authorization Architecture

## Model

STMS uses Spatie Permission, Laravel Policies/Gates, Form Requests, tenant scopes and route-model binding.

Active workspace roles:

| Role | Intended responsibility |
|---|---|
| `super-admin` | Cross-organization administration |
| `org-admin` | Tenant administration |
| `admin-sport` | Assigned-sport matches/results |
| `staff` | Operational reads/reports according to permissions |
| `faculty-representative` | Own-faculty registration/squad/forms |
| `dean` | Own-faculty verification/forms |

42 permissions exist in the audited workspace. Spatie role/permission checks are combined with organization ownership and assigned-sport rules.

## Intended Request Flow

```text
auth/verified -> TenantContext/global scope -> Form Request -> Policy/Gate -> Action/Service
```

Tenant scope is not a substitute for authorization. Cross-tenant records should normally resolve to 404; same-tenant records lacking permission should return 403.

## Current Gap

Mutation actions usually call `Gate::authorize()`, but read/index coverage is not consistent. At audit time:

- `OrganizationController@index` does not call `OrganizationPolicy::viewAny` and Organization is a root model without tenant scope.
- `UserController@index` manually tenant-scopes users but does not call `UserPolicy::viewAny`.
- `ParticipantController@index` relies on tenant scope but does not call `ParticipantPolicy::viewAny`.
- several other domain index pages rely on any authenticated same-tenant user being allowed to read them.

The role-aware sidebar is presentation only and cannot close this gap. Manual-URL allowed/denied tests are a release blocker.

## New Feature Requirement

Every protected feature must prove:

- allowed role;
- denied role through direct URL;
- cross-tenant read/write denial;
- assigned-sport restrictions where applicable;
- guest denial;
- index payload isolation.
