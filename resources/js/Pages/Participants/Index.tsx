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
import ParticipantLogo from '@/components/ParticipantLogo';
import { Head, router, usePage } from '@inertiajs/react';
import { Upload } from 'lucide-react';
import { z } from 'zod';
import { Eye, Pencil, Plus, Save, Trash2 } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { useI18n } from '@/lib/i18n';
import type { Participant, Session, Paginated, Flash } from '@/types';

const participantSchema = z.object({
    session_id: z.string().nullable().optional().default(''),
    name: z.string().min(1, 'Name is required'),
    slug: z.string().regex(/^[a-zA-Z0-9_-]*$/, 'Must be alphanumeric with dashes or underscores').optional().default(''),
    email: z.string().email('Invalid email address').optional().or(z.literal('')).default(''),
    phone: z.string().optional().default(''),
    participant_type: z.enum(['individual', 'team']).default('individual'),
    team_name: z.string().optional().default(''),
    status: z.string().optional().default('registered'),
    notes: z.string().optional().default(''),
    is_active: z.boolean().default(true),
});

type ParticipantForm = z.infer<typeof participantSchema>;

interface ParticipantRow extends Participant {
    slug: string;
}

interface ParticipantsIndexProps {
    participants: Paginated<ParticipantRow> | ParticipantRow[];
    sessions?: Session[];
}

const statusColors: Record<string, string> = {
    registered: 'bg-blue-100 text-blue-700',
    confirmed: 'bg-emerald-100 text-emerald-700',
    withdrawn: 'bg-yellow-100 text-yellow-700',
    disqualified: 'bg-red-100 text-red-700',
};

export default function ParticipantsIndex({ participants: participantsProp, sessions: sessionsProp = [] }: ParticipantsIndexProps) {
    const { flash } = usePage().props;
    const { t } = useI18n();
    const [open, setOpen] = useState(false);
    const [editingParticipant, setEditingParticipant] = useState<ParticipantRow | null>(null);
    const [deleteParticipant, setDeleteParticipant] = useState<ParticipantRow | null>(null);
    const [viewParticipant, setViewParticipant] = useState<ParticipantRow | null>(null);

    const participants = Array.isArray(participantsProp) ? participantsProp : (participantsProp?.data ?? []);
    const sessions = Array.isArray(sessionsProp) ? sessionsProp : (sessionsProp ?? []);

    const closeDialog = useCallback(() => {
        setOpen(false);
        setEditingParticipant(null);
    }, []);

    const handleDelete = () => {
        if (!deleteParticipant) return;
        router.delete(route('participants.destroy', deleteParticipant.slug), {
            preserveScroll: true,
            onSuccess: () => setDeleteParticipant(null),
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">{t('Participants')}</h1>
                        <p className="text-sm text-muted-foreground">
                            {t('Manage athletes and teams participating in tournaments')}
                        </p>
                    </div>

                    <Dialog open={open} onOpenChange={(isOpen) => {
                        if (!isOpen) closeDialog();
                        else setOpen(true);
                    }}>
                        <DialogTrigger asChild>
                            <Button onClick={() => { setEditingParticipant(null); setOpen(true); }}>
                                <Plus className="mr-2 size-4" />
                                {t('Add Participant')}
                            </Button>
                        </DialogTrigger>
                            <DialogContent className="max-h-[90vh] max-w-2xl overflow-y-auto">
                                <ParticipantFormDialog
                                    key={editingParticipant?.id ?? 'create'}
                                    participant={editingParticipant}
                                    sessions={sessions}
                                    onClose={closeDialog}
                                />
                            </DialogContent>
                    </Dialog>
                </div>
            }
        >
            <Head title={t('Participants')} />

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
                    <CardTitle>{t('Participants List')}</CardTitle>
                    <CardDescription>
                        {t('Athletes and teams registered in the system...')}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{t('Name')}</TableHead>
                                <TableHead>{t('Contact')}</TableHead>
                                <TableHead>{t('Type')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead>{t('User')}</TableHead>
                                <TableHead className="text-right">{t('Actions')}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {participants.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={6} className="text-center text-muted-foreground">
                                        {t('No participants yet.')}
                                    </TableCell>
                                </TableRow>
                            )}
                            {participants.map((participant) => (
                                <TableRow key={participant.id}>
                                    <TableCell className="font-medium">
                                        <div className="flex items-center gap-3">
                                            <ParticipantLogo participant={participant} size="sm" alt="" />
                                            <span className="truncate">{participant.name}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-sm">
                                        {participant.email && <div>{participant.email}</div>}
                                        {participant.phone && <div className="text-muted-foreground">{participant.phone}</div>}
                                    </TableCell>
                                    <TableCell>
                                        <span className="capitalize rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-700">
                                            {participant.participant_type}
                                        </span>
                                        {participant.team_name && (
                                            <div className="text-xs text-muted-foreground mt-1">{participant.team_name}</div>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <span className={`rounded-full px-2 py-0.5 text-xs capitalize ${statusColors[participant.status] || 'bg-gray-100 text-gray-600'}`}>
                                            {participant.status}
                                        </span>
                                    </TableCell>
                                    <TableCell className="text-sm">
                                        {participant.users && participant.users.length > 0
                                            ? participant.users.map(u => (
                                                <div key={u.uuid} className="truncate max-w-[180px]" title={u.email}>{u.name}</div>
                                              ))
                                            :                                          <span className="text-muted-foreground text-xs italic">{t('No user')}</span>
                                        }
                                    </TableCell>
                                    <TableCell className="text-right space-x-2">
                                        <Button variant="ghost" size="sm" onClick={() => setViewParticipant(participant)}>
                                            <Eye className="size-3" />
                                        </Button>
                                        <Button variant="outline" size="sm" onClick={() => { setEditingParticipant(participant); setOpen(true); }}>
                                            <Pencil className="mr-1 size-3" /> {t('Edit')}
                                        </Button>
                                        <Button variant="destructive" size="sm" onClick={() => setDeleteParticipant(participant)}>
                                            <Trash2 className="mr-1 size-3" /> {t('Delete')}
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

                    {!Array.isArray(participantsProp) && participantsProp?.links && (
                        <div className="mt-4">
                            <Pagination links={participantsProp.links} />
                        </div>
                    )}
                </CardContent>
            </Card>

            <Dialog open={!!deleteParticipant} onOpenChange={(isOpen) => !isOpen && setDeleteParticipant(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('Delete Participant?')}</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete <strong>{deleteParticipant?.name}</strong>? This will also remove all their tournament registrations. This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteParticipant(null)}>
                            {t('Cancel')}
                        </Button>
                        <Button variant="destructive" onClick={handleDelete} disabled={false}>
                            {t('Yes, Delete')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={!!viewParticipant} onOpenChange={(isOpen) => !isOpen && setViewParticipant(null)}>
                <DialogContent className="max-w-xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <div className="flex items-center gap-3">
                            {viewParticipant && <ParticipantLogo participant={viewParticipant} size="lg" alt="" />}
                            <div>
                                <DialogTitle>{viewParticipant?.name}</DialogTitle>
                                <DialogDescription>{t('Full participant details')}</DialogDescription>
                            </div>
                        </div>
                    </DialogHeader>

                    {viewParticipant && (
                        <div className="grid gap-6 py-4 text-sm">
                            <div>
                                <h4 className="mb-2 font-semibold text-foreground">{t('Brand assets')}</h4>
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <div className="flex items-center gap-3 rounded-lg border bg-white p-3">
                                        <ParticipantLogo participant={viewParticipant} size="xl" alt="" />
                                        <div><p className="font-medium">{t('Standard logo')}</p><p className="text-xs text-muted-foreground">{t('For light backgrounds')}</p></div>
                                    </div>
                                    <div className="flex items-center gap-3 rounded-lg border border-slate-700 bg-slate-950 p-3 text-white">
                                        <ParticipantLogo participant={viewParticipant} surface="dark" size="xl" alt="" />
                                        <div><p className="font-medium">{t('Inverse logo')}</p><p className="text-xs text-white/60">{t('For dark backgrounds')}</p></div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h4 className="mb-2 font-semibold text-foreground">{t('Participant Information')}</h4>
                                <div className="grid grid-cols-2 gap-3 rounded-md border bg-muted/30 p-3">
                                    <div><span className="text-muted-foreground">{t('Name')}</span><p className="font-medium">{viewParticipant.name}</p></div>
                                    <div><span className="text-muted-foreground">{t('Slug')}</span><p className="font-medium">{viewParticipant.slug}</p></div>
                                    <div><span className="text-muted-foreground">{t('Email')}</span><p className="font-medium">{viewParticipant.email || '—'}</p></div>
                                    <div><span className="text-muted-foreground">{t('Phone')}</span><p className="font-medium">{viewParticipant.phone || '—'}</p></div>
                                    <div><span className="text-muted-foreground">{t('Type')}</span><p className="font-medium capitalize">{viewParticipant.participant_type}</p></div>
                                    <div><span className="text-muted-foreground">{t('Team Name')}</span><p className="font-medium">{viewParticipant.team_name || '—'}</p></div>
                                    <div><span className="text-muted-foreground">{t('Status')}</span><p className="font-medium capitalize">{viewParticipant.status}</p></div>
                                    <div><span className="text-muted-foreground">Active</span><p className="font-medium">{viewParticipant.is_active ? t('Yes') : t('No')}</p></div>
                                    <div className="col-span-2"><span className="text-muted-foreground">{t('Notes')}</span><p className="font-medium">{viewParticipant.notes || '—'}</p></div>
                                    <div><span className="text-muted-foreground">Created</span><p className="font-medium">{new Date(viewParticipant.created_at).toLocaleDateString('ms-MY', { day: 'numeric', month: 'short', year: 'numeric' })}</p></div>
                                    <div><span className="text-muted-foreground">Updated</span><p className="font-medium">{new Date(viewParticipant.updated_at).toLocaleDateString('ms-MY', { day: 'numeric', month: 'short', year: 'numeric' })}</p></div>
                                </div>
                            </div>

                            <div>
                                <h4 className="mb-2 font-semibold text-foreground">{t('Linked User Accounts')}</h4>
                                {viewParticipant.users && viewParticipant.users.length > 0 ? (
                                    <div className="space-y-2">
                                        {viewParticipant.users.map(u => (
                                            <div key={u.uuid} className="grid grid-cols-2 gap-3 rounded-md border bg-muted/30 p-3">
                                                <div><span className="text-muted-foreground">{t('Name')}</span><p className="font-medium">{u.name}</p></div>
                                                <div><span className="text-muted-foreground">{t('Email')}</span><p className="font-medium">{u.email}</p></div>
                                                <div className="col-span-2">
                                                    <span className="text-muted-foreground">Roles</span>
                                                    <p className="font-medium">
                                                        {u.roles && u.roles.length > 0
                                                            ? u.roles.map(r => r.name).join(', ')
                                                            : '—'}
                                                    </p>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="rounded-md border bg-muted/30 p-3 text-center text-muted-foreground">
                                        {t('No user accounts linked to this participant.')}
                                    </div>
                                )}
                            </div>
                        </div>
                    )}

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setViewParticipant(null)}>
                            {t('Close')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <div className="mt-6 text-xs text-muted-foreground">
                M3: Participant module complete. Part of the Participant & Registration system.
            </div>
        </AuthenticatedLayout>
    );
}

interface FormData {
    session_id: string;
    name: string;
    slug: string;
    email: string;
    phone: string;
    participant_type: string;
    team_name: string;
    status: string;
    notes: string;
    is_active: boolean;
}

function ParticipantFormDialog({ participant, sessions, onClose }: { participant: ParticipantRow | null; sessions: Session[]; onClose: () => void }) {
    const { t } = useI18n();
    const [formData, setFormData] = useState<FormData>(() => participant ? {
        session_id: participant.session_id || '',
        name: participant.name,
        slug: participant.slug,
        email: participant.email || '',
        phone: participant.phone || '',
        participant_type: participant.participant_type || 'individual',
        team_name: participant.team_name || '',
        status: participant.status || 'registered',
        notes: participant.notes || '',
        is_active: participant.is_active ?? true,
    } : {
        session_id: sessions.length > 0 ? sessions[0].id : '',
        name: '',
        slug: '',
        email: '',
        phone: '',
        participant_type: 'individual',
        team_name: '',
        status: 'registered',
        notes: '',
        is_active: true,
    });

    const [errors, setErrors] = useState<Partial<Record<keyof FormData | 'logo' | 'inverse_logo', string>>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [logoFile, setLogoFile] = useState<File | null>(null);
    const [inverseLogoFile, setInverseLogoFile] = useState<File | null>(null);
    const [logoPreview, setLogoPreview] = useState<string | null>(participant?.logo_url ?? null);
    const [inverseLogoPreview, setInverseLogoPreview] = useState<string | null>(participant?.inverse_logo_url ?? null);
    const [removeLogo, setRemoveLogo] = useState(false);
    const [removeInverseLogo, setRemoveInverseLogo] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);
    const inverseFileInputRef = useRef<HTMLInputElement>(null);

    useEffect(() => () => {
        if (logoPreview?.startsWith('blob:')) URL.revokeObjectURL(logoPreview);
        if (inverseLogoPreview?.startsWith('blob:')) URL.revokeObjectURL(inverseLogoPreview);
    }, [logoPreview, inverseLogoPreview]);

    const set = (field: keyof FormData, value: string | boolean) => {
        setFormData(prev => ({ ...prev, [field]: value }));
        setErrors(prev => ({ ...prev, [field]: undefined }));
    };

    const handleLogoChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;
        setLogoFile(file);
        setLogoPreview(URL.createObjectURL(file));
        setRemoveLogo(false);
        setErrors(prev => ({ ...prev, logo: undefined }));
    };

    const handleInverseLogoChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;
        setInverseLogoFile(file);
        setInverseLogoPreview(URL.createObjectURL(file));
        setRemoveInverseLogo(false);
        setErrors(prev => ({ ...prev, inverse_logo: undefined }));
    };

    const clearLogo = () => {
        setLogoFile(null);
        setLogoPreview(null);
        setRemoveLogo(Boolean(participant?.logo_url));
        if (fileInputRef.current) fileInputRef.current.value = '';
    };

    const clearInverseLogo = () => {
        setInverseLogoFile(null);
        setInverseLogoPreview(null);
        setRemoveInverseLogo(Boolean(participant?.inverse_logo_url));
        if (inverseFileInputRef.current) inverseFileInputRef.current.value = '';
    };

    const onSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setErrors({});
        setIsSubmitting(true);

        const payload = { ...formData } as Record<string, string | boolean>;
        const fd = new FormData();
        for (const key of Object.keys(payload)) {
            const value = payload[key];
            fd.append(key, typeof value === 'boolean' ? (value ? '1' : '0') : (value ?? ''));
        }
        if (logoFile) {
            fd.append('logo', logoFile);
        }
        if (inverseLogoFile) {
            fd.append('inverse_logo', inverseLogoFile);
        }
        if (participant) {
            fd.append('remove_logo', removeLogo ? '1' : '0');
            fd.append('remove_inverse_logo', removeInverseLogo ? '1' : '0');
        }
        const options = {
            onSuccess: () => onClose(),
            onError: (serverErrors: Record<string, string>) => setErrors(serverErrors),
            onFinish: () => setIsSubmitting(false),
        };

        try {
            if (participant) {
                fd.append('_method', 'PUT');
                router.post(route('participants.update', participant.slug), fd, options);
            } else {
                router.post(route('participants.store'), fd, options);
            }
        } catch {
            setErrors({ logo: 'Unable to submit the form. Please refresh the page and try again.' });
            setIsSubmitting(false);
        }
    };

    return (
        <form onSubmit={onSubmit}>
            <DialogHeader>
                <DialogTitle>{participant ? t('Edit Participant') : t('Create New Participant')}</DialogTitle>
                <DialogDescription>
                    {t('Register a new athlete or team in the system.')}
                </DialogDescription>
            </DialogHeader>

            <div className="grid gap-4 py-4">
                <div className="grid gap-2">
                    <Label htmlFor="name">{t('Name *')}</Label>
                    <Input
                        id="name"
                        value={formData.name}
                        onChange={e => set('name', e.target.value)}
                        placeholder={t('e.g. Ahmad bin Abdullah')}
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
                        placeholder={t('ahmad-bin-abdullah')}
                    />
                    {errors.slug && <p className="text-sm text-destructive">{errors.slug}</p>}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="session_id">{t('Session')}</Label>
                    <select
                        id="session_id"
                        className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                        value={formData.session_id}
                        onChange={e => set('session_id', e.target.value)}
                    >
                        <option value="">{t('-- Select Session --')}</option>
                        {sessions.map((s) => (
                            <option key={s.id} value={s.id}>{s.name}</option>
                        ))}
                    </select>
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="email">{t('Email')}</Label>
                        <Input
                            id="email"
                            type="email"
                            value={formData.email}
                            onChange={e => set('email', e.target.value)}
                            placeholder={t('ahmad@example.com')}
                        />
                        {errors.email && <p className="text-sm text-destructive">{errors.email}</p>}
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="phone">{t('Phone')}</Label>
                        <Input
                            id="phone"
                            value={formData.phone}
                            onChange={e => set('phone', e.target.value)}
                            placeholder={t('0123456789')}
                        />
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="participant_type">{t('Type')}</Label>
                        <select
                            id="participant_type"
                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                            value={formData.participant_type}
                            onChange={e => set('participant_type', e.target.value)}
                        >
                            <option value="individual">{t('Individual')}</option>
                            <option value="team">{t('Team')}</option>
                        </select>
                    </div>
                </div>

                {formData.participant_type === 'team' && (
                    <div className="grid gap-2">
                        <Label htmlFor="team_name">{t('Team Name')}</Label>
                        <Input
                            id="team_name"
                            value={formData.team_name}
                            onChange={e => set('team_name', e.target.value)}
                            placeholder={t('e.g. UFTE Team')}
                        />
                    </div>
                )}

                <div className="grid gap-2">
                    <Label htmlFor="notes">{t('Notes')}</Label>
                    <textarea
                        id="notes"
                        className="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        value={formData.notes}
                        onChange={e => set('notes', e.target.value)}
                    />
                </div>

                <div className="grid gap-3">
                    <div><Label>{t('Logo / Crest')}</Label><p className="mt-1 text-xs text-muted-foreground">{t('Upload separate official variants for light and dark backgrounds.')}</p></div>
                    <div className="grid gap-3 sm:grid-cols-2">
                        <div className="rounded-xl border bg-white p-3">
                            <div className="flex min-h-24 items-center justify-center rounded-lg border border-dashed bg-slate-50 p-3">
                                <ParticipantLogo participant={{ name: formData.name, logo_url: logoPreview }} size="xl" alt={t('Standard logo preview')} />
                            </div>
                            <div className="mt-3"><p className="text-sm font-semibold">{t('Standard logo')}</p><p className="text-xs text-muted-foreground">{t('For light backgrounds')}</p></div>
                            <div className="mt-3 flex flex-wrap gap-2">
                                <Button type="button" variant="outline" size="sm" onClick={() => fileInputRef.current?.click()}><Upload className="mr-1 size-3" />{logoPreview ? t('Change') : t('Upload')}</Button>
                                {logoPreview && <Button type="button" variant="ghost" size="sm" className="text-destructive hover:text-destructive" onClick={clearLogo}><Trash2 className="mr-1 size-3" />{t('Remove')}</Button>}
                            </div>
                            <input ref={fileInputRef} type="file" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml,.svg" className="hidden" onChange={handleLogoChange} />
                            {logoFile && <p className="mt-2 truncate text-[10px] text-muted-foreground">{logoFile.name}</p>}
                            {errors.logo && <p className="mt-2 text-sm text-destructive">{errors.logo}</p>}
                        </div>

                        <div className="rounded-xl border border-slate-700 bg-slate-950 p-3 text-white">
                            <div className="flex min-h-24 items-center justify-center rounded-lg border border-dashed border-white/20 bg-white/5 p-3">
                                <ParticipantLogo participant={{ name: formData.name, inverse_logo_url: inverseLogoPreview }} surface="dark" size="xl" alt={t('Inverse logo preview')} />
                            </div>
                            <div className="mt-3"><p className="text-sm font-semibold">{t('Inverse logo')}</p><p className="text-xs text-white/60">{t('For dark backgrounds')}</p></div>
                            <div className="mt-3 flex flex-wrap gap-2">
                                <Button type="button" variant="outline" size="sm" className="border-white/20 bg-white/5 text-white hover:bg-white/10 hover:text-white" onClick={() => inverseFileInputRef.current?.click()}><Upload className="mr-1 size-3" />{inverseLogoPreview ? t('Change') : t('Upload')}</Button>
                                {inverseLogoPreview && <Button type="button" variant="ghost" size="sm" className="text-rose-300 hover:bg-white/10 hover:text-rose-200" onClick={clearInverseLogo}><Trash2 className="mr-1 size-3" />{t('Remove')}</Button>}
                            </div>
                            <input ref={inverseFileInputRef} type="file" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml,.svg" className="hidden" onChange={handleInverseLogoChange} />
                            {inverseLogoFile && <p className="mt-2 truncate text-[10px] text-white/60">{inverseLogoFile.name}</p>}
                            {errors.inverse_logo && <p className="mt-2 text-sm text-rose-300">{errors.inverse_logo}</p>}
                        </div>
                    </div>
                    <p className="text-[10px] text-muted-foreground">{t('JPEG, PNG, GIF, WebP or SVG. Maximum 2 MB.')}</p>
                </div>
            </div>

            <DialogFooter>
                <Button type="button" variant="outline" onClick={onClose}>
                    {t('Cancel')}
                </Button>
                <Button type="submit">
                    <Save className="mr-2 size-4" />
                    {isSubmitting ? t('Processing...') : (participant ? t('Update') : t('Save'))}
                </Button>
            </DialogFooter>
        </form>
    );
}
