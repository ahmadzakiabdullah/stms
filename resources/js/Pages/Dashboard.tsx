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
import { useI18n } from '@/lib/i18n';

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

function formatDate(date: string | undefined, locale: string, t: (key: string) => string) {
    if (!date) return t('Date not set');
    const dateLocale = locale === 'ms' ? 'ms-MY' : 'en-MY';
    return new Intl.DateTimeFormat(dateLocale, { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(date));
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
    const { locale, t } = useI18n();
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
    const cancelled = Number(safeRegistrationPipeline.cancelled ?? 0);
    const pipelineTotal = Math.max(1, totalEventRegistrations);
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
            <Head title={t('Dashboard')} />

            <div className="-m-4 min-h-[calc(100vh-4rem)] space-y-6 bg-slate-50/70 p-4 sm:-m-6 sm:p-6">
                <section className="relative flex flex-col gap-6 overflow-hidden rounded-3xl bg-slate-950 p-6 text-white shadow-xl shadow-slate-900/10 sm:flex-row sm:items-center sm:justify-between sm:p-8">
                    <div className="pointer-events-none absolute -right-16 -top-24 size-72 rounded-full bg-emerald-400/15 blur-3xl" />
                    <div>
                        <div className="mb-2 flex flex-wrap items-center gap-2">
                            <span className="rounded-full border border-emerald-300/20 bg-emerald-300/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[.16em] text-emerald-300">{t('Dashboard')}</span>
                            <span className="text-xs text-slate-400">{t(roleLabels[primaryRole] ?? 'System User')}</span>
                        </div>
                        <h1 className="text-3xl font-black tracking-tight sm:text-4xl">{t('Welcome back')}, {user.name}</h1>
                        <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-300">
                            {isAdministrator
                                ? t('Monitor registrations, competition readiness and tasks requiring attention.')
                                : roles.has('admin-sport')
                                    ? t('Manage fixtures and results for your assigned sports.')
                                    : t('Review operational progress and reporting.')}
                        </p>
                    </div>
                    {user.organization?.name && <span className="relative hidden text-xs text-slate-400 lg:block">{user.organization.name}</span>}
                    <Button asChild className="relative shrink-0 bg-emerald-400 text-slate-950 hover:bg-emerald-300">
                        <Link href={route(actions[0].route)}>{t(actions[0].label)}<ArrowRight className="ml-2 size-4" /></Link>
                    </Button>
                </section>

                {isAdministrator && pending > 0 && (
                    <section className="flex flex-col gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-950 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-start gap-3">
                            <div className="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg bg-amber-100"><ShieldCheck className="size-5 text-amber-700" /></div>
                            <div><p className="font-semibold">{pending} {pending === 1 ? t('registration need attention') : t('registrations need attention')}</p><p className="text-sm text-amber-800">{t('Review pending teams before competition preparation continues.')}</p></div>
                        </div>
                        <Button asChild size="sm" variant="outline" className="border-amber-300 bg-white text-amber-900 hover:bg-amber-100"><Link href={route('event-participants.index', { status: 'pending' })}>{t('Review now')}</Link></Button>
                    </section>
                )}

                <div className="flex items-center justify-between px-1"><div><p className="text-xs font-bold uppercase tracking-[.18em] text-emerald-600">{t('Overview')}</p><h2 className="mt-1 text-xl font-bold tracking-tight text-slate-900">Operational snapshot</h2></div><span className="text-xs text-slate-500">Live workspace</span></div>
                <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {metrics.map((metric) => {
                        const Icon = metric.icon;
                        return <Card key={metric.label} className="rounded-2xl border-slate-200/80 bg-white shadow-sm"><CardHeader className="flex flex-row items-start justify-between pb-2"><div><CardDescription>{t(metric.label)}</CardDescription><CardTitle className="mt-1 text-3xl tabular-nums">{metric.value}</CardTitle></div><div className={`flex size-10 items-center justify-center rounded-xl ${metric.tone}`}><Icon className="size-5" /></div></CardHeader><CardContent><p className="text-xs text-muted-foreground">{t(metric.note)}</p></CardContent></Card>;
                    })}
                </section>

                <section className="grid gap-6 xl:grid-cols-[1.35fr_1fr]">
                    <Card className="rounded-2xl border-slate-200/80 bg-white shadow-sm">
                        <CardHeader><CardTitle>{t('Registration Readiness')}</CardTitle><CardDescription>{t('Current event-registration approval pipeline')}</CardDescription></CardHeader>
                        <CardContent>
                            <div className="flex h-3 overflow-hidden rounded-full bg-muted">
                                <div className="bg-emerald-500" style={{ width: `${(confirmed / pipelineTotal) * 100}%` }} />
                                <div className="bg-amber-400" style={{ width: `${(pending / pipelineTotal) * 100}%` }} />
                                <div className="bg-rose-500" style={{ width: `${(rejected / pipelineTotal) * 100}%` }} />
                                <div className="bg-slate-400" style={{ width: `${(cancelled / pipelineTotal) * 100}%` }} />
                            </div>
                            <div className="mt-5 grid grid-cols-3 gap-3">
                                <div className="rounded-lg border bg-emerald-50 p-3"><p className="text-xs font-medium text-emerald-700">{t('Confirmed')}</p><p className="mt-1 text-2xl font-semibold text-emerald-800 tabular-nums">{confirmed}</p></div>
                                <div className="rounded-lg border bg-amber-50 p-3"><p className="text-xs font-medium text-amber-700">{t('Pending')}</p><p className="mt-1 text-2xl font-semibold text-amber-800 tabular-nums">{pending}</p></div>
                                <div className="rounded-lg border bg-rose-50 p-3"><p className="text-xs font-medium text-rose-700">{t('Rejected')}</p><p className="mt-1 text-2xl font-semibold text-rose-800 tabular-nums">{rejected}</p></div>
                                {cancelled > 0 && <div className="rounded-lg border bg-slate-50 p-3"><p className="text-xs font-medium text-slate-700">Dibatalkan</p><p className="mt-1 text-2xl font-semibold text-slate-800 tabular-nums">{cancelled}</p></div>}
                            </div>
                            {isAdministrator && <Button asChild variant="outline" size="sm" className="mt-4"><Link href={route('event-participants.index')}>{t('Open Event Registrations')}<ArrowRight className="ml-2 size-3.5" /></Link></Button>}
                        </CardContent>
                    </Card>

                    <Card className="rounded-2xl border-slate-200/80 bg-white shadow-sm">
                        <CardHeader><CardTitle>{t('Quick Actions')}</CardTitle><CardDescription>{t('Actions available for your role')}</CardDescription></CardHeader>
                        <CardContent className="space-y-2">
                            {actions.map((action) => { const Icon = action.icon; return <Link key={action.label} href={route(action.route)} className="group flex items-center gap-3 rounded-lg border p-3 transition hover:border-primary/30 hover:bg-accent"><div className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"><Icon className="size-4" /></div><div className="min-w-0 flex-1"><p className="text-sm font-medium">{t(action.label)}</p><p className="truncate text-xs text-muted-foreground">{t(action.description)}</p></div><ArrowRight className="size-4 text-muted-foreground transition group-hover:translate-x-0.5 group-hover:text-primary" /></Link>; })}
                        </CardContent>
                    </Card>
                </section>

                <section className="grid gap-6 xl:grid-cols-2">
                    <Card className="rounded-2xl border-slate-200/80 bg-white shadow-sm">
                        <CardHeader><CardTitle>{t('Upcoming Events')}</CardTitle><CardDescription>{t('Next active competition dates')}</CardDescription></CardHeader>
                        <CardContent className="space-y-3">
                            {safeUpcomingEvents.length === 0 ? <p className="py-6 text-center text-sm text-muted-foreground">{t('No upcoming events.')}</p> : safeUpcomingEvents.map((event) => <div key={event.id} className="flex items-center gap-3 rounded-lg border p-3"><div className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-violet-50 text-violet-700"><Target className="size-5" /></div><div className="min-w-0 flex-1"><p className="truncate text-sm font-medium">{event.name}</p><p className="truncate text-xs text-muted-foreground">{event.sport?.name ?? t('Sport')} · {event.tournament?.name ?? t('Tournament')}</p></div><div className="text-right"><p className="text-xs font-medium">{formatDate(event.start_date, locale, t)}</p><p className="text-[10px] text-muted-foreground">{event.registration_count} {t('teams')}</p></div></div>)}
                        </CardContent>
                    </Card>

                    <Card className="rounded-2xl border-slate-200/80 bg-white shadow-sm">
                        <CardHeader><CardTitle>{t('Registrations by Sport')}</CardTitle><CardDescription>{t('Highest participation across configured sports')}</CardDescription></CardHeader>
                        <CardContent>
                            {safeRegistrationsBySport.length === 0 ? <p className="py-6 text-center text-sm text-muted-foreground">{t('No registration data.')}</p> : <div className="space-y-4">{safeRegistrationsBySport.map((item) => <div key={item.name}><div className="mb-1.5 flex items-center justify-between text-sm"><span className="font-medium">{item.name}</span><span className="tabular-nums text-muted-foreground">{item.total}</span></div><div className="h-2 overflow-hidden rounded-full bg-muted"><div className="h-full rounded-full bg-primary" style={{ width: `${(item.total / maxSportRegistrations) * 100}%` }} /></div></div>)}</div>}
                        </CardContent>
                    </Card>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <Card className="rounded-2xl border-slate-200/80 bg-white shadow-sm"><CardHeader className="pb-2"><CardDescription>{t('Tournaments')}</CardDescription><CardTitle className="text-2xl tabular-nums">{safeStats.tournaments ?? 0}</CardTitle></CardHeader></Card>
                    <Card className="rounded-2xl border-slate-200/80 bg-white shadow-sm"><CardHeader className="pb-2"><CardDescription>{t('Participants')}</CardDescription><CardTitle className="text-2xl tabular-nums">{safeStats.participants ?? 0}</CardTitle></CardHeader></Card>
                    <Card className="rounded-2xl border-slate-200/80 bg-white shadow-sm"><CardHeader className="pb-2"><CardDescription>{t('Organization Registrations')}</CardDescription><CardTitle className="text-2xl tabular-nums">{safeStats.registrations ?? 0}</CardTitle></CardHeader></Card>
                    <Card className="rounded-2xl border-slate-200/80 bg-white shadow-sm"><CardHeader className="pb-2"><CardDescription>{t('Organizations')}</CardDescription><CardTitle className="text-2xl tabular-nums">{safeStats.organizations ?? 0}</CardTitle></CardHeader></Card>
                </section>

                {(safeRecentSessions.length > 0 || safeRecentTournaments.length > 0) && (
                    <section className="grid gap-6 xl:grid-cols-2">
                        <Card className="rounded-2xl border-slate-200/80 bg-white shadow-sm"><CardHeader><CardTitle className="text-base">{t('Recent Sessions')}</CardTitle></CardHeader><CardContent className="space-y-2">{safeRecentSessions.slice(0, 3).map((session) => <div key={session.id} className="flex items-center justify-between rounded-lg border px-3 py-2"><span className="text-sm font-medium">{session.name}</span><Badge variant={session.is_active ? 'default' : 'secondary'}>{session.is_active ? t('Active') : t('Inactive')}</Badge></div>)}</CardContent></Card>
                        <Card className="rounded-2xl border-slate-200/80 bg-white shadow-sm"><CardHeader><CardTitle className="text-base">{t('Recent Tournaments')}</CardTitle></CardHeader><CardContent className="space-y-2">{safeRecentTournaments.slice(0, 3).map((tournament) => <div key={tournament.id} className="flex items-center justify-between rounded-lg border px-3 py-2"><span className="text-sm font-medium">{tournament.name}</span><CheckCircle2 className="size-4 text-muted-foreground" /></div>)}</CardContent></Card>
                    </section>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
