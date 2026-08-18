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

## Current Enforcement

The read/index gaps found during the original 17 August audit have been remediated. Sensitive indexes call `viewAny` or a dedicated gate, Organization management is super-admin-only, and admin-sport access is constrained to assigned sports. A six-role direct-URL matrix and HTTP/Inertia payload assertions cover allowed, denied and cross-tenant reads.

The role-aware sidebar remains presentation only; backend Policy/Gate enforcement is authoritative.

## New Feature Requirement

Every protected feature must prove:

- allowed role;
- denied role through direct URL;
- cross-tenant read/write denial;
- assigned-sport restrictions where applicable;
- guest denial;
- index payload isolation.
