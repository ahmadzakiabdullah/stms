import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
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
import { Pencil, Plus, Save, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { Result, Fixture, Participant, Paginated, Flash } from '@/types';

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

interface ResultsIndexProps {
    results: Paginated<ResultRow> | ResultRow[];
    matches?: Fixture[];
    participants?: Participant[];
    events?: Array<{ id: string; name: string }>;
}

export default function ResultsIndex({ results: resultsProp, matches: matchesProp = [], participants: participantsProp = [], events: eventsProp = [] }: ResultsIndexProps) {
    const { flash } = usePage().props;
    const [open, setOpen] = useState(false);
    const [editingResult, setEditingResult] = useState<ResultRow | null>(null);
    const [deleteResult, setDeleteResult] = useState<ResultRow | null>(null);

    const results = Array.isArray(resultsProp) ? resultsProp : (resultsProp?.data ?? []);
    const matches = Array.isArray(matchesProp) ? matchesProp : (matchesProp ?? []);
    const participants = Array.isArray(participantsProp) ? participantsProp : (participantsProp ?? []);
    const events = Array.isArray(eventsProp) ? eventsProp : (eventsProp ?? []);

    const { register, handleSubmit, reset, formState: { errors, isSubmitting } } = useForm<ResultForm>({
        resolver: zodResolver(resultSchema),
        defaultValues: {
            match_id: '',
            score_home: null,
            score_away: null,
            winner_participant_id: '',
            notes: '',
        },
    });

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
                                        Record the outcome of a match.
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
                                                    #{m.match_number} - {m.status}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.match_id && <p className="text-sm text-destructive">{errors.match_id.message}</p>}
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="score_home">Home Score</Label>
                                            <Input
                                                id="score_home"
                                                type="number"
                                                min="0"
                                                {...register('score_home', { valueAsNumber: true })}
                                                placeholder="0"
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="score_away">Away Score</Label>
                                            <Input
                                                id="score_away"
                                                type="number"
                                                min="0"
                                                {...register('score_away', { valueAsNumber: true })}
                                                placeholder="0"
                                            />
                                        </div>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="winner_participant_id">Winner</Label>
                                        <select
                                            id="winner_participant_id"
                                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                                            {...register('winner_participant_id')}
                                        >
                                            <option value="">-- Draw / None --</option>
                                            {participants.map((p) => (
                                                <option key={p.id} value={p.id}>{p.name}</option>
                                            ))}
                                        </select>
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
                                <TableHead>Home</TableHead>
                                <TableHead>Score</TableHead>
                                <TableHead>Away</TableHead>
                                <TableHead>Winner</TableHead>
                                <TableHead>Notes</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {results.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={7} className="text-center text-muted-foreground">
                                        No results recorded yet.
                                    </TableCell>
                                </TableRow>
                            )}
                            {results.map((result) => (
                                <TableRow key={result.id}>
                                    <TableCell className="font-medium">
                                        #{result.match?.match_number ?? '-'}
                                    </TableCell>
                                    <TableCell>{result.match?.home_participant?.name || '-'}</TableCell>
                                    <TableCell className="font-bold text-center">
                                        {result.score_home ?? '-'} : {result.score_away ?? '-'}
                                    </TableCell>
                                    <TableCell>{result.match?.away_participant?.name || '-'}</TableCell>
                                    <TableCell>{result.winner?.name || 'Draw'}</TableCell>
                                    <TableCell className="text-sm text-muted-foreground max-w-[150px] truncate">
                                        {result.notes || '-'}
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
