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
import Pagination from '@/components/Pagination';
import { Head, router, usePage } from '@inertiajs/react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Pencil, Plus, Save, Swords, Trash2, Trophy } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import type { Result, Fixture, Participant, Paginated, Flash, Event } from '@/types';

const resultSchema = z.object({
    match_id: z.string().min(1, 'Match is required'),
    score_home: z.number().nullable().optional().default(null),
    score_away: z.number().nullable().optional().default(null),
    winner_participant_id: z.string().nullable().optional().default(''),
    notes: z.string().optional().default(''),
});

type ResultForm = z.infer<typeof resultSchema>;

interface ResultRow extends Result {
    slug?: string;
}

interface MatchOption extends Fixture {
    pool?: { id: string; name: string } | null;
    event?: Event & { sport?: { id: string; name: string } };
    home_participant?: Participant;
    away_participant?: Participant;
}

interface ResultsIndexProps {
    results: Paginated<ResultRow> | ResultRow[];
    matches?: MatchOption[];
    participants?: Participant[];
    events?: Array<{ id: string; name: string }>;
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

const matchLabel = (match?: MatchOption) => {
    if (!match) return '';
    return `#${match.match_number} · ${participantName(match.home_participant)} vs ${participantName(match.away_participant)}`;
};

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
                        <span>{new Date(match.scheduled_at).toLocaleString()}</span>
                    )}
                </div>
            </div>
            <div className="flex items-center justify-center gap-4">
                <div className="flex min-w-0 flex-1 flex-col items-center gap-1 text-center">
                    <TeamMark participant={match.home_participant} />
                    <span className="truncate text-sm font-semibold">{participantName(match.home_participant)}</span>
                    <span className="text-[10px] uppercase tracking-wide text-muted-foreground">Home</span>
                </div>
                <span className="shrink-0 text-sm font-bold text-muted-foreground">VS</span>
                <div className="flex min-w-0 flex-1 flex-col items-center gap-1 text-center">
                    <TeamMark participant={match.away_participant} />
                    <span className="truncate text-sm font-semibold">{participantName(match.away_participant)}</span>
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

export default function ResultsIndex({ results: resultsProp, matches: matchesProp = [], participants: participantsProp = [], events: eventsProp = [] }: ResultsIndexProps) {
    const { flash } = usePage().props;
    const [open, setOpen] = useState(false);
    const [editingResult, setEditingResult] = useState<ResultRow | null>(null);
    const [deleteResult, setDeleteResult] = useState<ResultRow | null>(null);
    const [filterEventId, setFilterEventId] = useState('');

    const results = Array.isArray(resultsProp) ? resultsProp : (resultsProp?.data ?? []);
    const matches = Array.isArray(matchesProp) ? matchesProp : (matchesProp ?? []);
    const participants = Array.isArray(participantsProp) ? participantsProp : (participantsProp ?? []);
    const events = Array.isArray(eventsProp) ? eventsProp : (eventsProp ?? []);

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

    const openCreate = () => {
        setEditingResult(null);
        reset({
            match_id: matches.length > 0 ? matches[0].id : '',
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

    const filteredResults = results.filter(
        (result) => !filterEventId || result.match?.event_id === filterEventId
    );

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Results</h1>
                        <p className="text-sm text-muted-foreground">
                            Record and manage match results
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
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

                        <Dialog open={open} onOpenChange={(isOpen) => {
                            if (!isOpen) closeDialog();
                            else setOpen(true);
                        }}>
                        <DialogTrigger asChild>
                            <Button onClick={openCreate} disabled={matches.length === 0}>
                                <Plus className="mr-2 size-4" />
                                Add Result
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="max-w-lg">
                            <form onSubmit={handleSubmit(onSubmit)}>
                                <DialogHeader>
                                    <DialogTitle>{editingResult ? 'Edit Result' : 'Record Result'}</DialogTitle>
                                    <DialogDescription>
                                        Select the match, then enter the final scores.
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
                                            {matches.map((m) => (
                                                <option key={m.id} value={m.id}>
                                                    {matchLabel(m)} {m.event?.name ? `(${m.event.name})` : ''}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.match_id && <p className="text-sm text-destructive">{errors.match_id.message}</p>}
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
                                        Cancel
                                    </Button>
                                    <Button type="submit" disabled={isSubmitting}>
                                        <Save className="mr-2 size-4" />
                                        {editingResult ? 'Update' : 'Save'}
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                    </div>
                </div>
            }
        >
            <Head title="Results" />

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

            <div className="mb-4">
                <select
                    className="flex h-9 w-80 rounded-md border border-input bg-background px-3 py-1 text-sm"
                    value={filterEventId}
                    onChange={(e) => setFilterEventId(e.target.value)}
                >
                    <option value="">-- All Events --</option>
                    {events.map((event) => (
                        <option key={event.id} value={event.id}>{event.name}</option>
                    ))}
                </select>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Match Results</CardTitle>
                    <CardDescription>
                        All recorded match results. Track scores and winners.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Match</TableHead>
                                <TableHead>Event</TableHead>
                                <TableHead>Home</TableHead>
                                <TableHead>Score</TableHead>
                                <TableHead>Away</TableHead>
                                <TableHead>Winner</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {filteredResults.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={7} className="text-center text-muted-foreground">
                                        No results recorded yet.
                                    </TableCell>
                                </TableRow>
                            )}
                            {filteredResults.map((result) => (
                                <TableRow key={result.id}>
                                    <TableCell className="font-medium">
                                        #{result.match?.match_number ?? '-'}
                                    </TableCell>
                                    <TableCell className="text-sm text-muted-foreground">
                                        {result.match?.event?.name || '-'}
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex items-center gap-2">
                                            <TeamMark participant={result.match?.home_participant} size="size-6" />
                                            <span className="truncate">{participantName(result.match?.home_participant)}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell className="font-bold text-center">
                                        {result.score_home ?? '-'} : {result.score_away ?? '-'}
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex items-center gap-2">
                                            <TeamMark participant={result.match?.away_participant} size="size-6" />
                                            <span className="truncate">{participantName(result.match?.away_participant)}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        {result.winner ? (
                                            <span className="flex items-center gap-1.5 font-medium text-emerald-600 dark:text-emerald-400">
                                                <Trophy className="size-3.5" />
                                                {participantName(result.winner)}
                                            </span>
                                        ) : (
                                            <span className="text-muted-foreground">Draw</span>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right space-x-2">
                                        <Button variant="outline" size="sm" onClick={() => openEdit(result)}>
                                            <Pencil className="mr-1 size-3" /> Edit
                                        </Button>
                                        <Button variant="destructive" size="sm" onClick={() => setDeleteResult(result)}>
                                            <Trash2 className="mr-1 size-3" /> Delete
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

                    {resultsProp?.links && (
                        <div className="mt-4">
                            <Pagination links={resultsProp.links} />
                        </div>
                    )}
                </CardContent>
            </Card>

            <Dialog open={!!deleteResult} onOpenChange={(isOpen) => !isOpen && setDeleteResult(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Result?</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete this result? This action cannot be undone.
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

            <div className="mt-6 text-xs text-muted-foreground">
                M4: Result entry module. Record match scores and winners.
            </div>
        </AuthenticatedLayout>
    );
}
