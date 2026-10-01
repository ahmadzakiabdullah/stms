import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ConfirmDialog } from '@/components/ConfirmDialog';
import { EmptyState } from '@/components/EmptyState';
import { PageHeader } from '@/components/PageHeader';
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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Head, router } from '@inertiajs/react';
import { Controller, useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { FileText, Pencil, Plus, Save, Search, Trash2, Upload, ExternalLink, X } from 'lucide-react';
import { FormEvent, useEffect, useState } from 'react';
import Pagination from '@/components/Pagination';
import type { Session, Organization, Paginated, SportDocument } from '@/types';
import { formatDate, useI18n } from '@/lib/i18n';

const sessionSchema = z.object({
    organization_id: z.string().min(1, 'Organization is required'),
    name: z.string().min(1, 'Name is required'),
    slug: z.string().optional().default(''),
    description: z.string().optional().default(''),
    start_date: z.string().min(1, 'Start date is required'),
    end_date: z.string().min(1, 'End date is required'),
    event_registration_start_date: z.string().optional().default(''),
    event_registration_deadline: z.string().optional().default(''),
    squad_registration_start_date: z.string().optional().default(''),
    squad_registration_deadline: z.string().optional().default(''),
    is_active: z.boolean(),
});

type SessionForm = z.infer<typeof sessionSchema>;

interface SessionRow extends Session {
    organization?: { name: string } | null;
    documents?: SportDocument[];
}

interface SessionsIndexProps {
    sessions: Paginated<SessionRow> | SessionRow[];
    organizations?: Organization[];
}

export default function SessionsIndex({ sessions: sessionsProp, organizations = [] }: SessionsIndexProps) {
    const { locale, t } = useI18n();
    const [open, setOpen] = useState(false);
    const [editingSession, setEditingSession] = useState<SessionRow | null>(null);
    const [deleteSession, setDeleteSession] = useState<SessionRow | null>(null);
    const [search, setSearch] = useState(() => new URLSearchParams(window.location.search).get('search') ?? '');
    const [documentSession, setDocumentSession] = useState<SessionRow | null>(null);
    const [documentTitle, setDocumentTitle] = useState('');
    const [documentFile, setDocumentFile] = useState<string>('');
    const [availableFiles, setAvailableFiles] = useState<{name: string; path: string}[]>([]);
    const [deleteDocumentTarget, setDeleteDocumentTarget] = useState<SportDocument | null>(null);
    const [logoFile, setLogoFile] = useState<File | null>(null);
    const [inverseLogoFile, setInverseLogoFile] = useState<File | null>(null);
    const [logoPreview, setLogoPreview] = useState<string | null>(null);
    const [inverseLogoPreview, setInverseLogoPreview] = useState<string | null>(null);
    const [removeLogo, setRemoveLogo] = useState(false);
    const [removeInverseLogo, setRemoveInverseLogo] = useState(false);

    const sessions = Array.isArray(sessionsProp) ? sessionsProp : (sessionsProp?.data ?? []);
    const applySearch = (event: FormEvent<HTMLFormElement>) => { event.preventDefault(); router.get(route('sessions.index'), search.trim() ? { search: search.trim() } : {}, { preserveState: true, preserveScroll: true, replace: true }); };
    const clearSearch = () => { setSearch(''); router.get(route('sessions.index'), {}, { preserveState: true, preserveScroll: true, replace: true }); };
    useEffect(() => { if (documentSession) fetch(route('sessions.documents.available', documentSession.slug)).then((response) => response.json()).then((data) => setAvailableFiles(data.files ?? [])); }, [documentSession]);
    const uploadDocument = (event: FormEvent) => { event.preventDefault(); if (!documentSession || !documentFile) return; router.post(route('sessions.documents.select', documentSession.slug), { title: documentTitle, file_path: documentFile }, { onSuccess: () => { setDocumentSession(null); setDocumentTitle(''); setDocumentFile(''); router.reload({ only: ['sessions'] }); } }); };
    const deleteDocument = (document: SportDocument) => setDeleteDocumentTarget(document);
    const confirmDeleteDocument = () => { if (!deleteDocumentTarget) return; router.delete(route('sessions.documents.destroy', deleteDocumentTarget.id), { preserveScroll: true, onFinish: () => setDeleteDocumentTarget(null) }); };

    const { control, register, handleSubmit, reset, formState: { errors, isSubmitting } } = useForm<SessionForm>({
        resolver: zodResolver(sessionSchema),
        defaultValues: {
            organization_id: '',
            name: '',
            slug: '',
            description: '',
            start_date: '',
            end_date: '',
            event_registration_start_date: '',
            event_registration_deadline: '',
            squad_registration_start_date: '',
            squad_registration_deadline: '',
            is_active: true,
        },
    });

    const openCreate = () => {
        setEditingSession(null);
        setLogoFile(null);
        setInverseLogoFile(null);
        setLogoPreview(null);
        setInverseLogoPreview(null);
        setRemoveLogo(false);
        setRemoveInverseLogo(false);
        reset({
            organization_id: organizations && organizations.length > 0 ? organizations[0].id : '',
            name: '',
            slug: '',
            description: '',
            start_date: '',
            end_date: '',
            event_registration_start_date: '',
            event_registration_deadline: '',
            squad_registration_start_date: '',
            squad_registration_deadline: '',
            is_active: true,
        });
        setOpen(true);
    };

    const openEdit = (session: SessionRow) => {
        setEditingSession(session);
        setLogoFile(null);
        setInverseLogoFile(null);
        setLogoPreview(session.logo_url ?? null);
        setInverseLogoPreview(session.inverse_logo_url ?? null);
        setRemoveLogo(false);
        setRemoveInverseLogo(false);

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
            event_registration_start_date: formatForDateInput(session.event_registration_start_date),
            event_registration_deadline: formatForDateInput(session.event_registration_deadline),
            squad_registration_start_date: formatForDateInput(session.squad_registration_start_date),
            squad_registration_deadline: formatForDateInput(session.squad_registration_deadline),
            is_active: session.is_active,
        });
        setOpen(true);
    };

    const closeDialog = () => {
        setOpen(false);
        setEditingSession(null);
        setLogoFile(null);
        setInverseLogoFile(null);
        setLogoPreview(null);
        setInverseLogoPreview(null);
        setRemoveLogo(false);
        setRemoveInverseLogo(false);
        reset();
    };

    const onSubmit = (formData: SessionForm) => {
        const payload = new FormData();
        Object.entries(formData).forEach(([key, value]) => payload.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : String(value ?? '')));
        if (logoFile) payload.append('logo', logoFile);
        if (inverseLogoFile) payload.append('inverse_logo', inverseLogoFile);

        if (editingSession) {
            payload.append('_method', 'put');
            payload.append('remove_logo', removeLogo ? '1' : '0');
            payload.append('remove_inverse_logo', removeInverseLogo ? '1' : '0');
            router.post(route('sessions.update', { session: editingSession.slug }), payload, {
                onSuccess: () => closeDialog(),
            });
        } else {
            router.post(route('sessions.store'), payload, {
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
                <PageHeader
                    title={t('Sessions')}
                    description={t('Manage event sessions (e.g. SUKMA XXI, Paris 2024)')}
                    actions={
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
                                            <Controller
                                                control={control}
                                                name="organization_id"
                                                render={({ field }) => (
                                                    <Select value={field.value || 'none'} onValueChange={(v) => field.onChange(v === 'none' ? '' : v)}>
                                                        <SelectTrigger id="organization_id" className="h-9 w-full" disabled={!!editingSession}>
                                                            <SelectValue placeholder={t('-- Select Organization --')} />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="none">{t('-- Select Organization --')}</SelectItem>
                                                            {organizations.map((org) => (
                                                                <SelectItem key={org.id} value={org.id}>
                                                                    {org.name}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                )}
                                            />
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

                                    <div className="grid gap-3 rounded-xl border bg-muted/20 p-4">
                                        <div>
                                            <Label>{t('Session Branding')}</Label>
                                            <p className="mt-1 text-xs text-muted-foreground">{t('Upload separate official variants for light and dark backgrounds.')}</p>
                                        </div>
                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <div className="rounded-lg border bg-background p-3">
                                                <p className="text-sm font-semibold">{t('Standard logo')}</p>
                                                <p className="text-xs text-muted-foreground">{t('For light backgrounds')}</p>
                                                <div className="mt-3 flex min-h-20 items-center justify-center rounded-md bg-white p-3">
                                                    {logoPreview ? <img src={logoPreview} alt={t('Standard logo preview')} className="max-h-16 max-w-full object-contain" /> : <span className="text-xs text-muted-foreground">{t('No logo uploaded')}</span>}
                                                </div>
                                                <div className="mt-3 flex flex-wrap gap-2">
                                                    <Button type="button" variant="outline" size="sm" onClick={(event) => {
                                                        document.getElementById('session-logo-input')?.click();
                                                    }}><Upload className="mr-1 size-3" />{logoPreview ? t('Change') : t('Upload')}</Button>
                                                    {logoPreview && <Button type="button" variant="ghost" size="sm" onClick={() => { setLogoFile(null); setLogoPreview(null); setRemoveLogo(Boolean(editingSession?.logo_url)); }}>{t('Remove')}</Button>}
                                                </div>
                                                <input id="session-logo-input" type="file" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml,.svg" className="hidden" onChange={(event) => { const file = event.target.files?.[0] ?? null; setLogoFile(file); setLogoPreview(file ? URL.createObjectURL(file) : null); setRemoveLogo(false); }} />
                                            </div>
                                            <div className="rounded-lg border bg-[var(--public-dark)] p-3 text-white">
                                                <p className="text-sm font-semibold">{t('Inverse logo')}</p>
                                                <p className="text-xs text-white/60">{t('For dark backgrounds')}</p>
                                                <div className="mt-3 flex min-h-20 items-center justify-center rounded-md bg-black/30 p-3">
                                                    {inverseLogoPreview ? <img src={inverseLogoPreview} alt={t('Inverse logo preview')} className="max-h-16 max-w-full object-contain" /> : <span className="text-xs text-white/60">{t('No logo uploaded')}</span>}
                                                </div>
                                                <div className="mt-3 flex flex-wrap gap-2">
                                                    <Button type="button" variant="outline" size="sm" className="border-white/20 bg-white/5 text-white hover:bg-white/10 hover:text-white" onClick={(event) => {
                                                        document.getElementById('session-inverse-logo-input')?.click();
                                                    }}><Upload className="mr-1 size-3" />{inverseLogoPreview ? t('Change') : t('Upload')}</Button>
                                                    {inverseLogoPreview && <Button type="button" variant="ghost" size="sm" className="text-rose-300 hover:bg-white/10 hover:text-rose-200" onClick={() => { setInverseLogoFile(null); setInverseLogoPreview(null); setRemoveInverseLogo(Boolean(editingSession?.inverse_logo_url)); }}>{t('Remove')}</Button>}
                                                </div>
                                                <input id="session-inverse-logo-input" type="file" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml,.svg" className="hidden" onChange={(event) => { const file = event.target.files?.[0] ?? null; setInverseLogoFile(file); setInverseLogoPreview(file ? URL.createObjectURL(file) : null); setRemoveInverseLogo(false); }} />
                                            </div>
                                        </div>
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

                                    <div className="grid grid-cols-2 gap-4">
                                      <div className="grid gap-2">
                                        <Label htmlFor="event_registration_start_date">{t('Event Registration Opens')}</Label>
                                        <Input
                                            id="event_registration_start_date"
                                            type="date"
                                            {...register('event_registration_start_date')}
                                        />
                                        {errors.event_registration_start_date && <p className="text-sm text-destructive">{errors.event_registration_start_date.message}</p>}
                                      </div>
                                      <div className="grid gap-2">
                                        <Label htmlFor="event_registration_deadline">{t('Event Registration Closes')}</Label>
                                        <Input
                                            id="event_registration_deadline"
                                            type="date"
                                            {...register('event_registration_deadline')}
                                        />
                                        {errors.event_registration_deadline && <p className="text-sm text-destructive">{errors.event_registration_deadline.message}</p>}
                                      </div>
                                    </div>
                                    <div className="grid grid-cols-2 gap-4">
                                      <div className="grid gap-2">
                                        <Label htmlFor="squad_registration_start_date">{t('Officials & Athletes Open')}</Label>
                                        <Input
                                            id="squad_registration_start_date"
                                            type="date"
                                            {...register('squad_registration_start_date')}
                                        />
                                        {errors.squad_registration_start_date && <p className="text-sm text-destructive">{errors.squad_registration_start_date.message}</p>}
                                      </div>
                                      <div className="grid gap-2">
                                        <Label htmlFor="squad_registration_deadline">{t('Officials & Athletes Closes')}</Label>
                                        <Input
                                            id="squad_registration_deadline"
                                            type="date"
                                            {...register('squad_registration_deadline')}
                                        />
                                        <p className="text-xs text-muted-foreground">{t('Dean approval is still required before squad registration opens.')}</p>
                                        {errors.squad_registration_deadline && <p className="text-sm text-destructive">{errors.squad_registration_deadline.message}</p>}
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
                    }
                />
            }
        >
            <Head title={t('Sessions')} />

            <Card>
                <CardHeader>
                    <CardTitle>{t('Sessions List')}</CardTitle>
                    <CardDescription>
                        {t('Sessions are the top-level containers for tournaments and events.')}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={applySearch} className="mb-4 flex gap-2"><div className="relative flex-1"><Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" /><Input value={search} onChange={(event) => setSearch(event.target.value)} placeholder={t('Search sessions...')} className="pl-9 pr-9" aria-label={t('Search sessions')} />{search && <button type="button" onClick={clearSearch} className="absolute right-2 top-1/2 -translate-y-1/2" aria-label={t('Clear search')}><X className="size-4" /></button>}</div><Button type="submit" variant="secondary">{t('Search')}</Button></form>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{t('Name')}</TableHead>
                                <TableHead>{t('Organization')}</TableHead>
                                <TableHead>{t('Slug')}</TableHead>
                                 <TableHead>{t('Branding')}</TableHead>
                                 <TableHead>{t('Period')}</TableHead>
                                 <TableHead>{t('Registration Deadlines')}</TableHead>
                                 <TableHead>{t('Status')}</TableHead>
                                 <TableHead className="text-right">{t('Actions')}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {sessions.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={8} className="text-center text-muted-foreground">
                                        <EmptyState title={search ? t('No sessions match your search.') : t('No sessions yet. Create the first one.')} />
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
                                    <TableCell>
                                        <div className="flex items-center gap-1.5">
                                            {session.logo_url && <img src={session.logo_url} alt="" className="size-8 rounded border bg-white object-contain p-1" />}
                                            {session.inverse_logo_url && <span className="flex size-8 items-center justify-center rounded bg-[var(--public-dark)] p-1"><img src={session.inverse_logo_url} alt="" className="max-h-full max-w-full object-contain" /></span>}
                                            {!session.logo_url && !session.inverse_logo_url && <span className="text-xs text-muted-foreground">—</span>}
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-sm text-muted-foreground">
                                        {formatDate(session.start_date, locale)} — {formatDate(session.end_date, locale)}
                                    </TableCell>
                                    <TableCell className="text-sm text-muted-foreground">
                                        <div>{session.event_registration_start_date ? formatDate(session.event_registration_start_date, locale) : '—'} → {session.event_registration_deadline ? formatDate(session.event_registration_deadline, locale) : '—'}</div>
                                        <div className="text-xs text-muted-foreground">{session.squad_registration_start_date ? formatDate(session.squad_registration_start_date, locale) : '—'} → {session.squad_registration_deadline ? formatDate(session.squad_registration_deadline, locale) : '—'}</div>
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
                                        <Button variant="outline" size="sm" onClick={() => setDocumentSession(session)}>
                                            <FileText className="mr-1 size-3" /> {t('Documents')}
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

            <Dialog open={!!documentSession} onOpenChange={(open) => !open && setDocumentSession(null)}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader><DialogTitle>{t('Session Documents')}</DialogTitle><DialogDescription>{documentSession?.name}</DialogDescription></DialogHeader>
                    <div className="space-y-2">{(documentSession?.documents?.length ?? 0) === 0 ? <p className="text-sm text-muted-foreground">{t('No documents yet.')}</p> : documentSession?.documents?.map((document) => <div key={document.id} className="flex items-center justify-between rounded border px-3 py-2 text-sm"><span><span className="font-medium">{document.title}</span><span className="ml-2 text-xs text-muted-foreground">{document.file_name}</span></span><span className="flex gap-1"><a href={document.url} target="_blank" rel="noopener noreferrer"><Button type="button" size="sm" variant="ghost"><ExternalLink className="mr-1 size-3" />{t('View')}</Button></a><Button type="button" size="sm" variant="ghost" className="text-destructive" onClick={() => deleteDocument(document)}><Trash2 className="size-3" /></Button></span></div>)}</div>
                    <form onSubmit={uploadDocument} className="grid gap-3 border-t pt-4"><Label>{t('Select General Rules Document')}</Label><Input value={documentTitle} onChange={(event) => setDocumentTitle(event.target.value)} placeholder={t('Document title')} required /><Select value={documentFile ?? ''} onValueChange={setDocumentFile as (value: string) => void}><SelectTrigger><SelectValue placeholder={t('Select existing PDF or Markdown file')} /></SelectTrigger><SelectContent>{availableFiles.map((file) => <SelectItem key={file.path} value={file.path}>{file.name}</SelectItem>)}</SelectContent></Select><div className="flex justify-end"><Button type="submit" disabled={!documentFile || !documentTitle}><FileText className="mr-2 size-4" />{t('Link Document')}</Button></div></form>
                </DialogContent>
            </Dialog>

            <ConfirmDialog
                open={!!deleteSession}
                onOpenChange={(open) => !open && setDeleteSession(null)}
                title={t('Delete Session?')}
                description={<>{t('Are you sure you want to delete')} <strong>{deleteSession?.name}</strong>? {t('This action cannot be undone.')}</>}
                confirmLabel={t('Yes, Delete')}
                cancelLabel={t('Cancel')}
                destructive
                processing={isSubmitting}
                onConfirm={handleDelete}
            />

            <ConfirmDialog
                open={!!deleteDocumentTarget}
                onOpenChange={(open) => !open && setDeleteDocumentTarget(null)}
                title={t('Delete Document?')}
                description={<>{t('Are you sure you want to delete')} <strong>{deleteDocumentTarget?.title}</strong>? {t('This action cannot be undone.')}</>}
                confirmLabel={t('Yes, Delete')}
                cancelLabel={t('Cancel')}
                destructive
                onConfirm={confirmDeleteDocument}
            />

            <div className="mt-6 text-xs text-muted-foreground">
                M2: Session Management. Sessions contain tournaments and events. Next: Tournaments.
            </div>
        </AuthenticatedLayout>
    );
}
