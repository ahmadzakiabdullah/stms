# Navigation

## Current implementation

The authenticated shell uses a fixed desktop sidebar and a mobile sheet. Navigation is defined in `resources/js/Layouts/AuthenticatedLayout.tsx`, rendered with Inertia `Link`, Lucide icons, active-route highlighting, and tenant branding.

Visibility is controlled by an explicit role matrix intended to align with Laravel policies. Ia ialah presentation rule sahaja dan audit 17 Ogos menemui beberapa index controller tanpa `viewAny`; matrix ini tidak membuktikan authorization backend.

## Role menu matrix

| Section | Super Admin | Org Admin | Admin Sport | Staff | Faculty Representative | Dean |
|---|---:|---:|---:|---:|---:|---:|
| Dashboard | Yes | Yes | Yes | Yes | Faculty workspace | Dean verification workspace |
| Notifications | Yes | Yes | Yes | Yes | Yes | Yes |
| Competition Setup | Full | Tenant-scoped | No | No | No | No |
| Registration | Full | Tenant-scoped | No | No | Participation Confirmation | Participation Confirmation |
| Competition Operations | Full | Tenant-scoped | Assigned sports | No | No | No |
| Analytics | Yes | Yes | No | Yes | No | No |
| Organizations | Yes | No | No | No | No | No |
| Users / Settings / Activity Logs | Yes | Yes | No | No | No | No |
| Roles & Permissions | Yes | No | No | No | No | No |

Baris Organizations menggambarkan menu yang dikehendaki. Policy semasa membenarkan beberapa capability organisasi kepada `org-admin`, jadi policy/controller/menu mesti diselaraskan sebelum release.

`Notifications` is a global utility under Overview rather than a report. Role-specific home behavior is:

- `faculty-representative`: `/dashboard` renders `Faculty/Dashboard` for registration and squad work.
- `dean`: `/dashboard` redirects to `/dean` for verification work.
- `admin-sport`: `/dashboard` shows competition-operation actions.
- `super-admin`, `org-admin`, and `staff`: `/dashboard` shows the operational overview with actions restricted by role.

## Interaction and accessibility

- Desktop sidebar remains pinned while its navigation region scrolls.
- Mobile navigation uses a sheet opened from the top bar.
- Every item has visible text; icons never carry meaning alone.
- Current-page state uses `route().current()` and a left accent marker.
- Role filtering is presentation-level only; Laravel Policies and Gates remain authoritative.

## Shared state

`HandleInertiaRequests` shares the authenticated user with loaded roles, tenant context, branding settings, and notification summary. The sidebar derives its role set from `auth.user.roles`.
