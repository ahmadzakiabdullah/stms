import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Pagination from '@/components/Pagination';
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
import { Eye, Pencil, Plus, Save, Swords, Trash2, Users } from 'lucide-react';
import { FormEvent, useState } from 'react';
import type { Event, Fixture, Paginated, Participant, Pool } from '@/types';

interface PoolWithRelations extends Pool {
    event_participants: (import('@/types').EventParticipant & { participant: Participant })[];
    fixtures: MatchRow[];
}

interface MatchRow extends Fixture {
    home_participant?: Participant;
    away_participant?: Participant;
}

interface EventWithRelations extends Event {
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
    allFixtures: Paginated<MatchRow> | MatchRow[];
    participants: Participant[];
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

const participantName = (participant?: Participant, fallback = 'TBD') =>
    participant?.team_name || participant?.name || fallback;

const participantInitials = (name: string) =>
    name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() || '')
        .join('');

function TeamMark({ participant, fallback = 'TBD' }: { participant?: Participant; fallback?: string }) {
    const name = participantName(participant, fallback);

    if (participant?.logo_url) {
        return <img src={participant.logo_url} alt={name} className="size-9 shrink-0 object-contain" />;
    }

    return (
        <span className="flex size-9 shrink-0 items-center justify-center rounded-md border bg-muted text-[10px] font-semibold text-muted-foreground">
            {participantInitials(name)}
        </span>
    );
}

function ParticipantIdentity({ participant, fallback = 'TBD' }: { participant?: Participant; fallback?: string }) {
    const name = participantName(participant, fallback);

    return (
        <div className="flex items-center gap-2">
            <TeamMark participant={participant} fallback={fallback} />
            <span>{name}</span>
        </div>
    );
}

function Matchup({ home, away }: { home?: Participant; away?: Participant }) {
    return (
        <div className="grid min-w-[360px] grid-cols-[minmax(90px,1fr)_36px_32px_36px_minmax(90px,1fr)] items-center gap-2">
            <span className="truncate text-right font-medium">{participantName(home)}</span>
            <TeamMark participant={home} />
            <span className="text-center text-xs font-bold text-muted-foreground">VS</span>
            <TeamMark participant={away} />
            <span className="truncate font-medium">{participantName(away)}</span>
        </div>
    );
}

const statusBadge = (status: string) => {
    const map: Record<string, { label: string; variant: 'default' | 'secondary' | 'outline' | 'destructive' }> = {
        scheduled: { label: 'Scheduled', variant: 'outline' },
        in_progress: { label: 'In Progress', variant: 'default' },
        completed: { label: 'Completed', variant: 'secondary' },
        cancelled: { label: 'Cancelled', variant: 'destructive' },
    };
    const item = map[status] || { label: status, variant: 'outline' };
    return <Badge variant={item.variant}>{item.label}</Badge>;
};

const toDateTimeInput = (value: string | null) => (value ? value.slice(0, 16) : '');

export default function MatchesIndex({ events, drawnEventIds, selectedEventId, nextMatchNumber, pools, allFixtures: allFixturesProp, participants }: MatchesIndexProps) {
    const { flash } = usePage().props;
    const [filterEventId, setFilterEventId] = useState(selectedEventId || '');
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editingMatch, setEditingMatch] = useState<MatchRow | null>(null);
    const [deleteMatch, setDeleteMatch] = useState<MatchRow | null>(null);
    const fixtures = Array.isArray(allFixturesProp) ? allFixturesProp : (allFixturesProp?.data ?? []);
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

    const handleFilterChange = (eventId: string) => {
        setFilterEventId(eventId);
        const event = events.find((item) => item.id === eventId);
        router.get(route('matches.index'), event ? { event: event.slug } : {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const openCreate = (pool?: PoolWithRelations) => {
        const poolFixtures = pool?.fixtures ?? [];
        const nextMatchNumber = Math.max(0, ...pools.flatMap((item) => item.fixtures.map((fixture) => fixture.match_number))) + 1;
        setEditingMatch(null);
        clearErrors();
        setData({
            event_id: selectedEventId || '',
            pool_id: pool?.id || '',
            round: Math.max(1, ...poolFixtures.map((fixture) => fixture.round || 1)),
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

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Matches</h1>
                        <p className="text-sm text-muted-foreground">Browse and manage fixtures by event and pool.</p>
                    </div>
                    <Button onClick={() => openCreate()} disabled={!selectedEventId}>
                        <Plus className="mr-2 size-4" /> Add Match
                    </Button>
                </div>
            }
        >
            <Head title="Matches" />

            {flash?.success && <div className="mb-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-700">{flash.success}</div>}
            {flash?.error && <div className="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700">{flash.error}</div>}

            <div className="mb-6 flex flex-wrap items-center gap-4">
                <select
                    className="flex h-9 w-80 rounded-md border border-input bg-background px-3 py-1 text-sm"
                    value={filterEventId}
                    onChange={(event) => handleFilterChange(event.target.value)}
                >
                    <option value="">-- All Events --</option>
                    {events.map((event) => (
                        <option key={event.id} value={event.id}>
                            {event.name} {drawnEventIds.includes(event.id) ? '• Drawn' : ''}
                        </option>
                    ))}
                </select>
                {!selectedEventId && <span className="text-sm text-muted-foreground">Select an event to enable Add Match.</span>}
            </div>

            {selectedEvent && pools.length > 0 ? (
                <div className="space-y-6">
                    <Card>
                        <CardHeader className="pb-3">
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <CardTitle>{selectedEvent.name}</CardTitle>
                                    <CardDescription>
                                        {selectedEvent.tournament?.name} · {selectedEvent.sport?.name}
                                        {selectedEvent.sportCategory && ` — ${selectedEvent.sportCategory.name}`} · {pools.length} Pool
                                    </CardDescription>
                                </div>
                                <Link href={route('events.draw-result', selectedEvent.slug)}>
                                    <Button variant="outline" size="sm"><Eye className="mr-1 size-3" /> View Draw</Button>
                                </Link>
                            </div>
                        </CardHeader>
                    </Card>

                    {pools.map((pool) => (
                        <Card key={pool.id}>
                            <CardHeader className="pb-3">
                                <div className="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <CardTitle className="flex items-center gap-2 text-lg"><Users className="size-4" /> {pool.name}</CardTitle>
                                        <CardDescription>{pool.event_participants.length} participants · {pool.fixtures.length} fixtures</CardDescription>
                                    </div>
                                    <Button variant="outline" size="sm" onClick={() => openCreate(pool)}>
                                        <Plus className="mr-1 size-3" /> Add to {pool.name}
                                    </Button>
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

                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-14">#</TableHead>
                                            <TableHead className="w-16">Round</TableHead>
                                            <TableHead>Matchup</TableHead>
                                            <TableHead>Venue / Time</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead className="text-right">Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {pool.fixtures.length === 0 && (
                                            <TableRow><TableCell colSpan={6} className="text-center text-muted-foreground">No fixtures in this pool.</TableCell></TableRow>
                                        )}
                                        {pool.fixtures.map((fixture) => (
                                            <TableRow key={fixture.id}>
                                                <TableCell>{fixture.match_number}</TableCell>
                                                <TableCell>R{fixture.round || 1}</TableCell>
                                                <TableCell><Matchup home={fixture.home_participant} away={fixture.away_participant} /></TableCell>
                                                <TableCell className="text-sm">
                                                    <div>{fixture.venue || 'Venue TBD'}</div>
                                                    <div className="text-muted-foreground">{fixture.scheduled_at ? new Date(fixture.scheduled_at).toLocaleString() : 'Time TBD'}</div>
                                                </TableCell>
                                                <TableCell>{statusBadge(fixture.status)}</TableCell>
                                                <TableCell className="space-x-1 text-right">
                                                    <Button variant="outline" size="icon-sm" onClick={() => openEdit(fixture)} aria-label="Edit match"><Pencil className="size-3" /></Button>
                                                    <Button variant="destructive" size="icon-sm" onClick={() => setDeleteMatch(fixture)} aria-label="Delete match"><Trash2 className="size-3" /></Button>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            ) : (
                <Card>
                    <CardHeader>
                        <CardTitle>All Matches</CardTitle>
                        <CardDescription>{selectedEventId ? 'This event has no pools yet.' : 'Select an event to view and manage its pool fixtures.'}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader><TableRow><TableHead>#</TableHead><TableHead>Event</TableHead><TableHead>Matchup</TableHead><TableHead>Venue / Time</TableHead><TableHead>Status</TableHead><TableHead className="text-right">Actions</TableHead></TableRow></TableHeader>
                            <TableBody>
                                {fixtures.length === 0 && <TableRow><TableCell colSpan={6} className="text-center text-muted-foreground">No matches scheduled yet.</TableCell></TableRow>}
                                {fixtures.map((match) => (
                                    <TableRow key={match.id}>
                                        <TableCell>{match.match_number}</TableCell>
                                        <TableCell>{match.event?.name || '-'}</TableCell>
                                        <TableCell><Matchup home={match.home_participant} away={match.away_participant} /></TableCell>
                                        <TableCell className="text-sm"><div>{match.venue || 'Venue TBD'}</div><div className="text-muted-foreground">{match.scheduled_at ? new Date(match.scheduled_at).toLocaleString() : 'Time TBD'}</div></TableCell>
                                        <TableCell>{statusBadge(match.status)}</TableCell>
                                        <TableCell className="space-x-1 text-right">
                                            <Button variant="outline" size="icon-sm" onClick={() => openEdit(match)} aria-label="Edit match"><Pencil className="size-3" /></Button>
                                            <Button variant="destructive" size="icon-sm" onClick={() => setDeleteMatch(match)} aria-label="Delete match"><Trash2 className="size-3" /></Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                        {!Array.isArray(allFixturesProp) && allFixturesProp.links && <div className="mt-4"><Pagination links={allFixturesProp.links} /></div>}
                    </CardContent>
                </Card>
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
                                    {pools.map((pool) => <option key={pool.id} value={pool.id}>{pool.name}</option>)}
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
                    <DialogHeader><DialogTitle>Delete Match?</DialogTitle><DialogDescription>Match #{deleteMatch?.match_number} will be removed. This action cannot be undone.</DialogDescription></DialogHeader>
                    <DialogFooter><Button variant="outline" onClick={() => setDeleteMatch(null)}>Cancel</Button><Button variant="destructive" onClick={confirmDelete}><Trash2 className="mr-2 size-4" />Delete Match</Button></DialogFooter>
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}
