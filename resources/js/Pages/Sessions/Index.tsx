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
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Pencil, Plus, Save, Trash2 } from 'lucide-react';
import { useState } from 'react';
import Pagination from '@/components/Pagination';
import type { Session, Organization, Paginated, Flash } from '@/types';
import { formatDate, useI18n } from '@/lib/i18n';

const sessionSchema = z.object({
    organization_id: z.string().min(1, 'Organization is required'),
    name: z.string().min(1, 'Name is required'),
    slug: z.string().optional().default(''),
    description: z.string().optional().default(''),
    start_date: z.string().min(1, 'Start date is required'),
    end_date: z.string().min(1, 'End date is required'),
    is_active: z.boolean(),
});

type SessionForm = z.infer<typeof sessionSchema>;

interface SessionRow extends Session {
    organization?: { name: string } | null;
}

interface SessionsIndexProps {
    sessions: Paginated<SessionRow> | SessionRow[];
    organizations?: Organization[];
}

export default function SessionsIndex({ sessions: sessionsProp, organizations = [] }: SessionsIndexProps) {
    const { locale, t } = useI18n();
    const { flash } = usePage().props;
    const [open, setOpen] = useState(false);
    const [editingSession, setEditingSession] = useState<SessionRow | null>(null);
    const [deleteSession, setDeleteSession] = useState<SessionRow | null>(null);

    const sessions = Array.isArray(sessionsProp) ? sessionsProp : (sessionsProp?.data ?? []);

    const { register, handleSubmit, reset, formState: { errors, isSubmitting } } = useForm<SessionForm>({
        resolver: zodResolver(sessionSchema),
        defaultValues: {
            organization_id: '',
            name: '',
            slug: '',
            description: '',
            start_date: '',
            end_date: '',
            is_active: true,
        },
    });

    const openCreate = () => {
        setEditingSession(null);
        reset({
            organization_id: organizations && organizations.length > 0 ? organizations[0].id : '',
            name: '',
            slug: '',
            description: '',
            start_date: '',
            end_date: '',
            is_active: true,
        });
        setOpen(true);
    };

    const openEdit = (session: SessionRow) => {
        setEditingSession(session);

        const formatForDateInput = (dateStr: string) => {
            if (!dateStr) return '';
            return dateStr.split('T')[0];
        };

        reset({
            organization_id: session.organization_id || '',
            name: session.name,
            slug: session.slug,
            description: session.description || '',
            start_date: formatForDateInput(session.start_date),
            end_date: formatForDateInput(session.end_date),
            is_active: session.is_active,
        });
        setOpen(true);
    };

    const closeDialog = () => {
        setOpen(false);
        setEditingSession(null);
        reset();
    };

    const onSubmit = (formData: SessionForm) => {
        if (editingSession) {
            router.put(route('sessions.update', { session: editingSession.slug }), formData, {
                onSuccess: () => closeDialog(),
            });
        } else {
            router.post(route('sessions.store'), formData, {
                onSuccess: () => closeDialog(),
            });
        }
    };

    const handleDelete = () => {
        if (!deleteSession) return;
        router.delete(route('sessions.destroy', { session: deleteSession.slug }), {
            preserveScroll: true,
            onSuccess: () => setDeleteSession(null),
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">{t('Sessions')}</h1>
                        <p className="text-sm text-muted-foreground">
                            {t('Manage event sessions (e.g. SUKMA XXI, Paris 2024)')}
                        </p>
                    </div>

                    <Dialog open={open} onOpenChange={(isOpen) => {
                        if (!isOpen) closeDialog();
                        else setOpen(true);
                    }}>
                        <DialogTrigger asChild>
                            <Button onClick={openCreate}>
                                <Plus className="mr-2 size-4" />
                                {t('Add Session')}
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="max-w-lg">
                            <form onSubmit={handleSubmit(onSubmit)}>
                                <DialogHeader>
                                    <DialogTitle>{t(editingSession ? 'Edit Session' : 'Create New Session')}</DialogTitle>
                                    <DialogDescription>
                                        {t('A session groups tournaments and events over a period of time.')}
                                    </DialogDescription>
                                </DialogHeader>

                                <div className="grid gap-4 py-4">
                                    {organizations && organizations.length > 0 && (
                                        <div className="grid gap-2">
                                            <Label htmlFor="organization_id">{t('Organization')}</Label>
                                            <select
                                                id="organization_id"
                                                className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                                                {...register('organization_id')}
                                                disabled={!!editingSession}
                                                required
                                            >
                                                <option value="">{t('-- Select Organization --')}</option>
                                                {organizations.map((org) => (
                                                    <option key={org.id} value={org.id}>
                                                        {org.name}
                                                    </option>
                                                ))}
                                            </select>
                                            {errors.organization_id && <p className="text-sm text-destructive">{errors.organization_id.message}</p>}
                                        </div>
                                    )}

                                    <div className="grid gap-2">
                                        <Label htmlFor="name">{t('Session Name')}</Label>
                                        <Input
                                            id="name"
                                            {...register('name')}
                                            placeholder={t('e.g. SUKMA XXI or Paris 2024')}
                                            required
                                        />
                                        {errors.name && <p className="text-sm text-destructive">{errors.name.message}</p>}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="slug">{t('Slug (unique)')}</Label>
                                        <Input
                                            id="slug"
                                            {...register('slug')}
                                            placeholder={t('sukma-xxi')}
                                        />
                                        {errors.slug && <p className="text-sm text-destructive">{errors.slug.message}</p>}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="description">{t('Description (optional)')}</Label>
                                        <textarea
                                            id="description"
                                            className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                            {...register('description')}
                                            placeholder={t('Brief description of the session')}
                                        />
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="start_date">{t('Start Date')}</Label>
                                            <Input
                                                id="start_date"
                                                type="date"
                                                {...register('start_date')}
                                                required
                                            />
                                            {errors.start_date && <p className="text-sm text-destructive">{errors.start_date.message}</p>}
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="end_date">{t('End Date')}</Label>
                                            <Input
                                                id="end_date"
                                                type="date"
                                                {...register('end_date')}
                                                required
                                            />
                                            {errors.end_date && <p className="text-sm text-destructive">{errors.end_date.message}</p>}
                                        </div>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="is_active">{t('Status')}</Label>
                                        <label className="flex items-center gap-2 text-sm">
                                            <input
                                                type="checkbox"
                                                {...register('is_active')}
                                            />
                                            {t('Active')}
                                        </label>
                                    </div>
                                </div>

                                <DialogFooter>
                                    <Button type="button" variant="outline" onClick={closeDialog}>
                                        {t('Cancel')}
                                    </Button>
                                    <Button type="submit" disabled={isSubmitting}>
                                        <Save className="mr-2 size-4" />
                                        {t(editingSession ? 'Update' : 'Save')}
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>
            }
        >
            <Head title={t('Sessions')} />

            {flash?.success && (
                <div className="mb-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-700">
                    {flash.success}
                </div>
            )}

            <Card>
                <CardHeader>
                    <CardTitle>{t('Sessions List')}</CardTitle>
                    <CardDescription>
                        {t('Sessions are the top-level containers for tournaments and events.')}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{t('Name')}</TableHead>
                                <TableHead>{t('Organization')}</TableHead>
                                <TableHead>{t('Slug')}</TableHead>
                                <TableHead>{t('Period')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead className="text-right">{t('Actions')}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {sessions.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={6} className="text-center text-muted-foreground">
                                        {t('No sessions yet. Create the first one.')}
                                    </TableCell>
                                </TableRow>
                            )}
                            {sessions.map((session) => (
                                <TableRow key={session.id}>
                                    <TableCell className="font-medium">{session.name}</TableCell>
                                    <TableCell>
                                        {session.organization?.name || '-'}
                                    </TableCell>
                                    <TableCell>
                                        <code className="rounded bg-muted px-1.5 py-0.5 text-xs">{session.slug}</code>
                                    </TableCell>
                                    <TableCell className="text-sm text-muted-foreground">
                                        {formatDate(session.start_date, locale)} — {formatDate(session.end_date, locale)}
                                    </TableCell>
                                    <TableCell>
                                        <span
                                            className={
                                                session.is_active
                                                    ? 'rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700'
                                                    : 'rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600'
                                            }
                                        >
                                            {t(session.is_active ? 'Active' : 'Inactive')}
                                        </span>
                                    </TableCell>
                                    <TableCell className="text-right space-x-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => openEdit(session)}
                                        >
                                            <Pencil className="mr-1 size-3" /> {t('Edit')}
                                        </Button>
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            onClick={() => setDeleteSession(session)}
                                        >
                                            <Trash2 className="mr-1 size-3" /> {t('Delete')}
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>

                <Pagination paginator={sessionsProp} />
            </Card>

            <Dialog open={!!deleteSession} onOpenChange={(isOpen) => !isOpen && setDeleteSession(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('Delete Session?')}</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete <strong>{deleteSession?.name}</strong>? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteSession(null)}>
                            Cancel
                        </Button>
                        <Button variant="destructive" onClick={handleDelete} disabled={isSubmitting}>
                            {t('Yes, Delete')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <div className="mt-6 text-xs text-muted-foreground">
                M2: Session Management. Sessions contain tournaments and events. Next: Tournaments.
            </div>
        </AuthenticatedLayout>
    );
}
