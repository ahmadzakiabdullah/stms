import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { BarChart3, CalendarDays, Eye, Pencil, Plus, RefreshCw, Save, Search, Swords, Trash2, Trophy, Users } from 'lucide-react';
import { FormEvent, useEffect, useMemo, useState } from 'react';
import { eventCode, matchNumberLabel } from '@/lib/matchNumber';
import { matchProgress } from '@/lib/matchProgress';
import type { Event, Fixture, Participant, Pool, Result } from '@/types';

interface StandingRow {
    participant_id: string;
    played: number;
    won: number;
    drawn: number;
    lost: number;
    goals_for: number;
    goals_against: number;
    goal_difference: number;
    points: number;
    participant?: { id: string; name: string; team_name?: string; logo_url?: string } | null;
}

interface PoolWithRelations extends Pool {
    event_participants: (import('@/types').EventParticipant & { participant: Participant })[];
    fixtures: MatchRow[];
    standings: StandingRow[];
    has_standings: boolean;
}

interface MatchRow extends Fixture {
    home_participant?: Participant;
    away_participant?: Participant;
}

interface KnockoutData {
    has_stage: boolean;
    league_complete: boolean;
    event_slug?: string | null;
    fixtures: MatchRow[];
}

interface EventWithRelations extends Omit<Event, 'tournament' | 'sport'> {
    tournament?: { id: string; name: string };
    sport?: { id: string; name: string };
    sportCategory?: { id: string; name: string };
    pools_count?: number;
}

interface MatchesIndexProps {
    events: EventWithRelations[];
    drawnEventIds: string[];
    selectedEventId: string | null;
    pools: PoolWithRelations[];
    allFixtures: MatchRow[];
    knockout: KnockoutData;
    participants: Participant[];
    canManage?: boolean;
}

interface MatchForm {
    event_id: string;
    pool_id: string;
    round: number;
    match_number: number;
    home_participant_id: string;
    away_participant_id: string;
    venue: string;
    scheduled_at: string;
    status: Fixture['status'];
    notes: string;
}

type ParticipantSummary = Pick<Participant, 'id' | 'name'> & Partial<Pick<Participant, 'team_name' | 'logo_url'>>;

const participantName = (participant?: ParticipantSummary | null, fallback = 'TBD') => {
    if (!participant) return fallback;

    const code = participant.name?.trim();

    // Prefer the short code (e.g. FTKEK); fall back to the full team name
    // for long/individual names.
    if (code && code.length <= 12) return code;

    return participant.team_name || code || fallback;
};

const participantFullName = (participant?: ParticipantSummary | null, fallback = '') => {
    if (!participant) return fallback;
    return participant.team_name || participant.name || fallback;
};

const participantInitials = (name: string) =>
    name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() || '')
        .join('');

const formatDateTime = (value: string | null | undefined) =>
    value ? new Date(value).toLocaleString() : 'Time TBD';

function TeamMark({ participant, fallback = 'TBD', size = 'size-9' }: { participant?: ParticipantSummary | null; fallback?: string; size?: string }) {
    const name = participantName(participant, fallback);

    if (participant?.logo_url) {
        return <img src={participant.logo_url} alt={name} className={`${size} shrink-0 object-contain`} />;
    }

    return (
        <span className={`flex ${size} shrink-0 items-center justify-center rounded-md border bg-muted text-[10px] font-semibold text-muted-foreground`}>
            {participantInitials(name)}
        </span>
    );
}

function ParticipantIdentity({ participant, fallback = 'TBD' }: { participant?: ParticipantSummary | null; fallback?: string }) {
    const name = participantName(participant, fallback);

    return (
        <div className="flex items-center gap-2">
            <TeamMark participant={participant} fallback={fallback} size="size-12" />
            <span title={participantFullName(participant)}>{name}</span>
        </div>
    );
}

function StatCard({ label, value, tone }: { label: string; value: number; tone?: 'default' | 'emerald' | 'destructive' }) {
    const toneClass =
        tone === 'emerald' ? 'text-emerald-600 dark:text-emerald-400'
        : tone === 'destructive' ? 'text-destructive'
        : '';

    return (
        <Card>
            <CardContent className="p-4">
                <p className="text-xs uppercase tracking-wide text-muted-foreground">{label}</p>
                <p className={`mt-1 text-2xl font-bold tabular-nums ${toneClass}`}>{value}</p>
            </CardContent>
        </Card>
    );
}

function LeagueTable({ standings }: { standings: StandingRow[] }) {
    return (
        <div className="overflow-hidden rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow className="bg-muted/50">
                        <TableHead className="w-10">#</TableHead>
                        <TableHead>Team</TableHead>
                        <TableHead className="text-center">P</TableHead>
                        <TableHead className="text-center">W</TableHead>
                        <TableHead className="text-center">D</TableHead>
                        <TableHead className="text-center">L</TableHead>
                        <TableHead className="text-center">GF</TableHead>
                        <TableHead className="text-center">GA</TableHead>
                        <TableHead className="text-center">GD</TableHead>
                        <TableHead className="w-14 text-center font-semibold">Pts</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {standings.map((row, index) => {
                        const name = participantName(row.participant, 'Unknown');
                        const isLeader = index === 0 && row.points > 0;

                        return (
                            <TableRow key={row.participant_id} className={isLeader ? 'bg-emerald-50/60 dark:bg-emerald-950/20' : ''}>
                                <TableCell className="font-medium">{index + 1}</TableCell>
                                <TableCell>
                                    <div className="flex items-center gap-2">
                                        {row.participant?.logo_url && (
                                            <img src={row.participant.logo_url} alt={name} className="size-6 shrink-0 object-contain" />
                                        )}
                                        <span className="truncate font-medium">{name}</span>
                                        {isLeader && <Badge variant="secondary">Leader</Badge>}
                                    </div>
                                </TableCell>
                                <TableCell className="text-center">{row.played}</TableCell>
                                <TableCell className="text-center">{row.won}</TableCell>
                                <TableCell className="text-center">{row.drawn}</TableCell>
                                <TableCell className="text-center">{row.lost}</TableCell>
                                <TableCell className="text-center">{row.goals_for}</TableCell>
                                <TableCell className="text-center">{row.goals_against}</TableCell>
                                <TableCell className={`text-center ${row.goal_difference > 0 ? 'font-medium text-emerald-600 dark:text-emerald-400' : row.goal_difference < 0 ? 'text-destructive' : ''}`}>
                                    {row.goal_difference > 0 ? `+${row.goal_difference}` : row.goal_difference}
                                </TableCell>
                                <TableCell className="text-center font-semibold">{row.points}</TableCell>
                            </TableRow>
                        );
                    })}
                </TableBody>
            </Table>
        </div>
    );
}

const statusBadge = (status: string) => {
    const map: Record<string, { label: string; cls: string }> = {
        scheduled: { label: 'Scheduled', cls: 'bg-yellow-100 text-yellow-700' },
        in_progress: { label: 'In Progress', cls: 'bg-blue-100 text-blue-700' },
        completed: { label: 'Completed', cls: 'bg-emerald-100 text-emerald-700' },
        cancelled: { label: 'Cancelled', cls: 'bg-red-100 text-red-700' },
    };
    const item = map[status] || { label: status, cls: 'bg-gray-100 text-gray-600' };
    return <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${item.cls}`}>{item.label}</span>;
};

const stageTitle = (stage: string, round?: number | null) => {
    const map: Record<string, string> = {
        semi_final: `Semi-Final ${round ?? 1}`,
        bronze: 'Bronze · 3rd Place',
        final: 'Final',
    };
    return map[stage] || 'Knockout';
};

function KnockoutStageSection({ knockout, canManage = true }: { knockout: KnockoutData; canManage?: boolean }) {
    const [generating, setGenerating] = useState(false);
    const generate = () => {
        setGenerating(true);
        router.post(route('matches.generate-knockout', knockout.event_slug ?? ''), {}, {
            preserveScroll: true,
            onFinish: () => setGenerating(false),
        });
    };

    if (knockout.fixtures.length === 0) {
        return (
            <Card>
                <CardHeader className="pb-3">
                    <CardTitle className="flex items-center gap-2 text-lg"><Trophy className="size-4 text-primary" /> Knockout Stage</CardTitle>
                    <CardDescription>
                        {knockout.league_complete
                            ? 'League complete — generate the knockout stage to continue.'
                            : 'Not available yet. The knockout stage unlocks once every league fixture has a result.'}
                    </CardDescription>
                    {knockout.league_complete && canManage && (
                        <Button onClick={generate} disabled={generating} className="mt-2 w-fit">
                            <Trophy className="mr-2 size-4" /> {generating ? 'Generating…' : 'Generate Knockout Stage'}
                        </Button>
                    )}
                </CardHeader>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader className="pb-3">
                <CardTitle className="flex items-center gap-2 text-lg"><Trophy className="size-4 text-primary" /> Knockout Stage</CardTitle>
                <CardDescription>Semi-finals, bronze and final</CardDescription>
            </CardHeader>
            <CardContent>
                <div className="grid gap-3 sm:grid-cols-2">
                    {knockout.fixtures.map((fixture) => {
                        const scored = fixture.result?.score_home !== null && fixture.result?.score_home !== undefined;
                        const isFinal = fixture.stage === 'final';
                        const isBronze = fixture.stage === 'bronze';

                        return (
                            <div
                                key={fixture.id}
                                className={`rounded-lg border p-4 ${isFinal ? 'border-primary/40 bg-primary/5' : ''} ${isBronze ? 'border-amber-400/40 bg-amber-50/50 dark:bg-amber-950/20' : ''}`}
                            >
                                <div className="mb-3 flex items-center justify-between gap-2">
                                    <span className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                        {stageTitle(fixture.stage ?? 'semi_final', fixture.round)}
                                    </span>
                                    {statusBadge(fixture.status)}
                                </div>
                                <div className="flex items-center justify-center gap-3">
                                    <div className="flex min-w-0 flex-1 flex-col items-center gap-1 text-center">
                                        <TeamMark participant={fixture.home_participant} fallback="TBD" />
                                        <span className="truncate text-sm font-medium" title={participantFullName(fixture.home_participant)}>{participantName(fixture.home_participant)}</span>
                                    </div>
                                    <span className={`shrink-0 text-sm font-bold ${isFinal ? 'text-primary' : 'text-muted-foreground'}`}>
                                        {scored ? `${fixture.result!.score_home} : ${fixture.result!.score_away}` : 'VS'}
                                    </span>
                                    <div className="flex min-w-0 flex-1 flex-col items-center gap-1 text-center">
                                        <TeamMark participant={fixture.away_participant} fallback="TBD" />
                                        <span className="truncate text-sm font-medium" title={participantFullName(fixture.away_participant)}>{participantName(fixture.away_participant)}</span>
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>
            </CardContent>
        </Card>
    );
}

const toDateTimeInput = (value: string | null) => (value ? value.slice(0, 16) : '');

interface MatchRowViewProps {
    match: MatchRow;
    onEdit: () => void;
    onDelete: () => void;
    eventCode?: string;
    canManage?: boolean;
}

function MatchRowView({ match, onEdit, onDelete, eventCode: code = '', canManage = true }: MatchRowViewProps) {
    const scored = match.result?.score_home !== null && match.result?.score_home !== undefined;
    const detail = match.pool?.name ?? (match.stage ? stageTitle(match.stage, match.round) : `Round ${match.round || 1}`);
    const label = code
        ? `${code}${match.match_number}`
        : matchNumberLabel(match.match_number, match.event?.name);

    return (
        <TableRow key={match.id}>
            <TableCell className="w-14 font-medium text-muted-foreground">#{label}</TableCell>
            <TableCell>
                <div className="flex min-w-[260px] items-center gap-2">
                    <span className="max-w-[110px] truncate font-medium text-right" title={participantFullName(match.home_participant)}>{participantName(match.home_participant)}</span>
                    <TeamMark participant={match.home_participant} size="size-12" />
                    <span className={`mx-1 shrink-0 rounded-md px-2 py-0.5 text-sm font-bold tabular-nums ${scored ? 'bg-muted' : 'text-muted-foreground'}`}>
                        {scored ? `${match.result!.score_home} : ${match.result!.score_away}` : 'VS'}
                    </span>
                    <TeamMark participant={match.away_participant} size="size-12" />
                    <span className="max-w-[110px] truncate font-medium" title={participantFullName(match.away_participant)}>{participantName(match.away_participant)}</span>
                </div>
            </TableCell>
            <TableCell className="text-sm text-muted-foreground">{detail}</TableCell>
            <TableCell className="text-sm">
                <div className="flex items-center gap-1.5 text-muted-foreground">
                    <CalendarDays className="size-3" />
                    {match.venue || 'Venue TBD'}
                </div>
                <div className="text-xs text-muted-foreground">{formatDateTime(match.scheduled_at)}</div>
            </TableCell>
            <TableCell>{statusBadge(match.status)}</TableCell>
            {canManage && (
            <TableCell className="space-x-1 text-right">
                <Button variant="outline" size="icon-sm" onClick={onEdit} aria-label="Edit match"><Pencil className="size-3" /></Button>
                <Button variant="destructive" size="icon-sm" onClick={onDelete} aria-label="Delete match"><Trash2 className="size-3" /></Button>
            </TableCell>
            )}
        </TableRow>
    );
}

export default function MatchesIndex({ events, drawnEventIds, selectedEventId, pools, allFixtures, knockout, participants, canManage = true }: MatchesIndexProps) {
    const { flash } = usePage().props;
    const [query, setQuery] = useState('');
    const [statusFilter, setStatusFilter] = useState('');
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editingMatch, setEditingMatch] = useState<MatchRow | null>(null);
    const [deleteMatch, setDeleteMatch] = useState<MatchRow | null>(null);
    const [lastUpdated, setLastUpdated] = useState<Date | null>(null);
    const fixtures = useMemo(() => (Array.isArray(allFixtures) ? allFixtures : []), [allFixtures]);
    const selectedEvent = events.find((event) => event.id === selectedEventId);

    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm<MatchForm>({
        event_id: selectedEventId || '',
        pool_id: '',
        round: 1,
        match_number: 1,
        home_participant_id: '',
        away_participant_id: '',
        venue: '',
        scheduled_at: '',
        status: 'scheduled',
        notes: '',
    });

    const filteredFixtures = useMemo(() => {
        const q = query.trim().toLowerCase();

        return fixtures.filter((match) => {
            if (statusFilter && match.status !== statusFilter) return false;
            if (!q) return true;

            const haystack = [
                String(match.match_number),
                matchNumberLabel(match.match_number, match.event?.name),
                match.venue || '',
                participantFullName(match.home_participant),
                participantFullName(match.away_participant),
                match.event?.name || '',
                match.pool?.name || '',
            ].join(' ').toLowerCase();

            return haystack.includes(q);
        });
    }, [fixtures, query, statusFilter]);

    const groupedByEvent = useMemo(
        () =>
            events
                .map((event) => ({
                    event,
                    fixtures: fixtures.filter((match) => match.event_id === event.id),
                }))
                .filter((group) => group.fixtures.length > 0),
        [events, fixtures]
    );

    const counts = useMemo(() => {
        const c: Record<MatchRow['status'], number> = { scheduled: 0, in_progress: 0, completed: 0, cancelled: 0 };
        for (const match of fixtures) c[match.status]++;
        return c;
    }, [fixtures]);

    const handleFilterChange = (eventId: string) => {
        const event = events.find((item) => item.id === eventId);
        router.get(route('matches.index'), event ? { event: event.slug } : {}, {
            preserveScroll: true,
        });
    };

    const openCreate = (pool?: PoolWithRelations) => {
        const eventId = pool?.event_id ?? selectedEventId ?? '';
        const nextMatchNumber = Math.max(0, ...fixtures.filter((f) => f.event_id === eventId).map((f) => f.match_number)) + 1;
        setEditingMatch(null);
        clearErrors();
        setData({
            event_id: eventId,
            pool_id: pool?.id || '',
            round: Math.max(1, ...(pool?.fixtures ?? []).map((fixture) => fixture.round || 1)),
            match_number: nextMatchNumber,
            home_participant_id: '',
            away_participant_id: '',
            venue: '',
            scheduled_at: '',
            status: 'scheduled',
            notes: '',
        });
        setDialogOpen(true);
    };

    const openEdit = (match: MatchRow) => {
        setEditingMatch(match);
        clearErrors();
        setData({
            event_id: match.event_id,
            pool_id: match.pool_id || '',
            round: match.round || 1,
            match_number: match.match_number,
            home_participant_id: match.home_participant_id || '',
            away_participant_id: match.away_participant_id || '',
            venue: match.venue || '',
            scheduled_at: toDateTimeInput(match.scheduled_at),
            status: match.status,
            notes: match.notes || '',
        });
        setDialogOpen(true);
    };

    const closeDialog = () => {
        setDialogOpen(false);
        setEditingMatch(null);
        clearErrors();
        reset();
    };

    const submitMatch = (event: FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: closeDialog };
        if (editingMatch) {
            put(route('matches.update', editingMatch.id), options);
        } else {
            post(route('matches.store'), options);
        }
    };

    const confirmDelete = () => {
        if (!deleteMatch) return;
        router.delete(route('matches.destroy', deleteMatch.id), {
            preserveScroll: true,
            onSuccess: () => setDeleteMatch(null),
        });
    };

    const refreshData = () => {
        router.reload({
            only: ['pools', 'allFixtures', 'knockout'],
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => setLastUpdated(new Date()),
        });
    };

    useEffect(() => {
        const interval = setInterval(() => {
            if (document.hidden || dialogOpen || processing || deleteMatch) return;
            refreshData();
        }, 15000);

        return () => clearInterval(interval);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [dialogOpen, processing, deleteMatch]);

    const eventPools = pools.filter((pool) => pool.event_id === data.event_id);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Matches</h1>
                        <p className="text-sm text-muted-foreground">Browse and manage all fixtures across every event.</p>
                    </div>
                    {canManage && (
                    <Button onClick={() => openCreate()} disabled={!selectedEventId}>
                        <Plus className="mr-2 size-4" /> Add Match
                    </Button>
                    )}
                </div>
            }
        >
            <Head title="Matches" />

            {flash?.success && <div className="mb-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-700">{flash.success}</div>}
            {flash?.error && <div className="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700">{flash.error}</div>}

            <div className="mb-4 flex flex-wrap items-center gap-2">
                <Button
                    variant={!selectedEventId ? 'default' : 'outline'}
                    size="sm"
                    onClick={() => handleFilterChange('')}
                >
                    All Matches
                    <span className="ml-1.5 rounded-full bg-muted px-1.5 py-0.5 text-xs font-semibold tabular-nums">{fixtures.length}</span>
                </Button>
                {events.map((event) => {
                    const count = fixtures.filter((match) => match.event_id === event.id).length;
                    if (count === 0 && !drawnEventIds.includes(event.id)) return null;

                    const completed = fixtures.filter((match) => match.event_id === event.id && match.status === 'completed').length;
                    const progress = drawnEventIds.includes(event.id) ? matchProgress(count, completed) : null;

                    return (
                        <Button
                            key={event.id}
                            variant={selectedEventId === event.id ? 'default' : 'outline'}
                            size="sm"
                            onClick={() => handleFilterChange(event.id)}
                        >
                            {event.name}
                            {progress && <span className={`ml-1.5 size-1.5 rounded-full ${progress.bar}`} />}
                            <span className="ml-1.5 rounded-full bg-muted px-1.5 py-0.5 text-xs font-semibold tabular-nums">{count}</span>
                        </Button>
                    );
                })}
            </div>

            {!selectedEvent ? (
                <>
                    <div className="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-5">
                        <StatCard label="Total Matches" value={counts.scheduled + counts.in_progress + counts.completed + counts.cancelled} />
                        <StatCard label="Completed" value={counts.completed} tone="emerald" />
                        <StatCard label="In Progress" value={counts.in_progress} />
                        <StatCard label="Scheduled" value={counts.scheduled} />
                        <StatCard label="Cancelled" value={counts.cancelled} tone="destructive" />
                    </div>

                    <div className="mb-4 flex flex-wrap items-center gap-3">
                        <div className="relative">
                            <Search className="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                placeholder="Search team, venue, match #…"
                                value={query}
                                onChange={(event) => setQuery(event.target.value)}
                                className="w-72 pl-8"
                            />
                        </div>
                        <select
                            value={statusFilter}
                            onChange={(event) => setStatusFilter(event.target.value)}
                            className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="">All Statuses</option>
                            <option value="scheduled">Scheduled</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <span className="text-sm text-muted-foreground">
                            Showing {filteredFixtures.length} of {fixtures.length} matches
                        </span>
                    </div>

                    <div className="space-y-6">
                        {groupedByEvent.length === 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>No Matches</CardTitle>
                                    <CardDescription>
                                        {query || statusFilter
                                            ? 'No matches match your search or filters.'
                                            : 'No matches scheduled yet. Select an event above and add a match.'}
                                    </CardDescription>
                                </CardHeader>
                            </Card>
                        )}

                        {groupedByEvent.map(({ event, fixtures: eventFixtures }) => {
                            const shown = eventFixtures.filter((match) => filteredFixtures.includes(match));
                            if (shown.length === 0) return null;

                            const completed = eventFixtures.filter((match) => match.status === 'completed').length;
                            const eventHasDraw = drawnEventIds.includes(event.id);
                            const progress = matchProgress(eventFixtures.length, completed);

                            return (
                                <Card key={event.id}>
                                    <CardHeader className="pb-3">
                                        <div className="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <CardTitle className="flex items-center gap-2 text-lg">
                                                    {event.name}
                                                    {eventHasDraw && <Badge variant="outline">Drawn</Badge>}
                                                    <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${progress.badge}`}>{progress.label}</span>
                                                </CardTitle>
                                                <CardDescription>
                                                    {event.tournament?.name} · {event.sport?.name}
                                                    {event.sportCategory && ` — ${event.sportCategory.name}`}
                                                    <span className="mx-1.5">·</span>
                                                    {eventFixtures.length} matches
                                                    <span className="ml-2 inline-flex items-center gap-1.5 align-middle">
                                                        <span className="inline-block h-1 w-16 overflow-hidden rounded-full bg-gray-200">
                                                            <span className={`block h-full ${progress.bar}`} style={{ width: `${progress.pct}%` }} />
                                                        </span>
                                                        <span className="text-[10px] tabular-nums">{completed}/{eventFixtures.length}</span>
                                                    </span>
                                                </CardDescription>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                {eventHasDraw && (
                                                    <Link href={route('events.draw-result', event.slug)}>
                                                        <Button variant="outline" size="sm"><Eye className="mr-1 size-3" /> View Draw</Button>
                                                    </Link>
                                                )}
                                                <Button variant="outline" size="sm" onClick={() => handleFilterChange(event.id)}>
                                                    <Swords className="mr-1 size-3" /> Open Event
                                                </Button>
                                            </div>
                                        </div>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="overflow-x-auto">
                                            <Table>
                                                <TableHeader>
                                                    <TableRow>
                                                        <TableHead className="w-14">#</TableHead>
                                                        <TableHead>Matchup</TableHead>
                                                        <TableHead className="w-32">Pool / Stage</TableHead>
                                                        <TableHead>Venue / Time</TableHead>
                                                <TableHead className="w-28">Status</TableHead>
                                                {canManage && <TableHead className="text-right">Actions</TableHead>}
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {shown.map((match) => (
                                                <MatchRowView
                                                    key={match.id}
                                                    match={match}
                                                    onEdit={() => openEdit(match)}
                                                    onDelete={() => setDeleteMatch(match)}
                                                    canManage={canManage}
                                                />
                                            ))}
                                                </TableBody>
                                            </Table>
                                        </div>
                                        {shown.length < eventFixtures.length && (
                                            <p className="mt-3 text-xs text-muted-foreground">
                                                Showing {shown.length} of {eventFixtures.length} matches.
                                            </p>
                                        )}
                                    </CardContent>
                                </Card>
                            );
                        })}
                    </div>
                </>
            ) : (
                <div className="space-y-6">
                    <Card>
                        <CardHeader className="pb-3">
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <CardTitle className="flex items-center gap-2 text-lg">
                                        {selectedEvent.name}
                                        {drawnEventIds.includes(selectedEvent.id) && <Badge variant="outline">Drawn</Badge>}
                                    </CardTitle>
                                    <CardDescription>
                                        {selectedEvent.tournament?.name} · {selectedEvent.sport?.name}
                                        {selectedEvent.sportCategory && ` — ${selectedEvent.sportCategory.name}`}
                                        {pools.length > 0 && ` · ${pools.length} Pool${pools.length > 1 ? 's' : ''}`}
                                    </CardDescription>
                                </div>
                                <div className="flex items-center gap-2">
                                    <span className="flex items-center gap-1.5 text-xs text-muted-foreground">
                                        <RefreshCw className="size-3 animate-spin [animation-duration:3s]" />
                                        {lastUpdated ? `Auto-updated ${lastUpdated.toLocaleTimeString()}` : 'Auto-updates every 15s'}
                                    </span>
                                    {drawnEventIds.includes(selectedEvent.id) && (
                                        <Link href={route('events.draw-result', selectedEvent.slug)}>
                                            <Button variant="outline" size="sm"><Eye className="mr-1 size-3" /> View Draw</Button>
                                        </Link>
                                    )}
                                </div>
                            </div>
                        </CardHeader>
                    </Card>

                    {knockout.league_complete && <KnockoutStageSection knockout={knockout} canManage={canManage} />}
                    {pools.length === 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle>No Pools</CardTitle>
                                <CardDescription>
                                    This event has no pools yet. You can still add matches directly, or run a draw to create pools.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="flex flex-wrap gap-2">
                                    {canManage && (
                                    <Button variant="outline" size="sm" onClick={() => openCreate()}>
                                        <Plus className="mr-1 size-3" /> Add Match
                                    </Button>
                                    )}
                                    <Link href={route('events.draw-result', selectedEvent.slug)}>
                                        <Button variant="outline" size="sm"><Eye className="mr-1 size-3" /> View Draw</Button>
                                    </Link>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {pools.map((pool) => (
                        <Card key={pool.id}>
                            <CardHeader className="pb-3">
                                <div className="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <CardTitle className="flex items-center gap-2 text-lg"><Users className="size-4" /> {pool.name}</CardTitle>
                                        <CardDescription>{pool.event_participants.length} participants · {pool.fixtures.length} fixtures</CardDescription>
                                    </div>
                                    {canManage && (
                                    <Button variant="outline" size="sm" onClick={() => openCreate(pool)}>
                                        <Plus className="mr-1 size-3" /> Add to {pool.name}
                                    </Button>
                                    )}
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-5">
                                    <div className="flex flex-wrap gap-2">
                                        {pool.event_participants.map((entry) => (
                                            <div key={entry.id} className="rounded-md border px-3 py-2 text-sm">
                                                <ParticipantIdentity participant={entry.participant} fallback="Unknown" />
                                            </div>
                                        ))}
                                    </div>

                                {pool.has_standings && (
                                    <div className="space-y-2">
                                        <div className="flex items-center gap-2 text-sm font-semibold">
                                            <BarChart3 className="size-4 text-primary" />
                                            League Table
                                        </div>
                                        <LeagueTable standings={pool.standings} />
                                    </div>
                                )}

                                <div className="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead className="w-14">#</TableHead>
                                                <TableHead>Matchup</TableHead>
                                                <TableHead className="w-32">Pool / Stage</TableHead>
                                                <TableHead>Venue / Time</TableHead>
                                                <TableHead className="w-28">Status</TableHead>
                                                {canManage && <TableHead className="text-right">Actions</TableHead>}
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {pool.fixtures.length === 0 && (
                                                <TableRow><TableCell colSpan={canManage ? 6 : 5} className="text-center text-muted-foreground">No fixtures in this pool.</TableCell></TableRow>
                                            )}
                                            {pool.fixtures.map((fixture) => (
                                                <MatchRowView
                                                    key={fixture.id}
                                                    match={fixture}
                                                    eventCode={eventCode(selectedEvent.name)}
                                                    onEdit={() => openEdit(fixture)}
                                                    onDelete={() => setDeleteMatch(fixture)}
                                                    canManage={canManage}
                                                />
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            )}

            <Dialog open={dialogOpen} onOpenChange={(open) => open ? setDialogOpen(true) : closeDialog()}>
                <DialogContent className="max-h-[90vh] max-w-2xl overflow-y-auto">
                    <form onSubmit={submitMatch}>
                        <DialogHeader>
                            <DialogTitle>{editingMatch ? 'Edit Match' : 'Add Match'}</DialogTitle>
                            <DialogDescription>Set teams, pool, round, venue, schedule and status.</DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-4 py-5 sm:grid-cols-2">
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="event_id">Event</Label>
                                <select id="event_id" value={data.event_id} onChange={(event) => setData('event_id', event.target.value)} className="h-9 rounded-md border bg-background px-3 text-sm" required>
                                    <option value="">-- Select Event --</option>
                                    {events.map((event) => <option key={event.id} value={event.id}>{event.name}</option>)}
                                </select>
                                {errors.event_id && <p className="text-sm text-destructive">{errors.event_id}</p>}
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="pool_id">Pool</Label>
                                <select id="pool_id" value={data.pool_id} onChange={(event) => setData('pool_id', event.target.value)} className="h-9 rounded-md border bg-background px-3 text-sm">
                                    <option value="">-- No Pool --</option>
                                    {eventPools.map((pool) => <option key={pool.id} value={pool.id}>{pool.name}</option>)}
                                </select>
                                {errors.pool_id && <p className="text-sm text-destructive">{errors.pool_id}</p>}
                            </div>
                            <div className="grid grid-cols-2 gap-3">
                                <div className="grid gap-2"><Label htmlFor="round">Round</Label><Input id="round" type="number" min="1" value={data.round} onChange={(event) => setData('round', Number(event.target.value))} /></div>
                                <div className="grid gap-2"><Label htmlFor="match_number">Match #</Label><Input id="match_number" type="number" min="1" value={data.match_number} onChange={(event) => setData('match_number', Number(event.target.value))} required /></div>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="home_participant_id">Home</Label>
                                <select id="home_participant_id" value={data.home_participant_id} onChange={(event) => setData('home_participant_id', event.target.value)} className="h-9 rounded-md border bg-background px-3 text-sm"><option value="">-- TBD --</option>{participants.map((participant) => <option key={participant.id} value={participant.id}>{participantName(participant)}</option>)}</select>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="away_participant_id">Away</Label>
                                <select id="away_participant_id" value={data.away_participant_id} onChange={(event) => setData('away_participant_id', event.target.value)} className="h-9 rounded-md border bg-background px-3 text-sm"><option value="">-- TBD --</option>{participants.map((participant) => <option key={participant.id} value={participant.id}>{participantName(participant)}</option>)}</select>
                                {errors.away_participant_id && <p className="text-sm text-destructive">{errors.away_participant_id}</p>}
                            </div>
                            <div className="grid gap-2"><Label htmlFor="venue">Venue</Label><Input id="venue" value={data.venue} onChange={(event) => setData('venue', event.target.value)} /></div>
                            <div className="grid gap-2"><Label htmlFor="scheduled_at">Scheduled At</Label><Input id="scheduled_at" type="datetime-local" value={data.scheduled_at} onChange={(event) => setData('scheduled_at', event.target.value)} /></div>
                            <div className="grid gap-2"><Label htmlFor="status">Status</Label><select id="status" value={data.status} onChange={(event) => setData('status', event.target.value as Fixture['status'])} className="h-9 rounded-md border bg-background px-3 text-sm"><option value="scheduled">Scheduled</option><option value="in_progress">In Progress</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select></div>
                            <div className="grid gap-2"><Label htmlFor="notes">Notes</Label><Input id="notes" value={data.notes} onChange={(event) => setData('notes', event.target.value)} /></div>
                        </div>
                        <DialogFooter><Button type="button" variant="outline" onClick={closeDialog}>Cancel</Button><Button type="submit" disabled={processing}><Save className="mr-2 size-4" />{editingMatch ? 'Update Match' : 'Save Match'}</Button></DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={!!deleteMatch} onOpenChange={(open) => !open && setDeleteMatch(null)}>
                <DialogContent>
                    <DialogHeader><DialogTitle>Delete Match?</DialogTitle><DialogDescription>Match #{matchNumberLabel(deleteMatch?.match_number, deleteMatch?.event?.name ?? selectedEvent?.name)} will be removed. This action cannot be undone.</DialogDescription></DialogHeader>
                    <DialogFooter><Button variant="outline" onClick={() => setDeleteMatch(null)}>Cancel</Button><Button variant="destructive" onClick={confirmDelete}><Trash2 className="mr-2 size-4" />Delete Match</Button></DialogFooter>
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}
