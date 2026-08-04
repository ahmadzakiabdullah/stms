import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    Activity,
    ArrowRight,
    Award,
    CalendarClock,
    CheckCircle2,
    ClipboardCheck,
    ClipboardList,
    ListChecks,
    Settings,
    ShieldCheck,
    Swords,
    Target,
    Trophy,
    UserCog,
    Users,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { PageProps, Session, Tournament } from '@/types';

interface BackendStats {
    organizations?: number;
    activeSessions?: number;
    tournaments?: number;
    sports?: number;
    events?: number;
    participants?: number;
    registrations?: number;
    matches?: number;
    results?: number;
}

interface UpcomingEvent {
    id: string;
    name: string;
    slug: string;
    start_date: string;
    sport?: { id: string; name: string } | null;
    sport_category?: { id: string; name: string } | null;
    tournament?: { id: string; name: string } | null;
    registration_count: number;
}

interface SportCount { name: string; total: number }

interface DashboardProps {
    stats?: BackendStats;
    recentSessions?: Session[];
    recentTournaments?: Tournament[];
    totalEventRegistrations?: number;
    participantsWithRegistrations?: number;
    upcomingEvents?: UpcomingEvent[];
    registrationsBySport?: SportCount[];
    registrationPipeline?: Record<string, number>;
}

interface ActionItem {
    label: string;
    description: string;
    route: string;
    icon: LucideIcon;
}

const roleLabels: Record<string, string> = {
    'super-admin': 'Super Administrator',
    'org-admin': 'Organization Administrator',
    'admin-sport': 'Sport Administrator',
    staff: 'Operations Staff',
};

function formatDate(date?: string) {
    if (!date) return 'Date not set';
    return new Intl.DateTimeFormat('en-MY', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(date));
}

export default function Dashboard({
    stats = {},
    recentSessions = [],
    recentTournaments = [],
    totalEventRegistrations = 0,
    participantsWithRegistrations = 0,
    upcomingEvents = [],
    registrationsBySport = [],
    registrationPipeline = {},
}: DashboardProps) {
    const { auth, app } = usePage<PageProps>().props;
    const user = auth?.user;
    const safeStats = stats && typeof stats === 'object' && !Array.isArray(stats) ? stats : {};
    const safeRecentSessions = Array.isArray(recentSessions) ? recentSessions : [];
    const safeRecentTournaments = Array.isArray(recentTournaments) ? recentTournaments : [];
    const safeUpcomingEvents = Array.isArray(upcomingEvents) ? upcomingEvents : [];
    const safeRegistrationsBySport = Array.isArray(registrationsBySport) ? registrationsBySport : [];
    const safeRegistrationPipeline = registrationPipeline && typeof registrationPipeline === 'object' && !Array.isArray(registrationPipeline)
        ? registrationPipeline
        : {};
    const safeUserRoles = Array.isArray(user?.roles) ? user.roles : [];
    const roles = new Set(safeUserRoles.map((role) => role.name));
    const primaryRole = ['super-admin', 'org-admin', 'admin-sport', 'staff'].find((role) => roles.has(role)) ?? 'system-user';
    const isAdministrator = roles.has('super-admin') || roles.has('org-admin');
    const pending = Number(safeRegistrationPipeline.pending ?? 0);
    const confirmed = Number(safeRegistrationPipeline.confirmed ?? 0);
    const rejected = Number(safeRegistrationPipeline.rejected ?? 0);
    const pipelineTotal = Math.max(1, pending + confirmed + rejected);
    const maxSportRegistrations = Math.max(1, ...safeRegistrationsBySport.map((item) => Number(item.total)));

    const administratorActions: ActionItem[] = [
        { label: 'Review Registrations', description: 'Approve teams and inspect rosters', route: 'event-participants.index', icon: ClipboardCheck },
        { label: 'Manage Events', description: 'Configure formats, quotas and draws', route: 'events.index', icon: Target },
        { label: 'Schedule Matches', description: 'Create fixtures and competition stages', route: 'matches.index', icon: Swords },
        { label: 'Manage Users', description: 'Assign roles and operational access', route: 'users.index', icon: UserCog },
    ];
    const sportAdminActions: ActionItem[] = [
        { label: 'Manage Matches', description: 'Work on assigned sport fixtures', route: 'matches.index', icon: Swords },
        { label: 'Enter Results', description: 'Record and verify match outcomes', route: 'results.index', icon: Trophy },
        { label: 'View Rankings', description: 'Monitor current standings', route: 'rankings.index', icon: Award },
        { label: 'Notifications', description: 'Review operational updates', route: 'notifications.index', icon: Activity },
    ];
    const staffActions: ActionItem[] = [
        { label: 'Open Analytics', description: 'Review completion and participation', route: 'reports.index', icon: ClipboardList },
        { label: 'Notifications', description: 'Review system updates', route: 'notifications.index', icon: Activity },
    ];
    const actions = isAdministrator
        ? administratorActions
        : roles.has('admin-sport')
            ? sportAdminActions
            : roles.has('staff')
                ? staffActions
                : [{ label: 'Notifications', description: 'Review system updates', route: 'notifications.index', icon: Activity }];

    const metrics = [
        { label: 'Active Sessions', value: safeStats.activeSessions ?? 0, note: 'Current event cycles', icon: CalendarClock, tone: 'bg-blue-50 text-blue-700' },
        { label: 'Events', value: safeStats.events ?? 0, note: `${safeStats.sports ?? 0} configured sports`, icon: Target, tone: 'bg-violet-50 text-violet-700' },
        { label: 'Team Registrations', value: totalEventRegistrations, note: `${participantsWithRegistrations} participating faculties`, icon: ListChecks, tone: 'bg-cyan-50 text-cyan-700' },
        { label: 'Matches', value: safeStats.matches ?? 0, note: `${safeStats.results ?? 0} results recorded`, icon: Swords, tone: 'bg-amber-50 text-amber-700' },
    ];

    if (!user) return null;

    return (
        <AuthenticatedLayout>
            <Head title="Dashboard" />

            <div className="space-y-6">
                <section className="flex flex-col gap-4 rounded-xl border bg-card p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div className="mb-2 flex flex-wrap items-center gap-2">
                            <Badge variant="secondary">{roleLabels[primaryRole] ?? 'System User'}</Badge>
                            {user.organization?.name && <span className="text-xs text-muted-foreground">{user.organization.name}</span>}
                        </div>
                        <h1 className="text-2xl font-semibold tracking-tight">Welcome back, {user.name}</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {isAdministrator
                                ? 'Monitor registrations, competition readiness and tasks requiring attention.'
                                : roles.has('admin-sport')
                                    ? 'Manage fixtures and results for your assigned sports.'
                                    : 'Review operational progress and reporting.'}
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={route(actions[0].route)}>{actions[0].label}<ArrowRight className="ml-2 size-4" /></Link>
                    </Button>
                </section>

                {isAdministrator && pending > 0 && (
                    <section className="flex flex-col gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-950 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-start gap-3">
                            <div className="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg bg-amber-100"><ShieldCheck className="size-5 text-amber-700" /></div>
                            <div><p className="font-semibold">{pending} registration{pending === 1 ? '' : 's'} need attention</p><p className="text-sm text-amber-800">Review pending teams before competition preparation continues.</p></div>
                        </div>
                        <Button asChild size="sm" variant="outline" className="border-amber-300 bg-white text-amber-900 hover:bg-amber-100"><Link href={route('event-participants.index', { status: 'pending' })}>Review now</Link></Button>
                    </section>
                )}

                <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {metrics.map((metric) => {
                        const Icon = metric.icon;
                        return <Card key={metric.label}><CardHeader className="flex flex-row items-start justify-between pb-2"><div><CardDescription>{metric.label}</CardDescription><CardTitle className="mt-1 text-3xl tabular-nums">{metric.value}</CardTitle></div><div className={`flex size-10 items-center justify-center rounded-lg ${metric.tone}`}><Icon className="size-5" /></div></CardHeader><CardContent><p className="text-xs text-muted-foreground">{metric.note}</p></CardContent></Card>;
                    })}
                </section>

                <section className="grid gap-6 xl:grid-cols-[1.4fr_1fr]">
                    <Card>
                        <CardHeader><CardTitle>Registration Readiness</CardTitle><CardDescription>Current event-registration approval pipeline</CardDescription></CardHeader>
                        <CardContent>
                            <div className="flex h-3 overflow-hidden rounded-full bg-muted">
                                <div className="bg-emerald-500" style={{ width: `${(confirmed / pipelineTotal) * 100}%` }} />
                                <div className="bg-amber-400" style={{ width: `${(pending / pipelineTotal) * 100}%` }} />
                                <div className="bg-rose-500" style={{ width: `${(rejected / pipelineTotal) * 100}%` }} />
                            </div>
                            <div className="mt-5 grid grid-cols-3 gap-3">
                                <div className="rounded-lg border bg-emerald-50 p-3"><p className="text-xs font-medium text-emerald-700">Confirmed</p><p className="mt-1 text-2xl font-semibold text-emerald-800 tabular-nums">{confirmed}</p></div>
                                <div className="rounded-lg border bg-amber-50 p-3"><p className="text-xs font-medium text-amber-700">Pending</p><p className="mt-1 text-2xl font-semibold text-amber-800 tabular-nums">{pending}</p></div>
                                <div className="rounded-lg border bg-rose-50 p-3"><p className="text-xs font-medium text-rose-700">Rejected</p><p className="mt-1 text-2xl font-semibold text-rose-800 tabular-nums">{rejected}</p></div>
                            </div>
                            {isAdministrator && <Button asChild variant="outline" size="sm" className="mt-4"><Link href={route('event-participants.index')}>Open Event Registrations<ArrowRight className="ml-2 size-3.5" /></Link></Button>}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle>Quick Actions</CardTitle><CardDescription>Actions available for your role</CardDescription></CardHeader>
                        <CardContent className="space-y-2">
                            {actions.map((action) => { const Icon = action.icon; return <Link key={action.label} href={route(action.route)} className="group flex items-center gap-3 rounded-lg border p-3 transition hover:border-primary/30 hover:bg-accent"><div className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"><Icon className="size-4" /></div><div className="min-w-0 flex-1"><p className="text-sm font-medium">{action.label}</p><p className="truncate text-xs text-muted-foreground">{action.description}</p></div><ArrowRight className="size-4 text-muted-foreground transition group-hover:translate-x-0.5 group-hover:text-primary" /></Link>; })}
                        </CardContent>
                    </Card>
                </section>

                <section className="grid gap-6 xl:grid-cols-2">
                    <Card>
                        <CardHeader><CardTitle>Upcoming Events</CardTitle><CardDescription>Next active competition dates</CardDescription></CardHeader>
                        <CardContent className="space-y-3">
                            {safeUpcomingEvents.length === 0 ? <p className="py-6 text-center text-sm text-muted-foreground">No upcoming events.</p> : safeUpcomingEvents.map((event) => <div key={event.id} className="flex items-center gap-3 rounded-lg border p-3"><div className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-violet-50 text-violet-700"><Target className="size-5" /></div><div className="min-w-0 flex-1"><p className="truncate text-sm font-medium">{event.name}</p><p className="truncate text-xs text-muted-foreground">{event.sport?.name ?? 'Sport'} · {event.tournament?.name ?? 'Tournament'}</p></div><div className="text-right"><p className="text-xs font-medium">{formatDate(event.start_date)}</p><p className="text-[10px] text-muted-foreground">{event.registration_count} teams</p></div></div>)}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle>Registrations by Sport</CardTitle><CardDescription>Highest participation across configured sports</CardDescription></CardHeader>
                        <CardContent>
                            {safeRegistrationsBySport.length === 0 ? <p className="py-6 text-center text-sm text-muted-foreground">No registration data.</p> : <div className="space-y-4">{safeRegistrationsBySport.map((item) => <div key={item.name}><div className="mb-1.5 flex items-center justify-between text-sm"><span className="font-medium">{item.name}</span><span className="tabular-nums text-muted-foreground">{item.total}</span></div><div className="h-2 overflow-hidden rounded-full bg-muted"><div className="h-full rounded-full bg-primary" style={{ width: `${(item.total / maxSportRegistrations) * 100}%` }} /></div></div>)}</div>}
                        </CardContent>
                    </Card>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <Card><CardHeader className="pb-2"><CardDescription>Tournaments</CardDescription><CardTitle className="text-2xl tabular-nums">{safeStats.tournaments ?? 0}</CardTitle></CardHeader></Card>
                    <Card><CardHeader className="pb-2"><CardDescription>Participants</CardDescription><CardTitle className="text-2xl tabular-nums">{safeStats.participants ?? 0}</CardTitle></CardHeader></Card>
                    <Card><CardHeader className="pb-2"><CardDescription>Organization Registrations</CardDescription><CardTitle className="text-2xl tabular-nums">{safeStats.registrations ?? 0}</CardTitle></CardHeader></Card>
                    <Card><CardHeader className="pb-2"><CardDescription>Organizations</CardDescription><CardTitle className="text-2xl tabular-nums">{safeStats.organizations ?? 0}</CardTitle></CardHeader></Card>
                </section>

                {(safeRecentSessions.length > 0 || safeRecentTournaments.length > 0) && (
                    <section className="grid gap-6 xl:grid-cols-2">
                        <Card><CardHeader><CardTitle className="text-base">Recent Sessions</CardTitle></CardHeader><CardContent className="space-y-2">{safeRecentSessions.slice(0, 3).map((session) => <div key={session.id} className="flex items-center justify-between rounded-lg border px-3 py-2"><span className="text-sm font-medium">{session.name}</span><Badge variant={session.is_active ? 'default' : 'secondary'}>{session.is_active ? 'Active' : 'Inactive'}</Badge></div>)}</CardContent></Card>
                        <Card><CardHeader><CardTitle className="text-base">Recent Tournaments</CardTitle></CardHeader><CardContent className="space-y-2">{safeRecentTournaments.slice(0, 3).map((tournament) => <div key={tournament.id} className="flex items-center justify-between rounded-lg border px-3 py-2"><span className="text-sm font-medium">{tournament.name}</span><CheckCircle2 className="size-4 text-muted-foreground" /></div>)}</CardContent></Card>
                    </section>
                )}
            </div>
        </AuthenticatedLayout>
    );
}