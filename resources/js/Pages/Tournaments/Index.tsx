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
import { Head, router, usePage } from '@inertiajs/react';
import { z } from 'zod';
import { List, Loader, Pencil, Plus, Save, Search, Trash2, X } from 'lucide-react';
import { FormEvent, useState } from 'react';
import Pagination from '@/components/Pagination';
import type { Tournament, Session, Sport, Paginated, Flash } from '@/types';
import { formatDate, useI18n } from '@/lib/i18n';

const tournamentSchema = z.object({
    session_id: z.string().min(1, 'Session is required'),
    name: z.string().min(1, 'Name is required'),
    slug: z.string().optional().default(''),
    description: z.string().optional().default(''),
    start_date: z.string().min(1, 'Start date is required'),
    end_date: z.string().min(1, 'End date is required'),
    is_active: z.boolean(),
    sports: z.array(z.string()).default([]),
});

type TournamentForm = z.infer<typeof tournamentSchema>;

interface TournamentRow extends Omit<Tournament, 'session'> {
    session?: { name: string } | null;
    sports?: Sport[];
}

interface TournamentsIndexProps {
    tournaments: Paginated<TournamentRow> | TournamentRow[];
    sessions: Session[];
    sports: Sport[];
}

export default function TournamentsIndex({ tournaments: tournamentsProp, sessions, sports }: TournamentsIndexProps) {
    const { locale, t } = useI18n();
    const { flash } = usePage().props;
    const [open, setOpen] = useState(false);
    const [editingTournament, setEditingTournament] = useState<TournamentRow | null>(null);
    const [deleteTournament, setDeleteTournament] = useState<TournamentRow | null>(null);
    const [generatingId, setGeneratingId] = useState<string | null>(null);
    const [search, setSearch] = useState(() => new URLSearchParams(window.location.search).get('search') ?? '');

    const applySearch = (event: FormEvent<HTMLFormElement>) => { event.preventDefault(); router.get(route('tournaments.index'), search.trim() ? { search: search.trim() } : {}, { preserveState: true, preserveScroll: true, replace: true }); };
    const clearSearch = () => { setSearch(''); router.get(route('tournaments.index'), {}, { preserveState: true, preserveScroll: true, replace: true }); };

    const handleGenerateEvents = (tournament: TournamentRow) => {
        setGeneratingId(tournament.id);
        router.post(route('tournaments.generate-events', tournament.slug), {}, {
            preserveScroll: true,
            onFinish: () => setGeneratingId(null),
        });
    };

    const tournaments = Array.isArray(tournamentsProp) ? tournamentsProp : (tournamentsProp?.data ?? []);

    const closeDialog = () => {
        setOpen(false);
        setEditingTournament(null);
    };

    const handleDelete = () => {
        if (!deleteTournament) return;
        router.delete(route('tournaments.destroy', deleteTournament.slug), {
            preserveScroll: true,
            onSuccess: () => setDeleteTournament(null),
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">{t('Tournaments')}</h1>
                        <p className="text-sm text-muted-foreground">
                            {t('Manage tournaments within sessions')}
                        </p>
                    </div>

                    <Dialog open={open} onOpenChange={(isOpen) => {
                        if (!isOpen) closeDialog();
                        else setOpen(true);
                    }}>
                        <DialogTrigger asChild>
                            <Button onClick={() => { setEditingTournament(null); setOpen(true); }} disabled={sessions.length === 0}>
                                <Plus className="mr-2 size-4" />
                                {t('Add Tournament')}
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="max-w-lg max-h-[90vh] overflow-y-auto">
                            <TournamentFormDialog t={t}
                                key={editingTournament?.id ?? 'create'}
                                tournament={editingTournament}
                                sessions={sessions}
                                allSports={sports}
                                onClose={closeDialog}
                            />
                        </DialogContent>
                    </Dialog>
                </div>
            }
        >
            <Head title={t('Tournaments')} />

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
                    <CardTitle>{t('Tournaments List')}</CardTitle>
                    <CardDescription>
                        {t('Tournaments are held within sessions under an organization.')}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={applySearch} className="mb-4 flex gap-2"><div className="relative flex-1"><Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" /><Input value={search} onChange={(event) => setSearch(event.target.value)} placeholder={t('Search tournaments...')} className="pl-9 pr-9" aria-label={t('Search tournaments')} />{search && <button type="button" onClick={clearSearch} className="absolute right-2 top-1/2 -translate-y-1/2" aria-label={t('Clear search')}><X className="size-4" /></button>}</div><Button type="submit" variant="secondary">{t('Search')}</Button></form>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{t('Name')}</TableHead>
                                <TableHead>{t('Session')}</TableHead>
                                <TableHead>{t('Sports')}</TableHead>
                                <TableHead>{t('Period')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead className="text-right">{t('Actions')}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {tournaments.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={6} className="text-center text-muted-foreground">
                                        {search ? t('No tournaments match your search.') : t('No tournaments yet. Create the first one.')}
                                    </TableCell>
                                </TableRow>
                            )}
                            {tournaments.map((tournament) => (
                                <TableRow key={tournament.id}>
                                    <TableCell>
                                        <div className="font-medium">{tournament.name}</div>
                                        <code className="rounded bg-muted px-1.5 py-0.5 text-xs">{tournament.slug}</code>
                                    </TableCell>
                                    <TableCell>
                                        {tournament.session?.name || '-'}
                                    </TableCell>
                                    <TableCell>
                                        {tournament.sports && tournament.sports.length > 0
                                            ? tournament.sports.map(s => s.name).join(', ')
                                            : '-'}
                                    </TableCell>
                                    <TableCell className="text-sm text-muted-foreground">
                                        {formatDate(tournament.start_date, locale)} — {formatDate(tournament.end_date, locale)}
                                    </TableCell>
                                    <TableCell>
                                        <span
                                            className={
                                                tournament.is_active
                                                    ? 'rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700'
                                                    : 'rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600'
                                            }
                                        >
                                            {t(tournament.is_active ? 'Active' : 'Inactive')}
                                        </span>
                                    </TableCell>
                                    <TableCell className="text-right space-x-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => handleGenerateEvents(tournament)}
                                            disabled={generatingId === tournament.id}
                                        >
                                            {generatingId === tournament.id
                                                ? <Loader className="mr-1 size-3 animate-spin" />
                                                : <List className="mr-1 size-3" />}
                                            {generatingId === tournament.id ? 'Generating...' : 'Gen Events'}
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => { setEditingTournament(tournament); setOpen(true); }}
                                        >
                                            <Pencil className="mr-1 size-3" /> {t('Edit')}
                                        </Button>
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            onClick={() => setDeleteTournament(tournament)}
                                        >
                                            <Trash2 className="mr-1 size-3" /> {t('Delete')}
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>

                <Pagination paginator={tournamentsProp} />
            </Card>

            <Dialog open={!!deleteTournament} onOpenChange={(isOpen) => !isOpen && setDeleteTournament(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('Delete Tournament?')}</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete <strong>{deleteTournament?.name}</strong>? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteTournament(null)}>
                            {t('Cancel')}
                        </Button>
                        <Button variant="destructive" onClick={handleDelete} disabled={false}>
                            {t('Yes, Delete')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}

function TournamentFormDialog({ tournament, sessions, allSports, onClose, t }: { tournament: TournamentRow | null; sessions: Session[]; allSports: Sport[]; onClose: () => void; t: (key: string) => string }) {
    const [formData, setFormData] = useState(() => ({
        session_id: tournament?.session_id || (sessions.length > 0 ? sessions[0].id : ''),
        name: tournament?.name || '',
        slug: tournament?.slug || '',
        description: tournament?.description || '',
        start_date: tournament?.start_date?.split('T')[0] || '',
        end_date: tournament?.end_date?.split('T')[0] || '',
        is_active: tournament?.is_active ?? true,
        sports: tournament?.sports?.map(s => s.id) || [] as string[],
    }));

    const [errors, setErrors] = useState<Partial<Record<keyof TournamentForm, string>>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [serverError, setServerError] = useState<string | null>(null);

    const set = (field: keyof TournamentForm, value: string | boolean | string[]) => {
        setFormData(prev => ({ ...prev, [field]: value }));
        setErrors(prev => ({ ...prev, [field]: undefined }));
    };

    const toggleSport = (sportId: string) => {
        setFormData(prev => ({
            ...prev,
            sports: prev.sports.includes(sportId)
                ? prev.sports.filter(id => id !== sportId)
                : [...prev.sports, sportId],
        }));
    };

    const onSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const result = tournamentSchema.safeParse(formData);
        if (!result.success) {
            const fieldErrors: Partial<Record<keyof TournamentForm, string>> = {};
            for (const issue of result.error.issues) {
                const field = issue.path[0] as keyof TournamentForm;
                if (!fieldErrors[field]) fieldErrors[field] = issue.message;
            }
            setErrors(fieldErrors);
            return;
        }
        setIsSubmitting(true);
        setServerError(null);
        const options = {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => { setIsSubmitting(false); onClose(); },
            onError: (errors: Record<string, string>) => {
                setIsSubmitting(false);
                const firstError = Object.values(errors)[0];
                if (firstError) setServerError(firstError);
            },
        };
        if (tournament) {
            router.put(route('tournaments.update', tournament.slug), result.data, options);
        } else {
            router.post(route('tournaments.store'), result.data, options);
        }
    };

    return (
        <form onSubmit={onSubmit}>
            <DialogHeader>
                <DialogTitle>{t(tournament ? 'Edit Tournament' : 'Create New Tournament')}</DialogTitle>
                <DialogDescription>
                    {t('Tournaments are competitions held within a session.')}
                </DialogDescription>
            </DialogHeader>

            {serverError && (
                <div className="mb-4 mt-2 rounded-md bg-red-50 p-3 text-sm text-red-700">
                    {serverError}
                </div>
            )}

            <div className="grid gap-4 py-4">
                <div className="grid gap-2">
                    <Label htmlFor="session_id">{t('Session')}</Label>
                    <select
                        id="session_id"
                        className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                        value={formData.session_id}
                        onChange={e => set('session_id', e.target.value)}
                        disabled={!!tournament}
                        required
                    >
                        <option value="">{t('-- Select Session --')}</option>
                        {sessions.map((session) => (
                            <option key={session.id} value={session.id}>
                                {session.name}
                            </option>
                        ))}
                    </select>
                    {errors.session_id && <p className="text-sm text-destructive">{errors.session_id}</p>}
                </div>

                <div className="grid gap-2">
                    <Label>Sports</Label>
                    <div className="grid grid-cols-2 gap-1 max-h-40 overflow-y-auto rounded-md border border-input p-2">
                        {allSports.length === 0 && <p className="text-xs text-muted-foreground col-span-2">{t('No sports available')}</p>}
                        {allSports.map((sport) => (
                            <label key={sport.id} className="flex items-center gap-2 text-sm cursor-pointer hover:bg-muted rounded px-1 py-0.5">
                                <input
                                    type="checkbox"
                                    checked={formData.sports.includes(sport.id)}
                                    onChange={() => toggleSport(sport.id)}
                                    className="size-4"
                                />
                                {sport.name}
                            </label>
                        ))}
                    </div>
                    {errors.sports && <p className="text-sm text-destructive">{errors.sports}</p>}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="name">{t('Tournament Name')}</Label>
                    <Input
                        id="name"
                        value={formData.name}
                        onChange={e => set('name', e.target.value)}
                        placeholder={t('e.g. Inter-Faculty Sports 2026')}
                        required
                    />
                    {errors.name && <p className="text-sm text-destructive">{errors.name}</p>}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="slug">Slug</Label>
                    <Input
                        id="slug"
                        value={formData.slug}
                        onChange={e => set('slug', e.target.value)}
                        placeholder="inter-faculty-sports-2026"
                    />
                    {errors.slug && <p className="text-sm text-destructive">{errors.slug}</p>}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="description">Description</Label>
                    <textarea
                        id="description"
                        className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        value={formData.description}
                        onChange={e => set('description', e.target.value)}
                        placeholder={t('Brief description of the tournament')}
                    />
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="start_date">Start Date</Label>
                        <Input
                            id="start_date"
                            type="date"
                            value={formData.start_date}
                            onChange={e => set('start_date', e.target.value)}
                            required
                        />
                        {errors.start_date && <p className="text-sm text-destructive">{errors.start_date}</p>}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="end_date">End Date</Label>
                        <Input
                            id="end_date"
                            type="date"
                            value={formData.end_date}
                            onChange={e => set('end_date', e.target.value)}
                        />
                        {errors.end_date && <p className="text-sm text-destructive">{errors.end_date}</p>}
                    </div>
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="is_active">{t('Status')}</Label>
                    <label className="flex items-center gap-2 text-sm cursor-pointer">
                        <input
                            type="checkbox"
                            checked={formData.is_active}
                            onChange={e => set('is_active', e.target.checked)}
                            className="size-4"
                        />
                        Active
                    </label>
                </div>
            </div>

            <DialogFooter>
                <Button type="button" variant="outline" onClick={onClose}>
                    Cancel
                </Button>
                <Button type="submit" disabled={isSubmitting}>
                    <Save className="mr-2 size-4" />
                    {t(tournament ? 'Update' : 'Save')}
                </Button>
            </DialogFooter>
        </form>
    );
}
