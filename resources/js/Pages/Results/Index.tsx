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
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Head, router, usePage } from '@inertiajs/react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { CalendarDays, CheckCircle2, Pencil, Plus, Save, Search, Swords, Trash2, Trophy } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { matchNumberLabel } from '@/lib/matchNumber';
import { useI18n } from '@/lib/i18n';
import type { Result, Fixture, Participant, Event } from '@/types';

const resultSchema = z.object({
    match_id: z.string().min(1, 'Match is required'),
    score_home: z.number().nullable().optional().default(null),
    score_away: z.number().nullable().optional().default(null),
    winner_participant_id: z.string().nullable().optional().default(''),
    notes: z.string().optional().default(''),
});

type ResultForm = z.infer<typeof resultSchema>;

type ResultRow = Result;

interface MatchOption extends Fixture {
    event?: Event & { sport?: { id: string; name: string } };
    home_participant?: Participant;
    away_participant?: Participant;
}

interface ResultsIndexProps {
    results: ResultRow[];
    matches?: MatchOption[];
    participants?: Participant[];
    events?: Array<{ id: string; name: string; slug?: string }>;
    canManage?: boolean;
}

const participantName = (participant?: Participant, fallback = 'TBD') => {
    if (!participant) return fallback;

    const code = participant.name?.trim();

    // Prefer the short code (e.g. FTKEK); full team name used only for
    // long/individual names or as a subtitle/tooltip.
    if (code && code.length <= 12) return code;

    return participant.team_name || code || fallback;
};

const participantFullName = (participant?: Participant, fallback = '') => {
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

function TeamMark({ participant, fallback = 'TBD', size = 'size-9' }: { participant?: Participant; fallback?: string; size?: string }) {
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

const stageTitle = (stage: string | null | undefined, round?: number | null) => {
    const map: Record<string, string> = {
        semi_final: `Semi-Final ${round ?? 1}`,
        bronze: 'Bronze · 3rd Place',
        final: 'Final',
    };
    return map[stage ?? ''] || 'Knockout';
};

const matchDetail = (match?: MatchOption) => {
    if (!match) return '';
    if (match.pool?.name) return match.pool.name;
    if (match.stage) return stageTitle(match.stage, match.round);
    return `Round ${match.round || 1}`;
};

const matchLabel = (match?: MatchOption) => {
    if (!match) return '';
    return `#${matchNumberLabel(match.match_number, match.event?.name)} · ${participantName(match.home_participant)} vs ${participantName(match.away_participant)}`;
};

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

function MatchupPreview({ match }: { match?: MatchOption }) {
    if (!match) return null;

    return (
        <div className="rounded-lg border bg-muted/30 p-3">
            <div className="mb-2 flex flex-wrap items-center justify-between gap-2">
                <div className="flex items-center gap-2 text-xs text-muted-foreground">
                    <Swords className="size-3.5" />
                    <span>{match.event?.name || 'Match'}</span>
                    {match.pool?.name && <Badge variant="outline">{match.pool.name}</Badge>}
                    {match.scheduled_at && (
                        <span>{formatDateTime(match.scheduled_at)}</span>
                    )}
                </div>
            </div>
            <div className="flex items-center justify-center gap-4">
                <div className="flex min-w-0 flex-1 flex-col items-center gap-1 text-center">
                    <TeamMark participant={match.home_participant} />
                    <span className="truncate text-sm font-semibold" title={participantFullName(match.home_participant)}>{participantName(match.home_participant)}</span>
                    {participantFullName(match.home_participant) !== participantName(match.home_participant) && (
                        <span className="line-clamp-1 max-w-40 text-[10px] leading-tight text-muted-foreground" title={participantFullName(match.home_participant)}>
                            {participantFullName(match.home_participant)}
                        </span>
                    )}
                    <span className="text-[10px] uppercase tracking-wide text-muted-foreground">Home</span>
                </div>
                <span className="shrink-0 text-sm font-bold text-muted-foreground">VS</span>
                <div className="flex min-w-0 flex-1 flex-col items-center gap-1 text-center">
                    <TeamMark participant={match.away_participant} />
                    <span className="truncate text-sm font-semibold" title={participantFullName(match.away_participant)}>{participantName(match.away_participant)}</span>
                    {participantFullName(match.away_participant) !== participantName(match.away_participant) && (
                        <span className="line-clamp-1 max-w-40 text-[10px] leading-tight text-muted-foreground" title={participantFullName(match.away_participant)}>
                            {participantFullName(match.away_participant)}
                        </span>
                    )}
                    <span className="text-[10px] uppercase tracking-wide text-muted-foreground">Away</span>
                </div>
            </div>
        </div>
    );
}

function WinnerHint({
    match,
    scoreHome,
    scoreAway,
}: {
    match?: MatchOption;
    scoreHome: number | null | undefined;
    scoreAway: number | null | undefined;
}) {
    if (!match || scoreHome == null || scoreAway == null) {
        return null;
    }

    if (scoreHome === scoreAway) {
        return (
            <p className="text-sm text-muted-foreground">
                Result: <span className="font-semibold">Draw</span>
            </p>
        );
    }

    const winner = scoreHome > scoreAway ? match.home_participant : match.away_participant;

    return (
        <p className="text-sm text-muted-foreground">
            Winner: <span className="font-semibold text-emerald-600 dark:text-emerald-400">{participantName(winner)}</span>
        </p>
    );
}

interface ResultRowViewProps {
    result: ResultRow;
    onEdit: () => void;
    onDelete: () => void;
    canManage?: boolean;
}

function ResultRowView({ result, onEdit, onDelete, canManage = true }: ResultRowViewProps) {
    const scored = result.score_home !== null && result.score_home !== undefined;
    const isDraw = scored && result.score_home === result.score_away;

    return (
        <TableRow key={result.id}>
            <TableCell className="w-14 font-medium text-muted-foreground">#{matchNumberLabel(result.match?.match_number, result.match?.event?.name)}</TableCell>
            <TableCell>
                <div className="flex min-w-[260px] items-center gap-2">
                    <span className="max-w-[110px] truncate font-medium text-right" title={participantFullName(result.match?.home_participant)}>{participantName(result.match?.home_participant)}</span>
                    <TeamMark participant={result.match?.home_participant} size="size-12" />
                    <span className={`mx-1 shrink-0 rounded-md px-2 py-0.5 text-sm font-bold tabular-nums ${scored ? 'bg-muted' : 'text-muted-foreground'}`}>
                        {scored ? `${result.score_home} : ${result.score_away}` : 'VS'}
                    </span>
                    <TeamMark participant={result.match?.away_participant} size="size-12" />
                    <span className="max-w-[110px] truncate font-medium" title={participantFullName(result.match?.away_participant)}>{participantName(result.match?.away_participant)}</span>
                </div>
            </TableCell>
            <TableCell className="text-sm text-muted-foreground">
                {result.match?.pool?.name || (result.match?.stage ? stageTitle(result.match.stage, result.match.round) : `Round ${result.match?.round || 1}`)}
            </TableCell>
            <TableCell className="text-sm">
                <div className="flex items-center gap-1.5 text-muted-foreground">
                    <CalendarDays className="size-3" />
                    {result.match?.venue || 'Venue TBD'}
                </div>
                <div className="text-xs text-muted-foreground">{formatDateTime(result.match?.scheduled_at)}</div>
            </TableCell>
            <TableCell>
                {isDraw ? (
                    <span className="flex items-center gap-1.5 text-sm font-medium text-muted-foreground">
                        Draw
                    </span>
                ) : result.winner ? (
                    <span className="flex items-center gap-1.5 text-sm font-medium text-emerald-600 dark:text-emerald-400">
                        <Trophy className="size-3.5" />
                        <span title={participantFullName(result.winner)}>{participantName(result.winner)}</span>
                    </span>
                ) : (
                    <span className="text-sm text-muted-foreground">-</span>
                )}
            </TableCell>
            {canManage && (
            <TableCell className="space-x-1 text-right">
                <Button variant="outline" size="icon-sm" onClick={onEdit} aria-label="Edit result"><Pencil className="size-3" /></Button>
                <Button variant="destructive" size="icon-sm" onClick={onDelete} aria-label="Delete result"><Trash2 className="size-3" /></Button>
            </TableCell>
            )}
        </TableRow>
    );
}

export default function ResultsIndex({ results: resultsProp, matches: matchesProp = [], participants: participantsProp = [], events: eventsProp = [], canManage = true }: ResultsIndexProps) {
    const { flash } = usePage().props;
    const { t } = useI18n();
    const [open, setOpen] = useState(false);
    const [editingResult, setEditingResult] = useState<ResultRow | null>(null);
    const [deleteResult, setDeleteResult] = useState<ResultRow | null>(null);
    const [filterEventId, setFilterEventId] = useState('');
    const [query, setQuery] = useState('');

    const results = useMemo(() => (Array.isArray(resultsProp) ? resultsProp : []), [resultsProp]);
    const matches = useMemo(() => (Array.isArray(matchesProp) ? matchesProp : []), [matchesProp]);
    const participants = useMemo(() => (Array.isArray(participantsProp) ? participantsProp : []), [participantsProp]);
    const events = useMemo(() => (Array.isArray(eventsProp) ? eventsProp : []), [eventsProp]);

    const { register, handleSubmit, reset, watch, setValue, formState: { errors, isSubmitting } } = useForm<ResultForm>({
        resolver: zodResolver(resultSchema),
        defaultValues: {
            match_id: '',
            score_home: null,
            score_away: null,
            winner_participant_id: '',
            notes: '',
        },
    });

    const matchId = watch('match_id');
    const scoreHome = watch('score_home');
    const scoreAway = watch('score_away');
    const selectedMatch = useMemo(
        () => matches.find((m) => m.id === matchId),
        [matches, matchId]
    );

    // Auto-set winner from scores whenever they change (manual override still possible).
    useEffect(() => {
        if (!selectedMatch || scoreHome == null || scoreAway == null) {
            return;
        }

        const winnerId = scoreHome > scoreAway
            ? selectedMatch.home_participant_id
            : scoreHome < scoreAway
                ? selectedMatch.away_participant_id
                : '';

        setValue('winner_participant_id', winnerId ?? '', { shouldValidate: false });
    }, [scoreHome, scoreAway, selectedMatch, setValue]);

    const pendingMatches = useMemo(
        () => (filterEventId ? matches.filter((m) => m.event_id === filterEventId) : matches),
        [matches, filterEventId]
    );

    const filteredResults = useMemo(() => {
        const q = query.trim().toLowerCase();

        return results.filter((result) => {
            if (filterEventId && result.match?.event_id !== filterEventId) return false;
            if (!q) return true;

            const haystack = [
                String(result.match?.match_number ?? ''),
                matchNumberLabel(result.match?.match_number, result.match?.event?.name),
                result.match?.event?.name || '',
                result.match?.pool?.name || '',
                participantFullName(result.match?.home_participant),
                participantFullName(result.match?.away_participant),
                participantFullName(result.winner),
                result.notes || '',
            ].join(' ').toLowerCase();

            return haystack.includes(q);
        });
    }, [results, filterEventId, query]);

    const countsByEvent = useMemo(() => {
        const map = new Map<string, { results: number; pending: number }>();

        for (const result of results) {
            const eventId = result.match?.event_id ?? '';
            const entry = map.get(eventId) ?? { results: 0, pending: 0 };
            entry.results++;
            map.set(eventId, entry);
        }

        for (const match of matches) {
            const entry = map.get(match.event_id) ?? { results: 0, pending: 0 };
            entry.pending++;
            map.set(match.event_id, entry);
        }

        return map;
    }, [results, matches]);

    const drawCount = useMemo(() => results.filter((r) => r.score_home !== null && r.score_home === r.score_away).length, [results]);
    const homeWinCount = useMemo(() => results.filter((r) => r.score_home !== null && r.score_away !== null && r.score_home > r.score_away).length, [results]);
    const awayWinCount = useMemo(() => results.filter((r) => r.score_home !== null && r.score_away !== null && r.score_away > r.score_home).length, [results]);

    const dialogMatches = useMemo(
        () => (filterEventId ? matches.filter((m) => m.event_id === filterEventId) : matches),
        [matches, filterEventId]
    );

    const openCreate = (match?: MatchOption) => {
        setEditingResult(null);
        reset({
            match_id: match?.id ?? dialogMatches[0]?.id ?? '',
            score_home: null,
            score_away: null,
            winner_participant_id: '',
            notes: '',
        });
        setOpen(true);
    };

    const openEdit = (result: ResultRow) => {
        setEditingResult(result);
        reset({
            match_id: result.match_id,
            score_home: result.score_home ?? null,
            score_away: result.score_away ?? null,
            winner_participant_id: result.winner_participant_id || '',
            notes: result.notes || '',
        });
        setOpen(true);
    };

    const closeDialog = () => {
        setOpen(false);
        setEditingResult(null);
        reset();
    };

    const onSubmit = (formData: ResultForm) => {
        if (editingResult) {
            router.put(route('results.update', editingResult.id), formData, {
                onSuccess: () => closeDialog(),
            });
        } else {
            router.post(route('results.store'), formData, {
                onSuccess: () => closeDialog(),
            });
        }
    };

    const handleDelete = () => {
        if (!deleteResult) return;
        router.delete(route('results.destroy', deleteResult.id), {
            preserveScroll: true,
            onSuccess: () => setDeleteResult(null),
        });
    };

    const selectedEvent = events.find((event) => event.id === filterEventId);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">{t('Results')}</h1>
                        <p className="text-sm text-muted-foreground">
                            {t('Record and manage match results')}
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        {canManage && (
                        <>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => window.location.href = route('exports.results.pdf')}
                        >
                            PDF
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => window.location.href = route('exports.results.excel')}
                        >
                            Excel
                        </Button>
                        </>
                        )}

                        {canManage && (
                        <Dialog open={open} onOpenChange={(isOpen) => {
                            if (!isOpen) closeDialog();
                            else setOpen(true);
                        }}>
                        <DialogTrigger asChild>
                            <Button onClick={() => openCreate()} disabled={dialogMatches.length === 0}>
                                <Plus className="mr-2 size-4" />
                                {t('Add Result')}
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="max-h-[90vh] max-w-lg overflow-y-auto">
                            <form onSubmit={handleSubmit(onSubmit)}>
                                <DialogHeader>
                                    <DialogTitle>{editingResult ? t('Edit Result') : t('Record Result')}</DialogTitle>
                                    <DialogDescription>
                                        {t('Select the match, then enter the final scores.')}
                                    </DialogDescription>
                                </DialogHeader>

                                <div className="grid gap-4 py-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="match_id">Match *</Label>
                                        <select
                                            id="match_id"
                                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                                            {...register('match_id')}
                                            disabled={!!editingResult}
                                            required
                                        >
                                            <option value="">-- Select Match --</option>
                                            {dialogMatches.map((m) => (
                                                <option key={m.id} value={m.id}>
                                                    {matchLabel(m)} {m.event?.name ? `(${m.event.name})` : ''}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.match_id && <p className="text-sm text-destructive">{errors.match_id.message}</p>}
                                        {dialogMatches.length === 0 && (
                                            <p className="text-sm text-muted-foreground">No matches awaiting a result in this event.</p>
                                        )}
                                    </div>

                                    <MatchupPreview match={selectedMatch} />

                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="score_home">{participantName(selectedMatch?.home_participant)} (Home) Score</Label>
                                            <Input
                                                id="score_home"
                                                type="number"
                                                min="0"
                                                {...register('score_home', { valueAsNumber: true })}
                                                placeholder="0"
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="score_away">{participantName(selectedMatch?.away_participant)} (Away) Score</Label>
                                            <Input
                                                id="score_away"
                                                type="number"
                                                min="0"
                                                {...register('score_away', { valueAsNumber: true })}
                                                placeholder="0"
                                            />
                                        </div>
                                    </div>

                                    <WinnerHint match={selectedMatch} scoreHome={scoreHome} scoreAway={scoreAway} />

                                    <div className="grid gap-2">
                                        <Label htmlFor="winner_participant_id">Winner</Label>
                                        <select
                                            id="winner_participant_id"
                                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                                            {...register('winner_participant_id')}
                                        >
                                            <option value="">-- Draw / None --</option>
                                            {selectedMatch?.home_participant && (
                                                <option value={selectedMatch.home_participant.id}>
                                                    {participantName(selectedMatch.home_participant)} (Home)
                                                </option>
                                            )}
                                            {selectedMatch?.away_participant && (
                                                <option value={selectedMatch.away_participant.id}>
                                                    {participantName(selectedMatch.away_participant)} (Away)
                                                </option>
                                            )}
                                            {!selectedMatch && participants.map((p) => (
                                                <option key={p.id} value={p.id}>{participantName(p)}</option>
                                            ))}
                                        </select>
                                        <p className="text-xs text-muted-foreground">Auto-set from scores — change only for special cases (e.g. forfeit).</p>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="notes">Notes</Label>
                                        <textarea
                                            id="notes"
                                            className="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                            {...register('notes')}
                                        />
                                    </div>
                                </div>

                                <DialogFooter>
                                    <Button type="button" variant="outline" onClick={closeDialog}>
                                        {t('Cancel')}
                                    </Button>
                                    <Button type="submit" disabled={isSubmitting}>
                                        <Save className="mr-2 size-4" />
                                        {editingResult ? t('Update') : t('Save')}
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                        </Dialog>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={t('Results')} />

            {flash?.success && (
                <div className="mb-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-700">
                    {flash.success}
                </div>
            )}
            {flash?.error && (
                <div className="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700">
                    {flash.error}
                </div>
            )}

            <div className="mb-4 flex flex-wrap items-center gap-2">
                <Button
                    variant={!filterEventId ? 'default' : 'outline'}
                    size="sm"
                    onClick={() => setFilterEventId('')}
                >
                    {t('All Events')}
                    <span className="ml-1.5 rounded-full bg-muted px-1.5 py-0.5 text-xs font-semibold tabular-nums">{results.length}</span>
                </Button>
                {events.map((event) => {
                    const counts = countsByEvent.get(event.id);
                    const total = (counts?.results ?? 0) + (counts?.pending ?? 0);
                    if (total === 0) return null;

                    return (
                        <Button
                            key={event.id}
                            variant={filterEventId === event.id ? 'default' : 'outline'}
                            size="sm"
                            onClick={() => setFilterEventId(event.id)}
                        >
                            {event.name}
                            {counts && counts.pending > 0 && <span className="ml-1.5 size-1.5 rounded-full bg-amber-500" />}
                            <span className="ml-1.5 rounded-full bg-muted px-1.5 py-0.5 text-xs font-semibold tabular-nums">{total}</span>
                        </Button>
                    );
                })}
            </div>

            <div className="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-5">
                <StatCard label={t('Total Results')} value={results.length} />
                <StatCard label={t('Pending Matches')} value={pendingMatches.length} tone="destructive" />
                <StatCard label={t('Draws')} value={drawCount} />
                <StatCard label={t('Home Wins')} value={homeWinCount} tone="emerald" />
                <StatCard label={t('Away Wins')} value={awayWinCount} />
            </div>

            <div className="mb-4 flex flex-wrap items-center gap-3">
                <div className="relative">
                    <Search className="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        placeholder={t('Search team, match #, venue...')}
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                        className="w-72 pl-8"
                    />
                </div>
                <span className="text-sm text-muted-foreground">
                    {t('Showing')} {filteredResults.length} {t('of')} {results.length} {t('results')}
                </span>
            </div>

            {!selectedEvent ? (
                <div className="space-y-6">
                    {filteredResults.length === 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('No Results')}</CardTitle>
                                <CardDescription>
                                    {query
                                        ? t('No results match your search.')
                                        : t('No results recorded yet. Use "Add Result" to record the first match outcome.')}
                                </CardDescription>
                            </CardHeader>
                        </Card>
                    )}

                    {events
                        .map((event) => ({
                            event,
                            eventResults: filteredResults.filter((result) => result.match?.event_id === event.id),
                        }))
                        .filter((group) => group.eventResults.length > 0)
                        .map(({ event, eventResults }) => {
                            const pending = countsByEvent.get(event.id)?.pending ?? 0;

                            return (
                                <Card key={event.id}>
                                    <CardHeader className="pb-3">
                                        <div className="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <CardTitle className="text-lg">{event.name}</CardTitle>
                                                <CardDescription>
                                                    {eventResults.length} results
                                                    {pending > 0 && <span className="text-amber-600 dark:text-amber-400"> · {pending} pending</span>}
                                                </CardDescription>
                                            </div>
                                            {pending > 0 && canManage && (
                                                <Button variant="outline" size="sm" onClick={() => setFilterEventId(event.id)}>
                                                    <Plus className="mr-1 size-3" /> Record Results
                                                </Button>
                                            )}
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
                                                        <TableHead className="w-32">Winner</TableHead>
                                                        {canManage && <TableHead className="text-right">Actions</TableHead>}
                                                    </TableRow>
                                                </TableHeader>
                                                <TableBody>
                                                    {eventResults.map((result) => (
                                                        <ResultRowView
                                                            key={result.id}
                                                            result={result}
                                                            onEdit={() => openEdit(result)}
                                                            onDelete={() => setDeleteResult(result)}
                                                            canManage={canManage}
                                                        />
                                                    ))}
                                                </TableBody>
                                            </Table>
                                        </div>
                                    </CardContent>
                                </Card>
                            );
                        })}
                </div>
            ) : (
                <div className="space-y-6">
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-lg">{selectedEvent.name}</CardTitle>
                            <CardDescription>
                                {filteredResults.length} results
                                {pendingMatches.length > 0 && <span className="text-amber-600 dark:text-amber-400"> · {pendingMatches.length} pending</span>}
                            </CardDescription>
                        </CardHeader>
                    </Card>

                    {pendingMatches.length > 0 && (
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="flex items-center gap-2 text-lg"><CheckCircle2 className="size-4 text-amber-500" /> Pending Matches</CardTitle>
                                <CardDescription>Matches awaiting a recorded result.</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-2">
                                    {pendingMatches.map((match) => (
                                        <div key={match.id} className="flex flex-wrap items-center justify-between gap-2 rounded-lg border px-3 py-2">
                                            <div className="flex min-w-0 items-center gap-3">
                                                <span className="w-14 shrink-0 text-sm font-semibold text-muted-foreground">#{matchNumberLabel(match.match_number, match.event?.name)}</span>
                                                <span className="max-w-[110px] truncate text-sm font-medium" title={participantFullName(match.home_participant)}>{participantName(match.home_participant)}</span>
                                                <TeamMark participant={match.home_participant} size="size-12" />
                                                <span className="text-xs font-bold text-muted-foreground">VS</span>
                                                <TeamMark participant={match.away_participant} size="size-12" />
                                                <span className="max-w-[110px] truncate text-sm font-medium" title={participantFullName(match.away_participant)}>{participantName(match.away_participant)}</span>
                                                <span className="hidden text-xs text-muted-foreground sm:inline">· {matchDetail(match)}</span>
                                            </div>
                                            {canManage && (
                                            <Button size="sm" variant="outline" onClick={() => openCreate(match)}>
                                                <Plus className="mr-1 size-3" /> Record Result
                                            </Button>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-lg">Recorded Results</CardTitle>
                            <CardDescription>{filteredResults.length} result{filteredResults.length === 1 ? '' : 's'} for {selectedEvent.name}.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {filteredResults.length === 0 ? (
                                <p className="text-center text-sm text-muted-foreground">No results recorded yet.</p>
                            ) : (
                                <div className="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead className="w-14">#</TableHead>
                                                <TableHead>Matchup</TableHead>
                                                <TableHead className="w-32">Pool / Stage</TableHead>
                                                <TableHead>Venue / Time</TableHead>
                                                <TableHead className="w-32">Winner</TableHead>
                                                {canManage && <TableHead className="text-right">Actions</TableHead>}
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {filteredResults.map((result) => (
                                                <ResultRowView
                                                    key={result.id}
                                                    result={result}
                                                    onEdit={() => openEdit(result)}
                                                    onDelete={() => setDeleteResult(result)}
                                                    canManage={canManage}
                                                />
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            )}

            <Dialog open={!!deleteResult} onOpenChange={(isOpen) => !isOpen && setDeleteResult(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Result?</DialogTitle>
                        <DialogDescription>
                            Result for Match #{matchNumberLabel(deleteResult?.match?.match_number, deleteResult?.match?.event?.name)} will be removed. This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteResult(null)}>
                            Cancel
                        </Button>
                        <Button variant="destructive" onClick={handleDelete} disabled={isSubmitting}>
                            Yes, Delete
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}
