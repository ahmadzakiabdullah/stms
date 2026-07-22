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
import { Head, Link, usePage } from '@inertiajs/react';
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
import FacultyDashboard from '@/Pages/Dashboard/FacultyDashboard';
import { type PageProps, type Session, type Tournament } from '@/types';

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

interface DashboardProps {
    stats?: BackendStats;
    recentSessions?: Session[];
    recentTournaments?: Tournament[];
    totalEventRegistrations?: number;
    participantsWithRegistrations?: number;
    upcomingEvents?: UpcomingEvent[];
    registrationsBySport?: SportCount[];
    isFacultyRep?: boolean;
    myRegistrations?: number;
    facultyRegistrations?: FacultyRegistration[];
    facultyMale?: number;
    facultyFemale?: number;
    facultyOfficials?: number;
}

const defaultStats: StatItem[] = [
    { label: 'Organizations', value: '—', note: 'Tenants in system', icon: Users },
    { label: 'Active Sessions', value: '—', note: 'Current event cycles', icon: CalendarClock },
    { label: 'Tournaments', value: '—', note: 'Active competitions', icon: Trophy },
    { label: 'Sports', value: '—', note: 'Configured sports', icon: Award },
    { label: 'Events', value: '—', note: 'Defined sub-competitions', icon: Target },
    { label: 'Matches', value: '—', note: 'Scheduled matches', icon: Scale },
    { label: 'Results', value: '—', note: 'Recorded results', icon: Trophy },
];

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
    isFacultyRep = false,
    myRegistrations = 0,
    facultyRegistrations = [],
    facultyMale = 0,
    facultyFemale = 0,
    facultyOfficials = 0,
}: DashboardProps) {
    const { auth, app } = usePage<PageProps>().props;
    const user = auth?.user;

    const valueKeys = ['organizations', 'activeSessions', 'tournaments', 'sports', 'events', 'matches', 'results'];
    const stats = defaultStats.map((item, index) => {
        const key = valueKeys[index];
        const value = backendStats[key] != null ? String(backendStats[key]) : item.value;
        return { ...item, value };
    });

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
                    facultyRegistrations={facultyRegistrations}
                    facultyMale={facultyMale}
                    facultyFemale={facultyFemale}
                    facultyOfficials={facultyOfficials}
                />
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <div className="text-sm text-muted-foreground">Welcome, {user.name}</div>
                        <h1 className="mt-1 text-2xl font-semibold tracking-tight">{app?.name || 'Dashboard'}</h1>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline">
                            <Link href={route('event-participants.index')}>
                                <ListChecks className="mr-2 size-4" />
                                Event Registrations
                            </Link>
                        </Button>
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
                {stats.map((item) => {
                    const Icon = item.icon;
                    return (
                        <Card key={item.label}>
                            <CardHeader className="flex flex-row items-center justify-between gap-3">
                                <div>
                                    <CardDescription>{item.label}</CardDescription>
                                    <CardTitle className="mt-2 text-3xl">{item.value}</CardTitle>
                                </div>
                                <div className="flex size-11 items-center justify-center rounded-lg bg-muted">
                                    <Icon className="size-5 text-muted-foreground" />
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="text-sm text-muted-foreground">{item.note}</div>
                            </CardContent>
                        </Card>
                    );
                })}

                {isFacultyRep ? (
                    <Card className="border-primary/30">
                        <CardHeader className="flex flex-row items-center justify-between gap-3">
                            <div>
                                <CardDescription>My Event Registrations</CardDescription>
                                <CardTitle className="mt-2 text-3xl">{myRegistrations}</CardTitle>
                            </div>
                            <div className="flex size-11 items-center justify-center rounded-lg bg-primary/10">
                                <ListChecks className="size-5 text-primary" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <Button asChild variant="link" className="h-auto p-0 text-sm">
                                <Link href={route('event-participants.index')}>
                                    Manage registrations <ArrowRight className="ml-1 size-3" />
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between gap-3">
                            <div>
                                <CardDescription>Event Registrations</CardDescription>
                                <CardTitle className="mt-2 text-3xl">{totalEventRegistrations}</CardTitle>
                            </div>
                            <div className="flex size-11 items-center justify-center rounded-lg bg-muted">
                                <ListChecks className="size-5 text-muted-foreground" />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="text-sm text-muted-foreground">
                                {participantsWithRegistrations} participants registered
                            </div>
                        </CardContent>
                    </Card>
                )}
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
                        {upcomingEvents.length > 0 ? (
                            <div className="space-y-2">
                                {upcomingEvents.map((e) => (
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
                        <CardTitle>Registrations by Sport</CardTitle>
                        <CardDescription>Top sports by registration count</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {registrationsBySport.length > 0 ? (
                            <div className="space-y-2">
                                {registrationsBySport.map((s) => (
                                    <div key={s.name} className="flex items-center justify-between">
                                        <div className="flex items-center gap-2">
                                            <span className="text-lg">{getSportIcon(s.name)}</span>
                                            <span className="text-sm">{s.name}</span>
                                        </div>
                                        <Badge variant="secondary">{s.total}</Badge>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-sm text-muted-foreground">No registrations yet.</p>
                        )}
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
                        {recentSessions.length > 0 ? (
                            <div className="space-y-2 text-sm">
                                {recentSessions.map((s) => (
                                    <div key={s.id} className="flex items-center justify-between rounded border p-2">
                                        <div>
                                            <div className="font-medium">{s.name}</div>
                                            <div className="text-xs text-muted-foreground">
                                                {new Date(s.start_date).toLocaleDateString('ms-MY', { day: 'numeric', month: 'short', year: 'numeric' })}
                                                {' — '}
                                                {s.end_date ? new Date(s.end_date).toLocaleDateString('ms-MY', { day: 'numeric', month: 'short', year: 'numeric' }) : 'ongoing'}
                                            </div>
                                        </div>
                                        <span className={s.is_active ? 'text-emerald-600' : 'text-muted-foreground'}>
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
                        {recentTournaments.length > 0 ? (
                            <div className="space-y-2 text-sm">
                                {recentTournaments.map((t) => (
                                    <div key={t.id} className="flex items-center justify-between rounded border p-2">
                                        <div>
                                            <div className="font-medium">{t.name}</div>
                                            <div className="text-xs text-muted-foreground">
                                                {t.session?.name || '—'} • {new Date(t.start_date).toLocaleDateString('ms-MY', { day: 'numeric', month: 'short', year: 'numeric' })}
                                            </div>
                                        </div>
                                        <span className={t.is_active ? 'text-emerald-600' : 'text-muted-foreground'}>
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
