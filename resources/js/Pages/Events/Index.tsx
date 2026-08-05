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
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Eye, Pencil, Plus, Save, Target, Trash2, Trash } from 'lucide-react';
import { useState } from 'react';
import Pagination from '@/components/Pagination';
import { matchProgress } from '@/lib/matchProgress';
import type { Event, Tournament, Sport, SportCategory, Paginated, Flash } from '@/types';

const eventSchema = z.object({
    tournament_id: z.string().min(1, 'Tournament is required'),
    sport_id: z.string().min(1, 'Sport is required'),
    sport_category_id: z.string().min(1, 'Category is required'),
    name: z.string().min(1, 'Name is required'),
    slug: z.string().optional().default(''),
    description: z.string().optional().default(''),
    start_date: z.string().min(1, 'Start date is required'),
    end_date: z.string().optional().default(''),
    registration_deadline: z.string().optional().default(''),
    is_active: z.boolean(),
    format: z.string().optional().default(''),
    pool_size: z.coerce.number().min(2).max(32).optional().default(4),
});

type EventForm = z.infer<typeof eventSchema>;

interface EventRow extends Omit<Event, 'tournament' | 'sport'> {
    tournament?: { name: string } | null;
    sport?: { name: string } | null;
    sportCategory?: { name: string } | null;
}

interface EventsIndexProps {
    events: Paginated<EventRow> | EventRow[];
    tournaments?: Tournament[];
    sports?: Sport[];
    categories?: SportCategory[];
    usedCategoryIds?: Record<string, string[]>;
}

export default function EventsIndex({ events: eventsProp, tournaments: tournamentsProp = [], sports: sportsProp = [], categories: categoriesProp = [], usedCategoryIds = {} }: EventsIndexProps) {
    const { flash, isSuperAdmin = false } = usePage().props;
    const [open, setOpen] = useState(false);
    const [editingEvent, setEditingEvent] = useState<EventRow | null>(null);
    const [deleteEvent, setDeleteEvent] = useState<EventRow | null>(null);
    const [selectedIds, setSelectedIds] = useState<Set<string>>(new Set());
    const [batchDelete, setBatchDelete] = useState(false);
    const [drawEvent, setDrawEvent] = useState<EventRow | null>(null);
    const [drawFormat, setDrawFormat] = useState('group_knockout');

    const events = Array.isArray(eventsProp) ? eventsProp : (eventsProp?.data ?? []);
    const tournaments = Array.isArray(tournamentsProp) ? tournamentsProp : (tournamentsProp ?? []);
    const sports = Array.isArray(sportsProp) ? sportsProp : (sportsProp ?? []);
    const categories = Array.isArray(categoriesProp) ? categoriesProp : (categoriesProp ?? []);

    const { register, handleSubmit, reset, watch, setValue, formState: { errors, isSubmitting } } = useForm<EventForm>({
        resolver: zodResolver(eventSchema),
        defaultValues: {
            tournament_id: '',
            sport_id: '',
            sport_category_id: '',
            name: '',
            slug: '',
            description: '',
            start_date: '',
            end_date: '',
            registration_deadline: '',
            is_active: true,
        },
    });

    const selectedTournamentId = watch('tournament_id');
    const selectedSportId = watch('sport_id');

    const formatLabel = (format?: string | null) => {
    const map: Record<string, string> = {
        group_knockout: 'Group Knockout',
        league: 'League',
        knockout: 'Knockout',
    };
    return format ? map[format] || format : '—';
};

const formatForDateInput = (dateStr: string | null | undefined) => {
        if (!dateStr) return '';
        return dateStr.split('T')[0];
    };

    const selectedTournament = tournaments.find(t => t.id === selectedTournamentId);

    const filteredSports = selectedTournament?.sports?.length
        ? sports.filter(s => selectedTournament.sports!.some(ts => ts.id === s.id))
        : sports;

    const usedCatIds = selectedTournamentId && selectedSportId
        ? (usedCategoryIds[`${selectedTournamentId}:${selectedSportId}`] ?? [])
        : [];

    const filteredCategories = categories.filter(
        (c) => (!selectedSportId || c.sport_id === selectedSportId)
            && (!usedCatIds.includes(c.id) || editingEvent?.sport_category_id === c.id)
    );

    const onTournamentChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
        const tid = e.target.value;
        setValue('tournament_id', tid);
        setValue('sport_id', '');
        setValue('sport_category_id', '');
        const t = tournaments.find(t => t.id === tid);
        if (t) {
            setValue('start_date', formatForDateInput(t.start_date));
            setValue('end_date', formatForDateInput(t.end_date));
        }
    };

    const openCreate = () => {
        setEditingEvent(null);
        reset({
            tournament_id: '',
            sport_id: '',
            sport_category_id: '',
            name: '',
            slug: '',
            description: '',
            start_date: '',
            end_date: '',
            registration_deadline: '',
            is_active: true,
            format: '',
            pool_size: 4,
        });
        setOpen(true);
    };

    const openEdit = (event: EventRow) => {
        setEditingEvent(event);

        reset({
            tournament_id: event.tournament_id,
            sport_id: event.sport_id,
            sport_category_id: event.sport_category_id,
            name: event.name,
            slug: event.slug,
            description: event.description || '',
            start_date: formatForDateInput(event.start_date),
            end_date: formatForDateInput(event.end_date),
            registration_deadline: formatForDateInput((event as any).registration_deadline),
            is_active: event.is_active,
            format: (event as any).format || '',
            pool_size: (event as any).pool_size ?? 4,
        });
        setOpen(true);
    };

    const closeDialog = () => {
        setOpen(false);
        setEditingEvent(null);
        reset();
    };

    const onSubmit = (formData: EventForm) => {
        if (editingEvent) {
            router.put(route('events.update', editingEvent.slug), formData, {
                onSuccess: () => closeDialog(),
            });
        } else {
            router.post(route('events.store'), formData, {
                onSuccess: () => closeDialog(),
            });
        }
    };

    const handleDelete = () => {
        if (!deleteEvent) return;
        router.delete(route('events.destroy', deleteEvent.slug), {
            preserveScroll: true,
            onSuccess: () => setDeleteEvent(null),
        });
    };

    const toggleSelect = (id: string) => {
        setSelectedIds(prev => {
            const next = new Set(prev);
            if (next.has(id)) next.delete(id); else next.add(id);
            return next;
        });
    };

    const toggleSelectAll = () => {
        if (selectedIds.size === events.length) {
            setSelectedIds(new Set());
        } else {
            setSelectedIds(new Set(events.map(e => e.id)));
        }
    };

    const handleDraw = (event: EventRow) => {
        const existing = ['league', 'group_knockout', 'knockout'].includes((event as any).format)
            ? (event as any).format
            : 'group_knockout';
        setDrawFormat(existing);
        setDrawEvent(event);
    };

    const handleBatchDelete = () => {
        router.post(route('events.batch-destroy'), { ids: Array.from(selectedIds) }, {
            preserveScroll: true,
            onSuccess: () => { setSelectedIds(new Set()); setBatchDelete(false); },
            onError: () => setBatchDelete(false),
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Events</h1>
                        <p className="text-sm text-muted-foreground">
                            Specific competitions within tournaments (Sport + Category)
                        </p>
                    </div>

                    <Dialog open={open} onOpenChange={(isOpen) => {
                        if (!isOpen) closeDialog();
                        else setOpen(true);
                    }}>
                        {isSuperAdmin && (
                        <DialogTrigger asChild>
                            <Button onClick={openCreate} disabled={tournaments.length === 0}>
                                <Plus className="mr-2 size-4" />
                                Add Event
                            </Button>
                        </DialogTrigger>
                        )}
                        <DialogContent className="max-w-lg">
                            <form onSubmit={handleSubmit(onSubmit)}>
                                <DialogHeader>
                                    <DialogTitle>{editingEvent ? 'Edit Event' : 'Create New Event'}</DialogTitle>
                                    <DialogDescription>
                                        Events tie a tournament to a specific sport and category.
                                    </DialogDescription>
                                </DialogHeader>

                                <div className="grid gap-4 py-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="tournament_id">Tournament</Label>
                                        <select
                                            id="tournament_id"
                                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                                            value={selectedTournamentId}
                                            onChange={onTournamentChange}
                                            disabled={!!editingEvent}
                                            required
                                        >
                                            <option value="">-- Select Tournament --</option>
                                            {tournaments.map((t) => (
                                                <option key={t.id} value={t.id}>{t.name}</option>
                                            ))}
                                        </select>
                                        {errors.tournament_id && <p className="text-sm text-destructive">{errors.tournament_id.message}</p>}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="sport_id">Sport</Label>
                                        <select
                                            id="sport_id"
                                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                                            value={selectedSportId}
                                            onChange={(e) => { setValue('sport_id', e.target.value); setValue('sport_category_id', ''); }}
                                            required
                                        >
                                            <option value="">-- Select Sport --</option>
                                            {filteredSports.map((s) => (
                                                <option key={s.id} value={s.id}>{s.name}</option>
                                            ))}
                                        </select>
                                        {filteredSports.length === 0 && selectedTournamentId && (
                                            <p className="text-xs text-amber-600">This tournament has no sports assigned yet.</p>
                                        )}
                                        {errors.sport_id && <p className="text-sm text-destructive">{errors.sport_id.message}</p>}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="sport_category_id">Category</Label>
                                        <select
                                            id="sport_category_id"
                                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                                            {...register('sport_category_id')}
                                            required
                                        >
                                            <option value="">-- Select Category --</option>
                                            {filteredCategories.map((c) => (
                                                <option key={c.id} value={c.id}>{c.name}</option>
                                            ))}
                                        </select>
                                        {filteredCategories.length === 0 && selectedSportId && !editingEvent && (
                                            <p className="text-xs text-amber-600">All categories for this sport have already been used in this tournament.</p>
                                        )}
                                        {errors.sport_category_id && <p className="text-sm text-destructive">{errors.sport_category_id.message}</p>}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Event Name</Label>
                                        <Input
                                            id="name"
                                            {...register('name')}
                                            placeholder="e.g. Men's Football - Group A"
                                            required
                                        />
                                        {errors.name && <p className="text-sm text-destructive">{errors.name.message}</p>}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="slug">Slug</Label>
                                        <Input
                                            id="slug"
                                            {...register('slug')}
                                            placeholder="mens-football-group-a"
                                        />
                                        {errors.slug && <p className="text-sm text-destructive">{errors.slug.message}</p>}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="description">Description</Label>
                                        <textarea
                                            id="description"
                                            className="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                            {...register('description')}
                                        />
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="start_date">Start Date</Label>
                                            <Input
                                                id="start_date"
                                                type="date"
                                                {...register('start_date')}
                                                required
                                            />
                                            {errors.start_date && <p className="text-sm text-destructive">{errors.start_date.message}</p>}
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="end_date">End Date</Label>
                                            <Input
                                                id="end_date"
                                                type="date"
                                                {...register('end_date')}
                                            />
                                            {errors.end_date && <p className="text-sm text-destructive">{errors.end_date.message}</p>}
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="registration_deadline">Registration Deadline</Label>
                                            <Input
                                                id="registration_deadline"
                                                type="date"
                                                {...register('registration_deadline')}
                                            />
                                        </div>
                                    </div>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="format">Format</Label>
                                            <select id="format" {...register('format')} className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm">
                                                <option value="">No format</option>
                                                <option value="league">League (Round Robin)</option>
                                                <option value="group_knockout">Group + Knockout</option>
                                                <option value="knockout">Knockout</option>
                                            </select>
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="pool_size">Pool Size</Label>
                                            <Input id="pool_size" type="number" min={2} max={32} {...register('pool_size', { valueAsNumber: true })} />
                                        </div>
                                    </div>
                                </div>

                                <DialogFooter>
                                    <Button type="button" variant="outline" onClick={closeDialog}>
                                        Cancel
                                    </Button>
                                    <Button type="submit" disabled={isSubmitting}>
                                        <Save className="mr-2 size-4" />
                                        {editingEvent ? 'Update' : 'Save'}
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>
            }
        >
            <Head title="Events" />

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
                    <CardTitle>Events List</CardTitle>
                    <CardDescription>
                        Events are the concrete matches/competitions under a tournament, sport and category.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    {isSuperAdmin && selectedIds.size > 0 && (
                        <div className="mb-4 flex items-center gap-2">
                            <span className="text-sm text-muted-foreground">{selectedIds.size} selected</span>
                            <Button variant="destructive" size="sm" onClick={() => setBatchDelete(true)}>
                                <Trash className="mr-1 size-3" /> Delete Selected
                            </Button>
                        </div>
                    )}
                    <Table>
                        <TableHeader>
                            <TableRow>
                                {isSuperAdmin && <TableHead className="w-10">
                                    <input
                                        type="checkbox"
                                        className="size-4"
                                        checked={events.length > 0 && selectedIds.size === events.length}
                                        onChange={toggleSelectAll}
                                        aria-label="Select all events"
                                    />
                                </TableHead>}
                                <TableHead>Name</TableHead>
                                <TableHead>Tournament</TableHead>
                                <TableHead>Sport / Category</TableHead>
                                <TableHead>Dates</TableHead>
                                <TableHead>Deadline</TableHead>
                                <TableHead>Format</TableHead>
                                <TableHead>Status</TableHead>
                                {isSuperAdmin && <TableHead className="text-right">Actions</TableHead>}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {events.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={isSuperAdmin ? 9 : 8} className="text-center text-muted-foreground">
                                        No events yet.
                                    </TableCell>
                                </TableRow>
                            )}
                            {events.map((event) => {
                                const ep = matchProgress(event.matches_count ?? 0, event.completed_matches_count ?? 0);

                                return (
                                <TableRow key={event.id}>
                                    {isSuperAdmin && (
                                    <TableCell>
                                        <input
                                            type="checkbox"
                                            className="size-4"
                                            checked={selectedIds.has(event.id)}
                                            onChange={() => toggleSelect(event.id)}
                                            aria-label={`Select ${event.name}`}
                                        />
                                    </TableCell>
                                    )}
                                    <TableCell className="font-medium">{event.name}</TableCell>
                                    <TableCell>{event.tournament?.name || '-'}</TableCell>
                                    <TableCell>
                                        {event.sport?.name} / {event.sportCategory?.name}
                                    </TableCell>
                                    <TableCell className="text-sm text-muted-foreground">
                                        {new Date(event.start_date).toLocaleDateString('ms-MY', { day: 'numeric', month: 'short', year: 'numeric' })} {event.end_date ? `→ ${new Date(event.end_date).toLocaleDateString('ms-MY', { day: 'numeric', month: 'short', year: 'numeric' })}` : ''}
                                    </TableCell>
                                    <TableCell className="text-sm text-muted-foreground">
                                        {(event as any).registration_deadline ? new Date((event as any).registration_deadline).toLocaleDateString('ms-MY', { day: 'numeric', month: 'short', year: 'numeric' }) : '-'}
                                    </TableCell>
                                    <TableCell>
                                        <span className="text-xs">{formatLabel((event as any).format)}</span>
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex flex-col gap-1">
                                            <span className={event.is_active ? 'rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700' : 'rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600'}>
                                                {event.is_active ? 'Active' : 'Inactive'}
                                            </span>
                                            <span className={`inline-flex w-fit rounded-full px-2 py-0.5 text-xs ${ep.badge}`}>
                                                {ep.label}
                                            </span>
                                            {(event.matches_count ?? 0) > 0 && (
                                                <div className="flex items-center gap-1">
                                                    <div className="h-1 w-16 overflow-hidden rounded-full bg-gray-200">
                                                        <div
                                                            className={`h-full ${ep.bar}`}
                                                            style={{ width: `${ep.pct}%` }}
                                                        />
                                                    </div>
                                                    <span className="text-[10px] tabular-nums text-muted-foreground">
                                                        {event.completed_matches_count ?? 0}/{event.matches_count ?? 0}
                                                    </span>
                                                </div>
                                            )}
                                        </div>
                                    </TableCell>
                                    {isSuperAdmin && (
                                    <TableCell className="text-right space-x-2">
                                        {(event.pools_count ?? 0) > 0 && (
                                            <Link href={route('events.draw-result', event.slug)}>
                                                <Button variant="secondary" size="sm" title="View draw result">
                                                    <Eye className="mr-1 size-3" /> View Draw
                                                </Button>
                                            </Link>
                                        )}
                                        <Button variant="outline" size="sm" onClick={() => handleDraw(event)} title="Draw groups and generate fixtures">
                                            <Target className="mr-1 size-3" /> Draw
                                        </Button>
                                        <Button variant="outline" size="sm" onClick={() => openEdit(event)}>
                                            <Pencil className="mr-1 size-3" /> Edit
                                        </Button>
                                        <Button variant="destructive" size="sm" onClick={() => setDeleteEvent(event)}>
                                            <Trash2 className="mr-1 size-3" /> Delete
                                        </Button>
                                    </TableCell>
                                    )}
                                </TableRow>
                                );
                            })}
                        </TableBody>
                    </Table>
                </CardContent>

                <Pagination paginator={eventsProp} />
            </Card>

            {isSuperAdmin && (
                <Dialog open={batchDelete} onOpenChange={(isOpen) => !isOpen && setBatchDelete(false)}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Delete {selectedIds.size} Events?</DialogTitle>
                            <DialogDescription>
                                This action cannot be undone. The events and all associated data will be permanently deleted.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setBatchDelete(false)}>
                                Cancel
                            </Button>
                            <Button variant="destructive" onClick={handleBatchDelete}>
                                Yes, Delete All
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            )}

            {isSuperAdmin && (
            <Dialog open={!!drawEvent} onOpenChange={(o) => { if (!o) setDrawEvent(null); }}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Draw & Generate Fixtures?</DialogTitle>
                        <DialogDescription>
                            This will randomly assign confirmed participants into groups and generate round-robin fixtures for <strong>{drawEvent?.name}</strong>.
                            Any existing fixtures for this event will be replaced.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-2 py-2">
                        <Label htmlFor="draw_format">Format</Label>
                        <select
                            id="draw_format"
                            value={drawFormat}
                            onChange={(e) => setDrawFormat(e.target.value)}
                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                        >
                            <option value="group_knockout">Group + Knockout</option>
                            <option value="league">League (Round Robin)</option>
                            <option value="knockout">Knockout</option>
                        </select>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDrawEvent(null)}>Cancel</Button>
                        <Button onClick={() => {
                            const e = drawEvent;
                            setDrawEvent(null);
                            if (e) router.post(route('events.draw', e.slug), { format: drawFormat }, { preserveScroll: true });
                        }}>Yes, Draw</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
            )}

            {isSuperAdmin && (
            <Dialog open={!!deleteEvent} onOpenChange={(isOpen) => !isOpen && setDeleteEvent(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Event?</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete <strong>{deleteEvent?.name}</strong>? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteEvent(null)}>
                            Cancel
                        </Button>
                        <Button variant="destructive" onClick={handleDelete} disabled={isSubmitting}>
                            Yes, Delete
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
            )}

            <div className="mt-6 text-xs text-muted-foreground">
                M2: Event module complete (CRUD + scoped selectors + relations). Part of the core Organization → Session → Tournament → Sport → Event hierarchy.
            </div>
        </AuthenticatedLayout>
    );
}
