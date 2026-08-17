import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useI18n } from '@/lib/i18n';
import type { PageProps, Session, Tournament } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import {
    Activity,
    ArrowRight,
    Award,
    Building2,
    CalendarClock,
    CheckCircle2,
    CircleAlert,
    ClipboardCheck,
    ClipboardList,
    Database,
    LayoutGrid,
    ListChecks,
    Radio,
    ShieldCheck,
    Swords,
    Target,
    Trophy,
    UserCog,
    Users,
} from 'lucide-react';

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
    tournament?: { id: string; name: string } | null;
    registration_count: number;
}

interface SportCount { name: string; total: number }

interface RegistrationStats {
    totalRegistrations?: number;
    pending?: number;
    confirmed?: number;
    totalFaculties?: number;
    totalEvents?: number;
}

interface SystemOverview {
    users?: number;
    activeOrganizations?: number;
    inactiveOrganizations?: number;
    activeEvents?: number;
    inactiveEvents?: number;
    eventsWithoutFixtures?: number;
    unscheduledFixtures?: number;
    fixturesByStatus?: Record<string, number>;
}

interface DashboardProps {
    stats?: BackendStats;
    recentSessions?: Session[];
    recentTournaments?: Tournament[];
    totalEventRegistrations?: number;
    participantsWithRegistrations?: number;
    upcomingEvents?: UpcomingEvent[];
    registrationsBySport?: SportCount[];
    registrationPipeline?: Record<string, number>;
    registrationStats?: RegistrationStats;
    squadStats?: Record<string, number>;
    systemOverview?: SystemOverview;
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
    return new Intl.DateTimeFormat(locale === 'ms' ? 'ms-MY' : 'en-MY', {
        day: 'numeric', month: 'short', year: 'numeric',
    }).format(new Date(date));
}

function percentage(value: number, total: number) {
    if (total <= 0) return 0;
    return Math.min(100, Math.max(0, Math.round((value / total) * 100)));
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
    registrationStats = {},
    squadStats = {},
    systemOverview = {},
}: DashboardProps) {
    const { auth } = usePage<PageProps>().props;
    const { locale, t } = useI18n();
    const user = auth?.user;
    const safeStats = stats && typeof stats === 'object' && !Array.isArray(stats) ? stats : {};
    const safeSessions = Array.isArray(recentSessions) ? recentSessions : [];
    const safeTournaments = Array.isArray(recentTournaments) ? recentTournaments : [];
    const safeEvents = Array.isArray(upcomingEvents) ? upcomingEvents : [];
    const safeSports = Array.isArray(registrationsBySport) ? registrationsBySport : [];
    const pipeline = registrationPipeline && typeof registrationPipeline === 'object' ? registrationPipeline : {};
    const squads = squadStats && typeof squadStats === 'object' ? squadStats : {};
    const roles = new Set((Array.isArray(user?.roles) ? user.roles : []).map((role) => role.name));
    const primaryRole = ['super-admin', 'org-admin', 'admin-sport', 'staff'].find((role) => roles.has(role)) ?? 'system-user';
    const isAdministrator = roles.has('super-admin') || roles.has('org-admin');
    const isSuperAdmin = roles.has('super-admin');

    const pending = Number(pipeline.pending ?? 0);
    const confirmed = Number(pipeline.confirmed ?? 0);
    const rejected = Number(pipeline.rejected ?? 0);
    const cancelled = Number(pipeline.cancelled ?? 0);
    const registrationTotal = Math.max(0, Number(registrationStats.totalRegistrations ?? totalEventRegistrations));
    const facultyTotal = Math.max(0, Number(registrationStats.totalFaculties ?? safeStats.participants ?? 0));
    const matchTotal = Math.max(0, Number(safeStats.matches ?? 0));
    const resultTotal = Math.max(0, Number(safeStats.results ?? 0));
    const squadTotal = Object.values(squads).reduce((sum, value) => sum + Number(value || 0), 0);
    const maxSportRegistrations = Math.max(1, ...safeSports.map((item) => Number(item.total)));
    const system = systemOverview && typeof systemOverview === 'object' ? systemOverview : {};
    const fixtureStatus = system.fixturesByStatus && typeof system.fixturesByStatus === 'object' ? system.fixturesByStatus : {};
    const scheduledFixtures = Number(fixtureStatus.scheduled ?? 0);
    const liveFixtures = Number(fixtureStatus.in_progress ?? 0);
    const completedFixtures = Number(fixtureStatus.completed ?? 0);
    const cancelledFixtures = Number(fixtureStatus.cancelled ?? 0);
    const unscheduledFixtures = Number(system.unscheduledFixtures ?? 0);
    const eventsWithoutFixtures = Number(system.eventsWithoutFixtures ?? 0);
    const operationalIssues = pending + unscheduledFixtures + eventsWithoutFixtures;

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
    const actions = isAdministrator ? administratorActions : roles.has('admin-sport') ? sportAdminActions : staffActions;

    const primaryMetrics = [
        { label: 'Active Sessions', value: safeStats.activeSessions ?? 0, note: `${safeStats.tournaments ?? 0} ${t('tournaments')}`, icon: CalendarClock, tone: 'bg-blue-50 text-blue-700 ring-blue-100' },
        { label: 'Events', value: safeStats.events ?? 0, note: `${safeStats.sports ?? 0} ${t('configured sports')}`, icon: Target, tone: 'bg-violet-50 text-violet-700 ring-violet-100' },
        { label: 'Team Registrations', value: registrationTotal, note: `${participantsWithRegistrations} ${t('participating faculties')}`, icon: ListChecks, tone: 'bg-cyan-50 text-cyan-700 ring-cyan-100' },
        { label: 'Matches', value: matchTotal, note: `${resultTotal} ${t('results recorded')}`, icon: Swords, tone: 'bg-amber-50 text-amber-700 ring-amber-100' },
    ];

    const readiness = [
        { label: 'Registration approval', value: confirmed, total: registrationTotal, tone: 'bg-emerald-500' },
        { label: 'Faculty participation', value: participantsWithRegistrations, total: facultyTotal, tone: 'bg-cyan-500' },
        { label: 'Match completion', value: resultTotal, total: matchTotal, tone: 'bg-violet-500' },
    ];

    const lifecycle = [
        { label: 'Platform', value: Number(system.activeOrganizations ?? 0), total: Number(safeStats.organizations ?? 0), icon: Building2 },
        { label: 'Competition setup', value: Number(system.activeEvents ?? 0), total: Number(safeStats.events ?? 0), icon: Target },
        { label: 'Registration approval', value: confirmed, total: registrationTotal, icon: ClipboardCheck },
        { label: 'Fixture scheduling', value: Math.max(0, matchTotal - unscheduledFixtures), total: matchTotal, icon: CalendarClock },
        { label: 'Results completion', value: resultTotal, total: matchTotal, icon: Trophy },
    ];

    if (!user) return null;

    return (
        <AuthenticatedLayout>
            <Head title={t('Dashboard')} />

            <main className="-m-4 min-h-[calc(100vh-4rem)] bg-slate-50/80 p-4 sm:-m-6 sm:p-6">
                <div className="mx-auto max-w-[1600px] space-y-6">
                    <section className="relative overflow-hidden rounded-3xl bg-slate-950 px-6 py-7 text-white shadow-xl shadow-slate-900/10 sm:px-8 sm:py-9">
                        <div className="pointer-events-none absolute -right-20 -top-28 size-80 rounded-full bg-emerald-400/20 blur-3xl" />
                        <div className="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                            <div className="max-w-3xl">
                                <div className="mb-4 flex flex-wrap items-center gap-2">
                                    <span className="rounded-full border border-emerald-300/20 bg-emerald-300/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[.18em] text-emerald-300">{t('System overview')}</span>
                                    <span className="text-xs text-slate-400">{t(roleLabels[primaryRole] ?? 'System User')}</span>
                                </div>
                                <h1 className="text-3xl font-black tracking-tight sm:text-4xl">{t('Welcome back')}, {user.name}</h1>
                                <p className="mt-3 max-w-2xl text-sm leading-6 text-slate-300">{t('A complete view of registration, competition and system readiness.')}</p>
                                {user.organization?.name && <p className="mt-4 flex items-center gap-2 text-xs font-medium text-slate-400"><Building2 className="size-4" />{user.organization.name}</p>}
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <Button asChild variant="outline" className="border-white/15 bg-white/5 text-white hover:bg-white/10 hover:text-white"><Link href={route('reports.index')}><Activity className="mr-2 size-4" />{t('Open Analytics')}</Link></Button>
                                <Button asChild className="bg-emerald-400 text-slate-950 hover:bg-emerald-300"><Link href={route(actions[0].route)}>{t(actions[0].label)}<ArrowRight className="ml-2 size-4" /></Link></Button>
                            </div>
                        </div>
                    </section>

                    {isAdministrator && pending > 0 && (
                        <section className="flex flex-col gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-950 sm:flex-row sm:items-center sm:justify-between">
                            <div className="flex items-start gap-3"><div className="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-100"><ShieldCheck className="size-5 text-amber-700" /></div><div><p className="font-semibold">{pending} {pending === 1 ? t('registration need attention') : t('registrations need attention')}</p><p className="text-sm text-amber-800">{t('Review pending teams before competition preparation continues.')}</p></div></div>
                            <Button asChild size="sm" variant="outline" className="border-amber-300 bg-white text-amber-900 hover:bg-amber-100"><Link href={route('event-participants.index', { status: 'pending' })}>{t('Review now')}</Link></Button>
                        </section>
                    )}

                    {isSuperAdmin && (
                        <section className="space-y-4" aria-labelledby="control-centre-title">
                            <div className="flex flex-col gap-2 px-1 sm:flex-row sm:items-end sm:justify-between"><div><p className="text-xs font-bold uppercase tracking-[.18em] text-emerald-700">{t('Super Admin Control Centre')}</p><h2 id="control-centre-title" className="mt-1 text-xl font-bold tracking-tight text-slate-950">{t('Platform health and competition flow')}</h2></div><div className={`inline-flex w-fit items-center gap-2 rounded-full px-3 py-1.5 text-xs font-bold ${operationalIssues > 0 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'}`}><span className={`size-2 rounded-full ${operationalIssues > 0 ? 'bg-amber-500' : 'bg-emerald-500'}`}/>{operationalIssues > 0 ? `${operationalIssues} ${t('items need attention')}` : t('Operations on track')}</div></div>

                            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                <ControlMetric icon={Building2} label={t('Organizations')} value={Number(system.activeOrganizations ?? 0)} detail={`${system.inactiveOrganizations ?? 0} ${t('inactive')}`} href="organizations.index" tone="bg-blue-50 text-blue-700"/>
                                <ControlMetric icon={Users} label={t('System users')} value={Number(system.users ?? 0)} detail={t('Accounts across all organizations')} href="users.index" tone="bg-cyan-50 text-cyan-700"/>
                                <ControlMetric icon={Radio} label={t('Live matches')} value={liveFixtures} detail={`${scheduledFixtures} ${t('scheduled')}`} href="matches.index" tone="bg-rose-50 text-rose-700"/>
                                <ControlMetric icon={Database} label={t('Recorded results')} value={completedFixtures} detail={`${cancelledFixtures} ${t('cancelled matches')}`} href="results.index" tone="bg-violet-50 text-violet-700"/>
                            </div>

                            <div className="grid gap-4 xl:grid-cols-[1.5fr_.75fr]">
                                <Card className="overflow-hidden rounded-2xl border-slate-200/80 bg-white shadow-sm">
                                    <CardHeader className="border-b border-slate-100"><CardTitle>{t('Competition lifecycle')}</CardTitle><CardDescription>{t('End-to-end progress from platform setup to official results')}</CardDescription></CardHeader>
                                    <CardContent className="p-0"><div className="grid md:grid-cols-5">{lifecycle.map((step, index) => { const Icon = step.icon; const progress = percentage(step.value, step.total); return <div key={step.label} className="relative border-b border-slate-100 p-5 last:border-b-0 md:border-b-0 md:border-r md:last:border-r-0"><div className="mb-5 flex items-center justify-between"><div className={`flex size-10 items-center justify-center rounded-xl ${progress === 100 && step.total > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'}`}><Icon className="size-5"/></div><span className="text-[10px] font-black uppercase tracking-wider text-slate-600">0{index + 1}</span></div><p className="text-sm font-bold text-slate-900">{t(step.label)}</p><p className="mt-1 text-xs tabular-nums text-slate-500">{step.value}/{step.total} · {progress}%</p><div className="mt-4 h-1.5 overflow-hidden rounded-full bg-slate-100"><div className={`h-full rounded-full ${progress === 100 && step.total > 0 ? 'bg-emerald-500' : 'bg-slate-700'}`} style={{ width: `${progress}%` }}/></div></div>; })}</div></CardContent>
                                </Card>

                                <Card className="rounded-2xl border-slate-200/80 bg-white shadow-sm">
                                    <CardHeader><div className="flex items-start justify-between"><div><CardTitle>{t('Attention queue')}</CardTitle><CardDescription>{t('Operational blockers requiring action')}</CardDescription></div><CircleAlert className={`size-5 ${operationalIssues > 0 ? 'text-amber-600' : 'text-emerald-600'}`}/></div></CardHeader>
                                    <CardContent className="space-y-2">
                                        <AttentionItem label={t('Pending registrations')} value={pending} href={route('event-participants.index', { status: 'pending' })}/>
                                        <AttentionItem label={t('Events without fixtures')} value={eventsWithoutFixtures} href={route('events.index')}/>
                                        <AttentionItem label={t('Fixtures without schedule')} value={unscheduledFixtures} href={route('matches.index')}/>
                                        {operationalIssues === 0 && <div className="flex items-center gap-3 rounded-xl bg-emerald-50 p-4 text-sm font-medium text-emerald-800"><CheckCircle2 className="size-5"/>{t('No operational blockers detected.')}</div>}
                                    </CardContent>
                                </Card>
                            </div>
                        </section>
                    )}

                    <section aria-labelledby="system-kpis">
                        <div className="mb-4 flex items-end justify-between px-1"><div><p className="text-xs font-bold uppercase tracking-[.18em] text-emerald-700">{t('Overview')}</p><h2 id="system-kpis" className="mt-1 text-xl font-bold tracking-tight text-slate-950">{t('System at a glance')}</h2></div><span className="hidden text-xs text-slate-500 sm:block">{t('Current tenant data')}</span></div>
                        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            {primaryMetrics.map((metric) => { const Icon = metric.icon; return <Card key={metric.label} className="rounded-2xl border-slate-200/80 bg-white shadow-sm"><CardHeader className="flex flex-row items-start justify-between pb-2"><div><CardDescription>{t(metric.label)}</CardDescription><CardTitle className="mt-1 text-3xl tabular-nums text-slate-950">{metric.value}</CardTitle></div><div className={`flex size-11 items-center justify-center rounded-xl ring-1 ${metric.tone}`}><Icon className="size-5" /></div></CardHeader><CardContent><p className="text-xs text-muted-foreground">{metric.note}</p></CardContent></Card>; })}
                        </div>
                    </section>

                    <section className="grid gap-6 xl:grid-cols-[1.25fr_.75fr]">
                        <Card className="rounded-2xl border-slate-200/80 bg-white shadow-sm">
                            <CardHeader><div className="flex items-start justify-between gap-4"><div><CardTitle>{t('Operational readiness')}</CardTitle><CardDescription>{t('Completion across the most important workflows')}</CardDescription></div><div className="flex size-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700"><Activity className="size-5" /></div></div></CardHeader>
                            <CardContent className="space-y-6">
                                {readiness.map((item) => { const progress = percentage(item.value, item.total); return <div key={item.label}><div className="mb-2 flex items-center justify-between gap-4 text-sm"><span className="font-medium text-slate-800">{t(item.label)}</span><span className="tabular-nums text-slate-500">{item.value}/{item.total} · {progress}%</span></div><div className="h-2.5 overflow-hidden rounded-full bg-slate-100"><div className={`h-full rounded-full transition-all ${item.tone}`} style={{ width: `${progress}%` }} /></div></div>; })}
                                <div className="grid grid-cols-2 gap-3 border-t pt-5 sm:grid-cols-4">
                                    <MiniStat label={t('Faculties')} value={facultyTotal} />
                                    <MiniStat label={t('Squad Members')} value={squadTotal} />
                                    <MiniStat label={t('Tournaments')} value={safeStats.tournaments ?? 0} />
                                    <MiniStat label={t('Organizations')} value={safeStats.organizations ?? 0} />
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="rounded-2xl border-slate-200/80 bg-white shadow-sm">
                            <CardHeader><CardTitle>{t('Registration pipeline')}</CardTitle><CardDescription>{t('Current event-registration approval status')}</CardDescription></CardHeader>
                            <CardContent>
                                <div className="flex h-3 overflow-hidden rounded-full bg-slate-100" role="img" aria-label={t('Registration pipeline')}>
                                    <div className="bg-emerald-500" style={{ width: `${percentage(confirmed, registrationTotal)}%` }} />
                                    <div className="bg-amber-400" style={{ width: `${percentage(pending, registrationTotal)}%` }} />
                                    <div className="bg-rose-500" style={{ width: `${percentage(rejected, registrationTotal)}%` }} />
                                    <div className="bg-slate-400" style={{ width: `${percentage(cancelled, registrationTotal)}%` }} />
                                </div>
                                <div className="mt-5 grid grid-cols-2 gap-3">
                                    <StatusStat label={t('Confirmed')} value={confirmed} tone="border-emerald-100 bg-emerald-50 text-emerald-800" />
                                    <StatusStat label={t('Pending')} value={pending} tone="border-amber-100 bg-amber-50 text-amber-800" />
                                    <StatusStat label={t('Rejected')} value={rejected} tone="border-rose-100 bg-rose-50 text-rose-800" />
                                    <StatusStat label={t('Cancelled')} value={cancelled} tone="border-slate-200 bg-slate-50 text-slate-700" />
                                </div>
                                {isAdministrator && <Button asChild variant="outline" size="sm" className="mt-5 w-full"><Link href={route('event-participants.index')}>{t('Open Event Registrations')}<ArrowRight className="ml-2 size-3.5" /></Link></Button>}
                            </CardContent>
                        </Card>
                    </section>

                    <section className="grid gap-6 xl:grid-cols-[1fr_1fr_.8fr]">
                        <Card className="rounded-2xl border-slate-200/80 bg-white shadow-sm">
                            <CardHeader><CardTitle>{t('Upcoming Events')}</CardTitle><CardDescription>{t('Next active competition dates')}</CardDescription></CardHeader>
                            <CardContent className="space-y-3">
                                {safeEvents.length === 0 ? <EmptyState icon={CalendarClock} text={t('No upcoming events.')} /> : safeEvents.map((event) => <div key={event.id} className="flex items-center gap-3 rounded-xl border border-slate-200 p-3"><div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-700"><Target className="size-5" /></div><div className="min-w-0 flex-1"><p className="truncate text-sm font-semibold text-slate-900">{event.name}</p><p className="truncate text-xs text-muted-foreground">{event.sport?.name ?? t('Sport')} · {event.tournament?.name ?? t('Tournament')}</p></div><div className="shrink-0 text-right"><p className="text-xs font-medium">{formatDate(event.start_date, locale, t)}</p><p className="text-[10px] text-muted-foreground">{event.registration_count} {t('teams')}</p></div></div>)}
                            </CardContent>
                        </Card>

                        <Card className="rounded-2xl border-slate-200/80 bg-white shadow-sm">
                            <CardHeader><CardTitle>{t('Registrations by Sport')}</CardTitle><CardDescription>{t('Highest participation across configured sports')}</CardDescription></CardHeader>
                            <CardContent>
                                {safeSports.length === 0 ? <EmptyState icon={LayoutGrid} text={t('No registration data.')} /> : <div className="space-y-4">{safeSports.map((item, index) => <div key={item.name}><div className="mb-1.5 flex items-center justify-between gap-3 text-sm"><span className="flex min-w-0 items-center gap-2 font-medium"><span className="w-5 text-xs tabular-nums text-slate-400">{index + 1}</span><span className="truncate">{item.name}</span></span><span className="tabular-nums text-muted-foreground">{item.total}</span></div><div className="ml-7 h-2 overflow-hidden rounded-full bg-slate-100"><div className="h-full rounded-full bg-cyan-500" style={{ width: `${percentage(Number(item.total), maxSportRegistrations)}%` }} /></div></div>)}</div>}
                            </CardContent>
                        </Card>

                        <Card className="rounded-2xl border-slate-200/80 bg-white shadow-sm">
                            <CardHeader><CardTitle>{t('Quick Actions')}</CardTitle><CardDescription>{t('Actions available for your role')}</CardDescription></CardHeader>
                            <CardContent className="space-y-2">
                                {actions.map((action) => { const Icon = action.icon; return <Link key={action.label} href={route(action.route)} className="group flex items-center gap-3 rounded-xl border border-slate-200 p-3 transition hover:border-primary/30 hover:bg-slate-50"><div className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"><Icon className="size-4" /></div><div className="min-w-0 flex-1"><p className="text-sm font-medium">{t(action.label)}</p><p className="truncate text-xs text-muted-foreground">{t(action.description)}</p></div><ArrowRight className="size-4 text-muted-foreground transition group-hover:translate-x-0.5 group-hover:text-primary" /></Link>; })}
                            </CardContent>
                        </Card>
                    </section>

                    {(safeSessions.length > 0 || safeTournaments.length > 0) && (
                        <section className="grid gap-6 xl:grid-cols-2">
                            <Card className="rounded-2xl border-slate-200/80 bg-white shadow-sm"><CardHeader><CardTitle className="text-base">{t('Recent Sessions')}</CardTitle></CardHeader><CardContent className="space-y-2">{safeSessions.slice(0, 4).map((session) => <div key={session.id} className="flex items-center justify-between rounded-xl border border-slate-200 px-3 py-3"><div className="min-w-0"><p className="truncate text-sm font-medium">{session.name}</p><p className="mt-0.5 text-xs text-muted-foreground">{formatDate(session.start_date, locale, t)} — {formatDate(session.end_date, locale, t)}</p></div><Badge variant={session.is_active ? 'default' : 'secondary'}>{session.is_active ? t('Active') : t('Inactive')}</Badge></div>)}</CardContent></Card>
                            <Card className="rounded-2xl border-slate-200/80 bg-white shadow-sm"><CardHeader><CardTitle className="text-base">{t('Recent Tournaments')}</CardTitle></CardHeader><CardContent className="space-y-2">{safeTournaments.slice(0, 4).map((tournament) => <div key={tournament.id} className="flex items-center justify-between rounded-xl border border-slate-200 px-3 py-3"><div className="min-w-0"><p className="truncate text-sm font-medium">{tournament.name}</p><p className="mt-0.5 truncate text-xs text-muted-foreground">{tournament.session?.name ?? t('Session')}</p></div><CheckCircle2 className="size-4 shrink-0 text-emerald-600" /></div>)}</CardContent></Card>
                        </section>
                    )}
                </div>
            </main>
        </AuthenticatedLayout>
    );
}

function MiniStat({ label, value }: { label: string; value: number }) {
    return <div><p className="text-2xl font-bold tabular-nums text-slate-950">{value}</p><p className="mt-1 text-xs text-slate-500">{label}</p></div>;
}

function StatusStat({ label, value, tone }: { label: string; value: number; tone: string }) {
    return <div className={`rounded-xl border p-3 ${tone}`}><p className="text-xs font-medium">{label}</p><p className="mt-1 text-2xl font-bold tabular-nums">{value}</p></div>;
}

function EmptyState({ icon: Icon, text }: { icon: LucideIcon; text: string }) {
    return <div className="flex min-h-36 flex-col items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50/70 px-4 text-center"><Icon className="mb-2 size-5 text-slate-400" /><p className="text-sm text-muted-foreground">{text}</p></div>;
}

function ControlMetric({ icon: Icon, label, value, detail, href, tone }: { icon: LucideIcon; label: string; value: number; detail: string; href: string; tone: string }) {
    return <Link href={route(href)} className="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md"><div className="flex items-start justify-between"><div className={`flex size-11 items-center justify-center rounded-xl ${tone}`}><Icon className="size-5"/></div><ArrowRight className="size-4 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-slate-600"/></div><p className="mt-5 text-3xl font-black tabular-nums text-slate-950">{value}</p><p className="mt-1 text-sm font-bold text-slate-800">{label}</p><p className="mt-1 text-xs text-slate-500">{detail}</p></Link>;
}

function AttentionItem({ label, value, href }: { label: string; value: number; href: string }) {
    if (value <= 0) return null;
    return <Link href={href} className="group flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 p-3 transition hover:bg-amber-100"><span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-white text-sm font-black tabular-nums text-amber-800 shadow-sm">{value}</span><span className="min-w-0 flex-1 text-sm font-semibold text-amber-950">{label}</span><ArrowRight className="size-4 text-amber-500 transition group-hover:translate-x-0.5"/></Link>;
}
