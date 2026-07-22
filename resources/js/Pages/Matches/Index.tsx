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
import type { Fixture, Event, Participant, Paginated, Flash } from '@/types';

const matchSchema = z.object({
    event_id: z.string().min(1, 'Event is required'),
    match_number: z.number().min(1, 'Match number is required'),
    home_participant_id: z.string().nullable().optional().default(''),
    away_participant_id: z.string().nullable().optional().default(''),
    venue: z.string().optional().default(''),
    scheduled_at: z.string().optional().default(''),
    status: z.enum(['scheduled', 'in_progress', 'completed', 'cancelled']).default('scheduled'),
    notes: z.string().optional().default(''),
});

type MatchForm = z.infer<typeof matchSchema>;

interface MatchRow extends Fixture {
    slug?: string;
}

interface MatchesIndexProps {
    matches: Paginated<MatchRow> | MatchRow[];
    events?: Event[];
    participants?: Participant[];
}

const statusColors: Record<string, string> = {
    scheduled: 'bg-blue-100 text-blue-700',
    in_progress: 'bg-yellow-100 text-yellow-700',
    completed: 'bg-emerald-100 text-emerald-700',
    cancelled: 'bg-gray-100 text-gray-600',
};

export default function MatchesIndex({ matches: matchesProp, events: eventsProp = [], participants: participantsProp = [] }: MatchesIndexProps) {
    const { flash } = usePage().props;
    const [open, setOpen] = useState(false);
    const [editingMatch, setEditingMatch] = useState<MatchRow | null>(null);
    const [deleteMatch, setDeleteMatch] = useState<MatchRow | null>(null);

    const matches = Array.isArray(matchesProp) ? matchesProp : (matchesProp?.data ?? []);
    const events = Array.isArray(eventsProp) ? eventsProp : (eventsProp ?? []);
    const participants = Array.isArray(participantsProp) ? participantsProp : (participantsProp ?? []);

    const { register, handleSubmit, reset, formState: { errors, isSubmitting } } = useForm<MatchForm>({
        resolver: zodResolver(matchSchema),
        defaultValues: {
            event_id: '',
            match_number: 1,
            home_participant_id: '',
            away_participant_id: '',
            venue: '',
            scheduled_at: '',
            status: 'scheduled',
            notes: '',
        },
    });

    const openCreate = () => {
        setEditingMatch(null);
        reset({
            event_id: events.length > 0 ? events[0].id : '',
            match_number: matches.length + 1,
            home_participant_id: participants.length > 0 ? participants[0].id : '',
            away_participant_id: participants.length > 1 ? participants[1].id : '',
            venue: '',
            scheduled_at: '',
            status: 'scheduled',
            notes: '',
        });
        setOpen(true);
    };

    const openEdit = (match: MatchRow) => {
        setEditingMatch(match);

        const formatForInput = (dateStr: string | null) => {
            if (!dateStr) return '';
            return dateStr.slice(0, 16);
        };

        reset({
            event_id: match.event_id,
            match_number: match.match_number,
            home_participant_id: match.home_participant_id || '',
            away_participant_id: match.away_participant_id || '',
            venue: match.venue || '',
            scheduled_at: formatForInput(match.scheduled_at),
            status: match.status,
            notes: match.notes || '',
        });
        setOpen(true);
    };

    const closeDialog = () => {
        setOpen(false);
        setEditingMatch(null);
        reset();
    };

    const onSubmit = (formData: MatchForm) => {
        if (editingMatch) {
            router.put(route('matches.update', editingMatch.id), formData, {
                onSuccess: () => closeDialog(),
            });
        } else {
            router.post(route('matches.store'), formData, {
                onSuccess: () => closeDialog(),
            });
        }
    };

    const handleDelete = () => {
        if (!deleteMatch) return;
        router.delete(route('matches.destroy', deleteMatch.id), {
            preserveScroll: true,
            onSuccess: () => setDeleteMatch(null),
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Matches</h1>
                        <p className="text-sm text-muted-foreground">
                            Schedule and manage matches for events
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => window.location.href = route('exports.fixtures.pdf')}
                        >
                            PDF
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => window.location.href = route('exports.fixtures.excel')}
                        >
                            Excel
                        </Button>

                        <Dialog open={open} onOpenChange={(isOpen) => {
                            if (!isOpen) closeDialog();
                            else setOpen(true);
                        }}>
                        <DialogTrigger asChild>
                            <Button onClick={openCreate} disabled={events.length === 0 || participants.length === 0}>
                                <Plus className="mr-2 size-4" />
                                Add Match
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="max-w-lg">
                            <form onSubmit={handleSubmit(onSubmit)}>
                                <DialogHeader>
                                    <DialogTitle>{editingMatch ? 'Edit Match' : 'Create Match'}</DialogTitle>
                                    <DialogDescription>
                                        Schedule a new match between participants.
                                    </DialogDescription>
                                </DialogHeader>

                                <div className="grid gap-4 py-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="event_id">Event *</Label>
                                        <select
                                            id="event_id"
                                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                                            {...register('event_id')}
                                            required
                                        >
                                            <option value="">-- Select Event --</option>
                                            {events.map((e) => (
                                                <option key={e.id} value={e.id}>{e.name}</option>
                                            ))}
                                        </select>
                                        {errors.event_id && <p className="text-sm text-destructive">{errors.event_id.message}</p>}
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="match_number">Match # *</Label>
                                            <Input
                                                id="match_number"
                                                type="number"
                                                min="1"
                                                {...register('match_number', { valueAsNumber: true })}
                                                required
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="status">Status</Label>
                                            <select
                                                id="status"
                                                className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                                                {...register('status')}
                                            >
                                                <option value="scheduled">Scheduled</option>
                                                <option value="in_progress">In Progress</option>
                                                <option value="completed">Completed</option>
                                                <option value="cancelled">Cancelled</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="home_participant_id">Home Participant</Label>
                                            <select
                                                id="home_participant_id"
                                                className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                                                {...register('home_participant_id')}
                                            >
                                                <option value="">-- None --</option>
                                                {participants.map((p) => (
                                                    <option key={p.id} value={p.id}>{p.name}</option>
                                                ))}
                                            </select>
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="away_participant_id">Away Participant</Label>
                                            <select
                                                id="away_participant_id"
                                                className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                                                {...register('away_participant_id')}
                                            >
                                                <option value="">-- None --</option>
                                                {participants.map((p) => (
                                                    <option key={p.id} value={p.id}>{p.name}</option>
                                                ))}
                                            </select>
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="venue">Venue</Label>
                                            <Input
                                                id="venue"
                                                {...register('venue')}
                                                placeholder="e.g. Main Stadium"
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="scheduled_at">Scheduled At</Label>
                                            <Input
                                                id="scheduled_at"
                                                type="datetime-local"
                                                {...register('scheduled_at')}
                                            />
                                        </div>
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
                                        {editingMatch ? 'Update' : 'Save'}
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                    </div>
                </div>
            }
        >
            <Head title="Matches" />

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
                    <CardTitle>Match Schedule</CardTitle>
                    <CardDescription>
                        All scheduled matches across events. Track status and assign venues.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Match #</TableHead>
                                <TableHead>Event</TableHead>
                                <TableHead>Home</TableHead>
                                <TableHead>Away</TableHead>
                                <TableHead>Venue</TableHead>
                                <TableHead>Scheduled</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {matches.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={8} className="text-center text-muted-foreground">
                                        No matches scheduled yet.
                                    </TableCell>
                                </TableRow>
                            )}
                            {matches.map((match) => (
                                <TableRow key={match.id}>
                                    <TableCell className="font-medium">{match.match_number}</TableCell>
                                    <TableCell>{match.event?.name || '-'}</TableCell>
                                    <TableCell>{match.home_participant?.name || '-'}</TableCell>
                                    <TableCell>{match.away_participant?.name || '-'}</TableCell>
                                    <TableCell className="text-sm">{match.venue || '-'}</TableCell>
                                    <TableCell className="text-sm text-muted-foreground">
                                        {match.scheduled_at ? new Date(match.scheduled_at).toLocaleString() : '-'}
                                    </TableCell>
                                    <TableCell>
                                        <span className={`rounded-full px-2 py-0.5 text-xs capitalize ${statusColors[match.status] || 'bg-gray-100 text-gray-600'}`}>
                                            {match.status.replace('_', ' ')}
                                        </span>
                                    </TableCell>
                                    <TableCell className="text-right space-x-2">
                                        <Button variant="outline" size="sm" onClick={() => openEdit(match)}>
                                            <Pencil className="mr-1 size-3" /> Edit
                                        </Button>
                                        <Button variant="destructive" size="sm" onClick={() => setDeleteMatch(match)}>
                                            <Trash2 className="mr-1 size-3" /> Delete
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

                    {matchesProp?.links && (
                        <div className="mt-4">
                            <Pagination links={matchesProp.links} />
                        </div>
                    )}
                </CardContent>
            </Card>

            <Dialog open={!!deleteMatch} onOpenChange={(isOpen) => !isOpen && setDeleteMatch(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Match?</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete this match? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteMatch(null)}>
                            Cancel
                        </Button>
                        <Button variant="destructive" onClick={handleDelete} disabled={isSubmitting}>
                            Yes, Delete
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <div className="mt-6 text-xs text-muted-foreground">
                M4: Match scheduling module. Create and manage matches for events.
            </div>
        </AuthenticatedLayout>
    );
}
