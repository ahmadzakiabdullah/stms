import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Activity,
    ArrowRight,
    Award,
    CalendarCheck,
    CalendarClock,
    ListChecks,
    Scale,
    Target,
    Trophy,
    Users,
} from 'lucide-react';
import { type LucideIcon } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import FacultyDashboard from '@/Pages/Dashboard/FacultyDashboard';
import Pagination from '@/components/Pagination';
import { type Event, type PageProps, type Paginated, type Session, type Tournament } from '@/types';

interface BackendStats {
    organizations?: number;
    activeSessions?: number;
    tournaments?: number;
    sports?: number;
    events?: number;
    matches?: number;
    results?: number;
    [key: string]: number | undefined;
}

interface StatItem {
    label: string;
    value: string;
    note: string;
    icon: LucideIcon;
}

interface SportCount {
    name: string;
    total: number;
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

interface FacultyRegistration {
    id: string;
    event: {
        id: string;
        name: string;
        sport?: { id: string; name: string } | null;
        sport_category?: { id: string; name: string } | null;
        start_date: string;
    } | null;
    squad_members: { role: string }[];
}

interface FacultyStat {
    id: string;
    name: string;
    total: number;
    pending: number;
    confirmed: number;
    rejected: number;
}

interface RegistrationEventRow extends Omit<Event, 'sport' | 'tournament'> {
    sport?: { name: string };
    sportCategory?: { name: string };
    tournament?: { name: string };
    total?: number;
}

interface RegistrationStats {
    totalRegistrations: number;
    pending: number;
    confirmed: number;
    totalFaculties: number;
    totalEvents: number;
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
    isFacultyRep?: boolean;
    myRegistrations?: number;
    facultyRegistrations?: FacultyRegistration[];
    facultyMale?: number;
    facultyFemale?: number;
    facultyOfficials?: number;
    registrationStats?: RegistrationStats;
    facultyStats?: FacultyStat[];
    eventStats?: Paginated<RegistrationEventRow> | RegistrationEventRow[];
    sports?: { id: string; name: string }[];
    faculties?: { id: string; name: string }[];
    squadStats?: Record<string, number>;
}

const sportIcon: Record<string, string> = {
    badminton: '🏸', 'bola sepak': '⚽', 'bola keranjang': '🏀',
    'bola tampar': '🏐', hoki: '🏑', ragbi: '🏉', olahraga: '🏃',
    renang: '🏊', memanah: '🎯', pingpong: '🏓', taekwondo: '🥋',
    silat: '⚔️', catur: '♟️', efootball: '🎮',
};

function getSportIcon(name?: string): string {
    if (!name) return '🏅';
    const lower = name.toLowerCase();
    for (const [key, icon] of Object.entries(sportIcon)) {
        if (lower.includes(key)) return icon;
    }
    return '🏅';
}

const setupSteps = [
    {
        step: 1,
        title: 'Create Sessions',
        desc: 'Define event cycles like SUKMA 2026 or SUKIPT. Sessions are the highest-level container for your tournaments.',
        icon: CalendarClock,
        route: 'sessions.index',
        cta: 'Create Session',
        color: 'bg-blue-50 text-blue-700 border-blue-200',
        iconBg: 'bg-blue-100',
    },
    {
        step: 2,
        title: 'Add Sports & Categories',
        desc: 'Configure available sports (Football, Badminton, etc.) and their categories (Men\'s Singles, Women\'s Team, etc.).',
        icon: Award,
        route: 'sports.index',
        cta: 'Add Sports',
        color: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        iconBg: 'bg-emerald-100',
    },
    {
        step: 3,
        title: 'Create Tournaments',
        desc: 'Group events into competitions under each session. A tournament can include multiple sports.',
        icon: Trophy,
        route: 'tournaments.index',
        cta: 'Create Tournament',
        color: 'bg-amber-50 text-amber-700 border-amber-200',
        iconBg: 'bg-amber-100',
    },
    {
        step: 4,
        title: 'Create Events',
        desc: 'Define specific sub-competitions under each tournament, linked to a sport and category.',
        icon: Target,
        route: 'events.index',
        cta: 'Create Event',
        color: 'bg-purple-50 text-purple-700 border-purple-200',
        iconBg: 'bg-purple-100',
    },
    {
        step: 5,
        title: 'Add Participants',
        desc: 'Register faculties, teams, or individual participants who will compete in events.',
        icon: Users,
        route: 'participants.index',
        cta: 'Add Participant',
        color: 'bg-rose-50 text-rose-700 border-rose-200',
        iconBg: 'bg-rose-100',
    },
    {
        step: 6,
        title: 'Register for Events',
        desc: 'Assign participants to events they\'ll compete in. Track who is registered for what.',
        icon: ListChecks,
        route: 'event-participants.index',
        cta: 'Manage Registrations',
        color: 'bg-cyan-50 text-cyan-700 border-cyan-200',
        iconBg: 'bg-cyan-100',
    },
];

export default function Dashboard({
    stats: backendStats = {},
    recentSessions = [],
    recentTournaments = [],
    totalEventRegistrations = 0,
    participantsWithRegistrations = 0,
    upcomingEvents = [],
    registrationsBySport = [],
    registrationPipeline = {},
    isFacultyRep = false,
    myRegistrations = 0,
    facultyRegistrations = [],
    facultyMale = 0,
    facultyFemale = 0,
    facultyOfficials = 0,
    registrationStats: registrationStatsProp,
    facultyStats: facultyStatsProp = [],
    eventStats: eventStatsProp = [],
    sports: sportsProp = [],
    faculties: facultiesProp = [],
    squadStats: squadStatsProp = {},
}: DashboardProps) {
    const { auth, app } = usePage<PageProps>().props;
    const user = auth?.user;

    const facultyStats = Array.isArray(facultyStatsProp) ? facultyStatsProp : [];
    const sports = Array.isArray(sportsProp) ? sportsProp : [];
    const faculties = Array.isArray(facultiesProp) ? facultiesProp : [];
    const eventStats = Array.isArray(eventStatsProp) ? eventStatsProp : (eventStatsProp?.data ?? []);
    const registrationStats: Partial<RegistrationStats> = registrationStatsProp ?? {};
    const squadStats = (squadStatsProp && typeof squadStatsProp === 'object' && !Array.isArray(squadStatsProp)) ? squadStatsProp : {};

    const squadTotal = useMemo(() => Object.values(squadStats).reduce((sum, n) => sum + (Number(n) || 0), 0), [squadStats]);
    const squadMale = Number(squadStats.athlete_male) || 0;
    const squadFemale = Number(squadStats.athlete_female) || 0;
    const squadOfficials = Math.max(0, squadTotal - squadMale - squadFemale);

    const [regSportFilter, setRegSportFilter] = useState('');
    const [regFacultyFilter, setRegFacultyFilter] = useState('');
    const [regStatusFilter, setRegStatusFilter] = useState('');
    const regFilterTimerRef = useRef<ReturnType<typeof setTimeout>>();

    useEffect(() => {
        const p = new URLSearchParams(window.location.search);
        setRegSportFilter(p.get('sport_id') ?? '');
        setRegFacultyFilter(p.get('faculty_id') ?? '');
        setRegStatusFilter(p.get('status') ?? '');
    }, []);

    const navigateWithRegFilters = () => {
        const params: Record<string, string> = {};
        if (regSportFilter) params.sport_id = regSportFilter;
        if (regFacultyFilter) params.faculty_id = regFacultyFilter;
        if (regStatusFilter) params.status = regStatusFilter;
        router.get(route('dashboard'), params, { preserveScroll: true, preserveState: true });
    };

    const handleRegFilterChange = (setter: (v: string) => void) => (value: string) => {
        setter(value);
        if (regFilterTimerRef.current) clearTimeout(regFilterTimerRef.current);
        regFilterTimerRef.current = setTimeout(navigateWithRegFilters, 250);
    };

    const clearRegFilters = () => {
        setRegSportFilter(''); setRegFacultyFilter(''); setRegStatusFilter('');
        router.get(route('dashboard'), {}, { preserveScroll: true, preserveState: true });
    };

    const registrationsBySportSafe = Array.isArray(registrationsBySport) ? registrationsBySport : [];
    const upcomingEventsSafe = Array.isArray(upcomingEvents) ? upcomingEvents : [];
    const recentSessionsSafe = Array.isArray(recentSessions) ? recentSessions : [];
    const recentTournamentsSafe = Array.isArray(recentTournaments) ? recentTournaments : [];
    const pipelineSafe =
        typeof registrationPipeline === 'object' && registrationPipeline !== null ? registrationPipeline : {};

    const isEmpty = (backendStats.sports ?? 0) === 0 && (backendStats.activeSessions ?? 0) === 0;

    if (!user) {
        if (typeof window !== 'undefined' && typeof route !== 'undefined') {
            window.location.href = route('login');
        }
        return (
            <div className="flex min-h-screen items-center justify-center bg-background">
                <div className="text-center">
                    <p className="text-muted-foreground">Please log in to access the dashboard.</p>
                </div>
            </div>
        );
    }

    if (isEmpty) {
        return (
            <AuthenticatedLayout
                header={
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <div className="text-sm text-muted-foreground">Welcome, {user.name}</div>
                            <h1 className="mt-1 text-2xl font-semibold tracking-tight">{app?.name || 'Dashboard'}</h1>
                        </div>
                    </div>
                }
            >
                <Head title="Dashboard" />

                <Card className="border-primary/20 bg-gradient-to-br from-primary/5 to-transparent">
                    <CardContent className="py-10 text-center">
                        <div className="mx-auto mb-4 flex size-16 items-center justify-center rounded-2xl bg-primary/10">
                            <Trophy className="size-8 text-primary" />
                        </div>
                        <h2 className="mb-2 text-xl font-semibold">Welcome to SAF Portal</h2>
                        <p className="mx-auto mb-6 max-w-lg text-sm text-muted-foreground">
                            Your system is ready. Set up your sports event management platform by following the steps below.
                            Start with creating a Session, then add Sports, Tournaments, Events, and Participants.
                        </p>
                    </CardContent>
                </Card>

                <div className="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {setupSteps.map((s) => {
                        const Icon = s.icon;
                        return (
                            <Card key={s.step} className="border">
                                <CardHeader>
                                    <div className="flex items-start justify-between">
                                        <div className={`flex size-10 items-center justify-center rounded-lg ${s.iconBg}`}>
                                            <Icon className="size-5" />
                                        </div>
                                        <span className="flex size-7 items-center justify-center rounded-full bg-muted text-xs font-bold text-muted-foreground">
                                            {s.step}
                                        </span>
                                    </div>
                                    <CardTitle className="mt-3 text-base">{s.title}</CardTitle>
                                    <CardDescription>{s.desc}</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <Button asChild className="w-full" size="sm">
                                        <Link href={route(s.route)}>
                                            {s.cta} <ArrowRight className="ml-2 size-3.5" />
                                        </Link>
                                    </Button>
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>

                <div className="mt-8">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">System Hierarchy</CardTitle>
                            <CardDescription>How data flows in SAF Portal</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="flex flex-wrap items-center gap-2 text-sm">
                                <Badge variant="outline" className="py-1.5">Organization</Badge>
                                <ArrowRight className="size-4 text-muted-foreground" />
                                <Badge variant="outline" className="py-1.5">Session</Badge>
                                <ArrowRight className="size-4 text-muted-foreground" />
                                <Badge variant="outline" className="py-1.5">Tournament</Badge>
                                <ArrowRight className="size-4 text-muted-foreground" />
                                <Badge variant="outline" className="py-1.5">Event</Badge>
                                <ArrowRight className="size-4 text-muted-foreground" />
                                <Badge variant="outline" className="py-1.5">Participant</Badge>
                                <ArrowRight className="size-4 text-muted-foreground" />
                                <Badge className="bg-primary/10 text-primary py-1.5">Registration</Badge>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </AuthenticatedLayout>
        );
    }

    if (isFacultyRep) {
        return (
            <AuthenticatedLayout header={
                <div className="flex items-center justify-between">
                    <div>
                        <div className="text-sm text-muted-foreground">{user.name}</div>
                        <h1 className="mt-1 text-2xl font-semibold tracking-tight">{app?.name || 'Dashboard'}</h1>
                    </div>
                </div>
            }>
                <FacultyDashboard
                    facultyRegistrations={facultyRegistrations as any}
                    facultyMale={facultyMale}
                    facultyFemale={facultyFemale}
                    facultyOfficials={facultyOfficials}
                />
            </AuthenticatedLayout>
        );
    }

    const v = backendStats;

    const pipeline = {
        pending: pipelineSafe.pending ?? 0,
        confirmed: pipelineSafe.confirmed ?? 0,
        rejected: pipelineSafe.rejected ?? 0,
    };
    const pipelineTotal = pipeline.pending + pipeline.confirmed + pipeline.rejected;

    const primaryStats: StatItem[] = [
        { label: 'Active Sessions', value: String(v.activeSessions ?? 0), note: `${v.tournaments ?? 0} tournaments across all sessions`, icon: CalendarClock },
        { label: 'Events', value: String(v.events ?? 0), note: `Competing across ${v.sports ?? 0} sports`, icon: Target },
        { label: 'Event Registrations', value: String(totalEventRegistrations), note: `${participantsWithRegistrations} participants registered`, icon: ListChecks },
        { label: 'Matches', value: String(v.matches ?? 0), note: `${v.results ?? 0} results recorded`, icon: Scale },
    ];

    const secondaryStats: StatItem[] = [
        { label: 'Organizations', value: String(v.organizations ?? 0), note: 'Tenants', icon: Users },
        { label: 'Sports', value: String(v.sports ?? 0), note: 'Configured', icon: Award },
        { label: 'Participants', value: String(v.participants ?? 0), note: 'Faculties & teams', icon: Users },
        { label: 'Results', value: String(v.results ?? 0), note: 'Recorded', icon: Trophy },
    ];

    const maxSportRegistrations = Math.max(1, ...registrationsBySportSafe.map((s) => s.total));

    const quickActions = [
        { label: 'New Session', href: 'sessions.index', icon: CalendarClock, tone: 'bg-blue-50 text-blue-600', desc: 'Start an event cycle' },
        { label: 'New Event', href: 'events.index', icon: Target, tone: 'bg-purple-50 text-purple-600', desc: 'Define a competition' },
        { label: 'Add Participant', href: 'participants.index', icon: Users, tone: 'bg-rose-50 text-rose-600', desc: 'Register a faculty' },
        { label: 'Registrations', href: 'event-participants.index', icon: ListChecks, tone: 'bg-cyan-50 text-cyan-600', desc: 'Approve & manage' },
    ];

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <div className="text-sm text-muted-foreground">Welcome back, {user.name}</div>
                        <h1 className="mt-1 text-2xl font-semibold tracking-tight">{app?.name || 'Dashboard'}</h1>
                        <p className="mt-1 text-sm text-muted-foreground">Overview of your sports competition operations</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline">
                            <Link href={route('sessions.index')}>
                                <CalendarClock className="mr-2 size-4" />
                                Sessions
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={route('tournaments.index')}>
                                <Trophy className="mr-2 size-4" />
                                Tournaments
                            </Link>
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title="Dashboard" />

            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                {primaryStats.map((item) => {
                    const Icon = item.icon;
                    return (
                        <Card key={item.label}>
                            <CardHeader className="flex flex-row items-center justify-between gap-3">
                                <div>
                                    <CardDescription>{item.label}</CardDescription>
                                    <CardTitle className="mt-2 text-3xl tabular-nums">{item.value}</CardTitle>
                                </div>
                                <div className="flex size-11 items-center justify-center rounded-lg bg-primary/10">
                                    <Icon className="size-5 text-primary" />
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="text-sm text-muted-foreground">{item.note}</div>
                            </CardContent>
                        </Card>
                    );
                })}
            </div>

            <div className="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                {secondaryStats.map((item) => {
                    const Icon = item.icon;
                    return (
                        <Card key={item.label}>
                            <CardContent className="flex items-center justify-between py-4">
                                <div>
                                    <div className="text-sm text-muted-foreground">{item.label}</div>
                                    <div className="mt-1 text-2xl font-semibold tabular-nums">{item.value}</div>
                                </div>
                                <div className="flex size-9 items-center justify-center rounded-lg bg-muted">
                                    <Icon className="size-4 text-muted-foreground" />
                                </div>
                            </CardContent>
                        </Card>
                    );
                })}
            </div>

            <div className="mt-6 grid gap-4 xl:grid-cols-3">
                <Card className="xl:col-span-2">
                    <CardHeader className="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle>Registration Pipeline</CardTitle>
                            <CardDescription>Event registration approvals across all events</CardDescription>
                        </div>
                        <Button asChild variant="ghost" size="sm">
                            <Link href={route('event-participants.index')}>Review</Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        <div className="flex h-2.5 w-full overflow-hidden rounded-full bg-muted">
                            <div
                                className="bg-amber-400 transition-all"
                                style={{ width: `${pipelineTotal > 0 ? (pipeline.pending / pipelineTotal) * 100 : 0}%` }}
                            />
                            <div
                                className="bg-emerald-500 transition-all"
                                style={{ width: `${pipelineTotal > 0 ? (pipeline.confirmed / pipelineTotal) * 100 : 0}%` }}
                            />
                            <div
                                className="bg-rose-400 transition-all"
                                style={{ width: `${pipelineTotal > 0 ? (pipeline.rejected / pipelineTotal) * 100 : 0}%` }}
                            />
                        </div>
                        <div className="mt-4 grid grid-cols-3 gap-3">
                            <div className="rounded-lg border bg-amber-50/50 p-3">
                                <div className="text-xs text-muted-foreground">Pending</div>
                                <div className="mt-1 text-2xl font-semibold tabular-nums text-amber-700">{pipeline.pending}</div>
                            </div>
                            <div className="rounded-lg border bg-emerald-50/50 p-3">
                                <div className="text-xs text-muted-foreground">Confirmed</div>
                                <div className="mt-1 text-2xl font-semibold tabular-nums text-emerald-700">{pipeline.confirmed}</div>
                            </div>
                            <div className="rounded-lg border bg-rose-50/50 p-3">
                                <div className="text-xs text-muted-foreground">Rejected</div>
                                <div className="mt-1 text-2xl font-semibold tabular-nums text-rose-700">{pipeline.rejected}</div>
                            </div>
                        </div>
                        {pipeline.pending > 0 && (
                            <div className="mt-3">
                                <Button asChild size="sm" variant="outline" className="text-amber-700 hover:text-amber-800">
                                    <Link href={route('event-participants.index')}>
                                        <ArrowRight className="mr-1 size-3.5" />
                                        Review {pipeline.pending} pending registration{pipeline.pending > 1 ? 's' : ''}
                                    </Link>
                                </Button>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Registrations by Sport</CardTitle>
                        <CardDescription>Top sports by registration count</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {registrationsBySportSafe.length > 0 ? (
                            <div className="space-y-3">
                                {registrationsBySportSafe.map((s) => (
                                    <div key={s.name}>
                                        <div className="mb-1 flex items-center justify-between text-sm">
                                            <span className="flex items-center gap-2">
                                                <span className="text-base">{getSportIcon(s.name)}</span>
                                                {s.name}
                                            </span>
                                            <span className="font-medium tabular-nums">{s.total}</span>
                                        </div>
                                        <div className="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                            <div
                                                className="h-full rounded-full bg-gradient-to-r from-primary/70 to-primary transition-all"
                                                style={{ width: `${(s.total / maxSportRegistrations) * 100}%` }}
                                            />
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-sm text-muted-foreground">No registrations yet.</p>
                        )}
                    </CardContent>
                </Card>
            </div>

            <div className="mt-8">
                <div className="mb-3 flex flex-wrap items-end justify-between gap-2">
                    <div>
                        <h2 className="text-lg font-semibold tracking-tight">Registration Overview</h2>
                        <p className="text-sm text-muted-foreground">Registrations across faculties and events</p>
                    </div>
                    {(regSportFilter || regFacultyFilter || regStatusFilter) && (
                        <button onClick={clearRegFilters} className="text-xs text-muted-foreground underline-offset-4 hover:underline">
                            Clear filters
                        </button>
                    )}
                </div>

                <div className="grid grid-cols-2 gap-3 md:grid-cols-5">
                    <Card><CardHeader className="py-3"><CardTitle className="text-2xl text-center tabular-nums">{registrationStats.totalRegistrations ?? 0}</CardTitle><p className="text-xs text-center text-muted-foreground">Total Registrations</p></CardHeader></Card>
                    <Card><CardHeader className="py-3"><CardTitle className="text-2xl text-center tabular-nums text-amber-600">{registrationStats.pending ?? 0}</CardTitle><p className="text-xs text-center text-muted-foreground">Pending</p></CardHeader></Card>
                    <Card><CardHeader className="py-3"><CardTitle className="text-2xl text-center tabular-nums text-emerald-600">{registrationStats.confirmed ?? 0}</CardTitle><p className="text-xs text-center text-muted-foreground">Confirmed</p></CardHeader></Card>
                    <Card><CardHeader className="py-3"><CardTitle className="text-2xl text-center tabular-nums">{registrationStats.totalFaculties ?? 0}</CardTitle><p className="text-xs text-center text-muted-foreground">Faculties</p></CardHeader></Card>
                    <Card><CardHeader className="py-3"><CardTitle className="text-2xl text-center tabular-nums">{registrationStats.totalEvents ?? 0}</CardTitle><p className="text-xs text-center text-muted-foreground">Events</p></CardHeader></Card>
                </div>

                <div className="mt-4 flex flex-wrap items-center gap-2">
                    <select aria-label="Filter registrations by sport" value={regSportFilter} onChange={(e) => handleRegFilterChange(setRegSportFilter)(e.target.value)}
                        className="h-9 rounded-md border border-input bg-background px-3 text-sm">
                        <option value="">All Sports</option>
                        {sports.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                    </select>
                    <select aria-label="Filter registrations by faculty" value={regFacultyFilter} onChange={(e) => handleRegFilterChange(setRegFacultyFilter)(e.target.value)}
                        className="h-9 rounded-md border border-input bg-background px-3 text-sm">
                        <option value="">All Faculties</option>
                        {faculties.map((f) => <option key={f.id} value={f.id}>{f.name}</option>)}
                    </select>
                    <select aria-label="Filter registrations by status" value={regStatusFilter} onChange={(e) => handleRegFilterChange(setRegStatusFilter)(e.target.value)}
                        className="h-9 rounded-md border border-input bg-background px-3 text-sm">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>

                <div className="mt-4 grid grid-cols-2 gap-3 md:grid-cols-4">
                    <Card><CardHeader className="py-3"><CardTitle className="text-2xl text-center tabular-nums">{squadTotal}</CardTitle><p className="text-xs text-center text-muted-foreground">Squad Members</p></CardHeader></Card>
                    <Card><CardHeader className="py-3"><CardTitle className="text-2xl text-center tabular-nums text-blue-600">{squadMale}</CardTitle><p className="text-xs text-center text-muted-foreground">Male Athletes</p></CardHeader></Card>
                    <Card><CardHeader className="py-3"><CardTitle className="text-2xl text-center tabular-nums text-pink-600">{squadFemale}</CardTitle><p className="text-xs text-center text-muted-foreground">Female Athletes</p></CardHeader></Card>
                    <Card><CardHeader className="py-3"><CardTitle className="text-2xl text-center tabular-nums text-purple-600">{squadOfficials}</CardTitle><p className="text-xs text-center text-muted-foreground">Officials</p></CardHeader></Card>
                </div>

                <div className="mt-4 grid gap-4 xl:grid-cols-2">
                    <Card>
                        <CardHeader><CardTitle className="text-sm">Per-Faculty Breakdown</CardTitle></CardHeader>
                        <CardContent className="p-0">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-xs text-muted-foreground">
                                        <th className="px-4 py-2 font-medium">Faculty</th>
                                        <th className="px-4 py-2 font-medium text-center">Total</th>
                                        <th className="px-4 py-2 font-medium text-center">Pending</th>
                                        <th className="px-4 py-2 font-medium text-center">Confirmed</th>
                                        <th className="px-4 py-2 font-medium text-center">Rejected</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {facultyStats.length === 0 && (
                                        <tr><td colSpan={5} className="px-4 py-6 text-center text-muted-foreground">No data.</td></tr>
                                    )}
                                    {facultyStats.map((f) => (
                                        <tr key={f.id} className="border-b last:border-0 hover:bg-muted/50">
                                            <td className="px-4 py-2 font-medium">{f.name}</td>
                                            <td className="px-4 py-2 text-center tabular-nums">{f.total}</td>
                                            <td className="px-4 py-2 text-center tabular-nums"><span className="text-amber-600">{f.pending}</span></td>
                                            <td className="px-4 py-2 text-center tabular-nums"><span className="text-emerald-600">{f.confirmed}</span></td>
                                            <td className="px-4 py-2 text-center tabular-nums"><span className="text-red-600">{f.rejected}</span></td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle className="text-sm">Per-Event Breakdown</CardTitle></CardHeader>
                        <CardContent className="p-0">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-xs text-muted-foreground">
                                        <th className="px-4 py-2 font-medium">Event</th>
                                        <th className="px-4 py-2 font-medium">Sport / Category</th>
                                        <th className="px-4 py-2 font-medium text-center">Registrations</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {eventStats.length === 0 && (
                                        <tr><td colSpan={3} className="px-4 py-6 text-center text-muted-foreground">No data.</td></tr>
                                    )}
                                    {eventStats.map((e) => (
                                        <tr key={e.id} className="border-b last:border-0 hover:bg-muted/50">
                                            <td className="px-4 py-2 font-medium">{e.name}</td>
                                            <td className="px-4 py-2 text-muted-foreground">{e.sport?.name} / {e.sportCategory?.name}</td>
                                            <td className="px-4 py-2 text-center tabular-nums">{e.total ?? 0}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </CardContent>
                        <Pagination paginator={eventStatsProp} />
                    </Card>
                </div>
            </div>

            <div className="mt-6 grid gap-4 xl:grid-cols-3">
                <Card className="xl:col-span-2">
                    <CardHeader className="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle>Upcoming Events</CardTitle>
                            <CardDescription>Next events on the calendar</CardDescription>
                        </div>
                        <Button asChild variant="ghost" size="sm">
                            <Link href={route('events.index')}>View all</Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {upcomingEventsSafe.length > 0 ? (
                            <div className="space-y-2">
                                {upcomingEventsSafe.map((e) => (
                                    <div key={e.id} className="flex items-center gap-3 rounded border p-2.5 text-sm">
                                        <span className="text-lg">{getSportIcon(e.sport?.name)}</span>
                                        <div className="min-w-0 flex-1">
                                            <div className="truncate font-medium">{e.name}</div>
                                            <div className="text-xs text-muted-foreground">
                                                {e.sport?.name}{e.sport_category?.name ? ` · ${e.sport_category.name}` : ''}
                                                {' · '}{e.tournament?.name}
                                            </div>
                                        </div>
                                        <div className="shrink-0 text-right">
                                            <div className="text-xs text-muted-foreground">{new Date(e.start_date).toLocaleDateString('ms-MY', { day: 'numeric', month: 'short', year: 'numeric' })}</div>
                                            <Badge variant="secondary" className="mt-0.5 text-xs">
                                                {e.registration_count} registered
                                            </Badge>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-sm text-muted-foreground">No upcoming events.</p>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Quick Actions</CardTitle>
                        <CardDescription>Common setup tasks</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {quickActions.map((a) => {
                            const Icon = a.icon;
                            return (
                                <Link
                                    key={a.label}
                                    href={route(a.href)}
                                    className="flex items-center gap-3 rounded-lg border p-3 text-sm transition hover:bg-accent"
                                >
                                    <div className={`flex size-9 shrink-0 items-center justify-center rounded-lg ${a.tone}`}>
                                        <Icon className="size-4" />
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <div className="font-medium">{a.label}</div>
                                        <div className="text-xs text-muted-foreground">{a.desc}</div>
                                    </div>
                                    <ArrowRight className="size-4 text-muted-foreground" />
                                </Link>
                            );
                        })}
                    </CardContent>
                </Card>
            </div>

            <div className="mt-6 grid gap-4 xl:grid-cols-2">
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle>Recent Sessions</CardTitle>
                            <CardDescription>Latest event cycles</CardDescription>
                        </div>
                        <Button asChild variant="ghost" size="sm">
                            <Link href={route('sessions.index')}>View all</Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {recentSessionsSafe.length > 0 ? (
                            <div className="space-y-2 text-sm">
                                {recentSessionsSafe.map((s) => (
                                    <div key={s.id} className="flex items-center justify-between rounded border p-2">
                                        <div>
                                            <div className="font-medium">{s.name}</div>
                                            <div className="text-xs text-muted-foreground">
                                                {new Date(s.start_date).toLocaleDateString('ms-MY', { day: 'numeric', month: 'short', year: 'numeric' })}
                                                {' — '}
                                                {s.end_date ? new Date(s.end_date).toLocaleDateString('ms-MY', { day: 'numeric', month: 'short', year: 'numeric' }) : 'ongoing'}
                                            </div>
                                        </div>
                                        <span className={s.is_active ? 'text-emerald-700' : 'text-muted-foreground'}>
                                            {s.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-sm text-muted-foreground">No recent sessions.</p>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle>Recent Tournaments</CardTitle>
                            <CardDescription>Latest competitions</CardDescription>
                        </div>
                        <Button asChild variant="ghost" size="sm">
                            <Link href={route('tournaments.index')}>View all</Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {recentTournamentsSafe.length > 0 ? (
                            <div className="space-y-2 text-sm">
                                {recentTournamentsSafe.map((t) => (
                                    <div key={t.id} className="flex items-center justify-between rounded border p-2">
                                        <div>
                                            <div className="font-medium">{t.name}</div>
                                            <div className="text-xs text-muted-foreground">
                                                {t.session?.name || '—'} • {new Date(t.start_date).toLocaleDateString('ms-MY', { day: 'numeric', month: 'short', year: 'numeric' })}
                                            </div>
                                        </div>
                                        <span className={t.is_active ? 'text-emerald-700' : 'text-muted-foreground'}>
                                            {t.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-sm text-muted-foreground">No recent tournaments.</p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
