# Navigation

## Layout Structure

The application shell consists of two primary navigation areas:

1. **Sidebar** — persistent left-side navigation for primary section access
2. **Top bar** — user profile, notifications, theme toggle, and logout

## Sidebar Navigation

The sidebar contains the main menu items organized into sections with role-based visibility (super admin, faculty rep, dean). Each item uses a **Lucide icon** and is linked via Inertia's `<Link>` component.

```tsx
const navSections = [
  { title: null, items: [
    { label: 'Dashboard', icon: LayoutDashboard, href: 'dashboard' },
  ]},
  { title: 'Event Planning', items: [
    { label: 'Sessions', icon: Calendar, href: 'sessions.index' },
    { label: 'Tournaments', icon: Trophy, href: 'tournaments.index' },
    { label: 'Events', icon: Target, href: 'events.index' },
  ]},
  // ...
];
```

Menu items are highlighted using `route().current()`. The sidebar is collapsible on smaller screens via a hamburger toggle (`Menu` icon).

## Top Navigation

The top bar is minimal and focused on user actions:

- **Organization selector** (multi-tenant switch)
- **Theme toggle** (light/dark/system)
- **User dropdown** (profile, settings, logout)

## Implementation

Navigation state is managed via Inertia shared data. The current user, organization, and role flags (`isSuperAdmin`, `isFacultyRep`, `isDean`) are passed as shared props. Navigation items are defined inline in `AuthenticatedLayout.tsx` with filter logic based on role flags.

```tsx
// AuthenticatedLayout.tsx
<Sidebar
  user={user}
  isSuperAdmin={isSuperAdmin}
  isFacultyRep={isFacultyRep}
  isDean={isDean}
  app={app}
/>
```
