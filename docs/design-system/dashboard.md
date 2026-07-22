# Dashboard

## Layout

The dashboard is the default landing page after login. It follows a **stats + recent activity** layout:

```
┌──────────────────────────────────────────────┐
│  Dashboard                      [org switcher]│
├────────┬────────┬────────┬────────────────────┤
│  Stats  │  Stats  │  Stats  │  Stats           │
│  Card   │  Card   │  Card   │  Card            │
├────────┴────────┴────────┴────────────────────┤
│  Recent Sessions                  View All →   │
│  ┌──────────────────────────────────────────┐  │
│  │ Session 1         status · date          │  │
│  │ Session 2         status · date          │  │
│  └──────────────────────────────────────────┘  │
├────────────────────────────────────────────────┤
│  Recent Tournaments                View All →  │
│  ┌──────────────────────────────────────────┐  │
│  │ Tournament 1      status · date          │  │
│  │ Tournament 2      status · date          │  │
│  └──────────────────────────────────────────┘  │
└──────────────────────────────────────────────┘
```

## Stats Cards

Each stat card displays a metric (total sessions, active tournaments, participants, matches) with an icon and trend indicator. Cards use the shadcn/ui `<Card>` component:

```tsx
<Card>
  <CardHeader className="flex flex-row items-center justify-between pb-2">
    <CardTitle className="text-sm font-medium">Total Sessions</CardTitle>
    <CalendarIcon className="h-4 w-4 text-muted-foreground" />
  </CardHeader>
  <CardContent>
    <div className="text-2xl font-bold">{sessionCount}</div>
  </CardContent>
</Card>
```

## Inertia Page Structure

The dashboard is an Inertia page (`Pages/Dashboard.tsx`) that receives data via `props`:

```tsx
interface DashboardProps {
  stats: {
    sessionCount: number;
    tournamentCount: number;
    participantCount: number;
    matchCount: number;
  };
  recentSessions: Session[];
  recentTournaments: Tournament[];
}
```

Data is fetched in the controller using eager-loaded queries scoped to the current organization.
