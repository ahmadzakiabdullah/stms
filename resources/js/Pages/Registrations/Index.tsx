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
import { useI18n } from '@/lib/i18n';
import type { Registration, Tournament, Participant, Paginated, Flash } from '@/types';

const registrationSchema = z.object({
    tournament_id: z.string().min(1, 'Tournament is required'),
    participant_id: z.string().min(1, 'Participant is required'),
    status: z.enum(['pending', 'confirmed', 'rejected', 'cancelled']).default('pending'),
    registered_at: z.string().optional().default(''),
    notes: z.string().optional().default(''),
});

type RegistrationForm = z.infer<typeof registrationSchema>;

interface RegistrationRow extends Registration {
    slug?: string;
}

interface RegistrationsIndexProps {
    registrations: Paginated<RegistrationRow> | RegistrationRow[];
    tournaments?: Tournament[];
    participants?: Participant[];
}

const statusColors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-700',
    confirmed: 'bg-emerald-100 text-emerald-700',
    rejected: 'bg-red-100 text-red-700',
    cancelled: 'bg-gray-100 text-gray-600',
};

export default function RegistrationsIndex({ registrations: registrationsProp, tournaments: tournamentsProp = [], participants: participantsProp = [] }: RegistrationsIndexProps) {
    const { flash } = usePage().props;
    const { t } = useI18n();
    const [open, setOpen] = useState(false);
    const [editingRegistration, setEditingRegistration] = useState<RegistrationRow | null>(null);
    const [deleteRegistration, setDeleteRegistration] = useState<RegistrationRow | null>(null);

    const registrations = Array.isArray(registrationsProp) ? registrationsProp : (registrationsProp?.data ?? []);
    const tournaments = Array.isArray(tournamentsProp) ? tournamentsProp : (tournamentsProp ?? []);
    const participants = Array.isArray(participantsProp) ? participantsProp : (participantsProp ?? []);

    const { register, handleSubmit, reset, formState: { errors, isSubmitting } } = useForm<RegistrationForm>({
        resolver: zodResolver(registrationSchema),
        defaultValues: {
            tournament_id: '',
            participant_id: '',
            status: 'pending',
            registered_at: '',
            notes: '',
        },
    });

    const openCreate = () => {
        setEditingRegistration(null);
        reset({
            tournament_id: tournaments.length > 0 ? tournaments[0].id : '',
            participant_id: participants.length > 0 ? participants[0].id : '',
            status: 'pending',
            registered_at: new Date().toISOString().split('T')[0],
            notes: '',
        });
        setOpen(true);
    };

    const openEdit = (registration: RegistrationRow) => {
        setEditingRegistration(registration);

        const formatForDateInput = (dateStr: string | null) => {
            if (!dateStr) return '';
            return dateStr.split('T')[0];
        };

        reset({
            tournament_id: registration.tournament_id,
            participant_id: registration.participant_id,
            status: registration.status as RegistrationForm['status'],
            registered_at: formatForDateInput(registration.registered_at),
            notes: registration.notes || '',
        });
        setOpen(true);
    };

    const closeDialog = () => {
        setOpen(false);
        setEditingRegistration(null);
        reset();
    };

    const onSubmit = (formData: RegistrationForm) => {
        if (editingRegistration) {
            router.put(route('registrations.update', editingRegistration.id), formData, {
                onSuccess: () => closeDialog(),
            });
        } else {
            router.post(route('registrations.store'), formData, {
                onSuccess: () => closeDialog(),
            });
        }
    };

    const handleDelete = () => {
        if (!deleteRegistration) return;
        router.delete(route('registrations.destroy', deleteRegistration.id), {
            preserveScroll: true,
            onSuccess: () => setDeleteRegistration(null),
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">{t('Registrations')}</h1>
                        <p className="text-sm text-muted-foreground">
                            {t('Manage participant registrations for tournaments')}
                        </p>
                    </div>

                    <Dialog open={open} onOpenChange={(isOpen) => {
                        if (!isOpen) closeDialog();
                        else setOpen(true);
                    }}>
                        <DialogTrigger asChild>
                            <Button onClick={openCreate} disabled={tournaments.length === 0 || participants.length === 0}>
                                <Plus className="mr-2 size-4" />
                                {t('Add Registration')}
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="max-w-lg">
                            <form onSubmit={handleSubmit(onSubmit)}>
                                <DialogHeader>
                                    <DialogTitle>{editingRegistration ? t('Edit Registration') : t('Register Participant')}</DialogTitle>
                                    <DialogDescription>
                                        {t('Register a participant for a specific tournament.')}
                                    </DialogDescription>
                                </DialogHeader>

                                <div className="grid gap-4 py-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="tournament_id">{t('Tournament *')}</Label>
                                        <select
                                            id="tournament_id"
                                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                                            {...register('tournament_id')}
                                            disabled={!!editingRegistration}
                                            required
                                        >
                                            <option value="">{t('-- Select Tournament --')}</option>
                                            {tournaments.map((t) => (
                                                <option key={t.id} value={t.id}>{t.name}</option>
                                            ))}
                                        </select>
                                        {errors.tournament_id && <p className="text-sm text-destructive">{errors.tournament_id.message}</p>}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="participant_id">{t('Participant *')}</Label>
                                        <select
                                            id="participant_id"
                                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                                            {...register('participant_id')}
                                            disabled={!!editingRegistration}
                                            required
                                        >
                                            <option value="">{t('-- Select Participant --')}</option>
                                            {participants.map((p) => (
                                                <option key={p.id} value={p.id}>{p.name}</option>
                                            ))}
                                        </select>
                                        {errors.participant_id && <p className="text-sm text-destructive">{errors.participant_id.message}</p>}
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="status">{t('Status')}</Label>
                                            <select
                                                id="status"
                                                className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                                                {...register('status')}
                                            >
                                                <option value="pending">{t('Pending')}</option>
                                                <option value="confirmed">{t('Confirmed')}</option>
                                                <option value="rejected">{t('Rejected')}</option>
                                                <option value="cancelled">{t('Cancelled')}</option>
                                            </select>
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="registered_at">{t('Registration Date')}</Label>
                                            <Input
                                                id="registered_at"
                                                type="date"
                                                {...register('registered_at')}
                                            />
                                        </div>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="notes">{t('Notes')}</Label>
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
                                        {editingRegistration ? t('Update') : t('Save')}
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>
            }
        >
            <Head title={t('Registrations')} />

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
                    <CardTitle>{t('Registrations List')}</CardTitle>
                    <CardDescription>
                        {t('All participant registrations across tournaments...')}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>{t('Participant')}</TableHead>
                                <TableHead>{t('Tournament')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead>{t('Registered')}</TableHead>
                                <TableHead>{t('Notes')}</TableHead>
                                <TableHead className="text-right">{t('Actions')}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {registrations.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={6} className="text-center text-muted-foreground">
                                        {t('No registrations yet.')}
                                    </TableCell>
                                </TableRow>
                            )}
                            {registrations.map((registration) => (
                                <TableRow key={registration.id}>
                                    <TableCell className="font-medium">
                                        {registration.participant?.name || '-'}
                                    </TableCell>
                                    <TableCell>{registration.tournament?.name || '-'}</TableCell>
                                    <TableCell>
                                        <span className={`rounded-full px-2 py-0.5 text-xs capitalize ${statusColors[registration.status] || 'bg-gray-100 text-gray-600'}`}>
                                            {registration.status}
                                        </span>
                                    </TableCell>
                                    <TableCell className="text-sm text-muted-foreground">
                                        {registration.registered_at ? new Date(registration.registered_at).toLocaleDateString() : '-'}
                                    </TableCell>
                                    <TableCell className="text-sm text-muted-foreground max-w-[200px] truncate">
                                        {registration.notes || '-'}
                                    </TableCell>
                                    <TableCell className="text-right space-x-2">
                                        <Button variant="outline" size="sm" onClick={() => openEdit(registration)}>
                                            <Pencil className="mr-1 size-3" /> {t('Edit')}
                                        </Button>
                                        <Button variant="destructive" size="sm" onClick={() => setDeleteRegistration(registration)}>
                                            <Trash2 className="mr-1 size-3" /> {t('Delete')}
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

                    {!Array.isArray(registrationsProp) && registrationsProp?.links && (
                        <div className="mt-4">
                            <Pagination links={registrationsProp.links} />
                        </div>
                    )}
                </CardContent>
            </Card>

            <Dialog open={!!deleteRegistration} onOpenChange={(isOpen) => !isOpen && setDeleteRegistration(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('Delete Registration?')}</DialogTitle>
                        <DialogDescription>
                            {t('Are you sure you want to delete this registration?')}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteRegistration(null)}>
                            {t('Cancel')}
                        </Button>
                        <Button variant="destructive" onClick={handleDelete} disabled={isSubmitting}>
                            {t('Yes, Delete')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <div className="mt-6 text-xs text-muted-foreground">
                M3: Registration module complete. Track participant sign-ups for tournaments.
            </div>
        </AuthenticatedLayout>
    );
}
