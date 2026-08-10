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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import Pagination from '@/components/Pagination';
import { Head, router, usePage } from '@inertiajs/react';
import { Check, ShieldCheck, X } from 'lucide-react';
import { useState } from 'react';
import type { Paginated, Flash, EventParticipant, Event as EventType } from '@/types';
import { useI18n } from '@/lib/i18n';

interface RegEventParticipant extends Omit<EventParticipant, 'event' | 'participant'> {
    event?: EventType & {
        sport?: { id: string; name: string };
        sport_category?: { id: string; name: string };
        tournament?: { id: string; name: string };
    };
    participant?: { id: string; name: string };
}

interface DeanDashboardProps {
    registrations: Paginated<RegEventParticipant> | RegEventParticipant[];
    counts: Record<string, number>;
}

const statusBadge: Record<string, { class: string; label: string }> = {
    pending: { class: 'bg-yellow-100 text-yellow-700', label: 'Pending' },
    confirmed: { class: 'bg-emerald-100 text-emerald-700', label: 'Approved' },
    rejected: { class: 'bg-red-100 text-red-700', label: 'Rejected' },
    withdrawn: { class: 'bg-gray-100 text-gray-600', label: 'Withdrawn' },
    disqualified: { class: 'bg-gray-100 text-gray-600', label: 'Disqualified' },
};

export default function DeanDashboard({ registrations: regsProp, counts = {} }: DeanDashboardProps) {
    const { flash } = usePage<{ flash: Flash }>().props;
    const { t } = useI18n();
    const [processing, setProcessing] = useState<string | null>(null);

    const registrations = Array.isArray(regsProp) ? regsProp : (regsProp?.data ?? []);
    const pendingCount = counts.pending ?? 0;
    const approvedCount = counts.confirmed ?? 0;
    const rejectedCount = counts.rejected ?? 0;

    const handleAction = (id: string, action: 'approve' | 'reject') => {
        if (processing) return;
        setProcessing(id);
        const routeName = action === 'approve' ? 'dean.approve' : 'dean.reject';
        router.post(route(routeName, id), {}, {
            preserveScroll: true,
            onFinish: () => setProcessing(null),
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">{t('Dean Dashboard')}</h1>
                        <p className="text-sm text-muted-foreground">
                            {t('Verify and manage your faculty\'s event registrations')}
                        </p>
                    </div>
                </div>
            }
        >
            <Head title={t('Dean Dashboard')} />

            {flash?.success && (
                <div className="mb-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-700">
                    {flash.success}
                </div>
            )}

            <div className="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <div>
                            <CardDescription>{t('Pending')}</CardDescription>
                            <CardTitle className="mt-1 text-3xl text-yellow-600">{pendingCount}</CardTitle>
                        </div>
                        <div className="flex size-11 items-center justify-center rounded-lg bg-yellow-100">
                            <ShieldCheck className="size-5 text-yellow-600" />
                        </div>
                    </CardHeader>
                    <CardContent className="text-sm text-muted-foreground">
                        {t('Awaiting verification')}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <div>
                            <CardDescription>{t('Approved')}</CardDescription>
                            <CardTitle className="mt-1 text-3xl text-emerald-600">{approvedCount}</CardTitle>
                        </div>
                        <div className="flex size-11 items-center justify-center rounded-lg bg-emerald-100">
                            <Check className="size-5 text-emerald-600" />
                        </div>
                    </CardHeader>
                    <CardContent className="text-sm text-muted-foreground">
                        {t('Verified registrations')}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <div>
                            <CardDescription>{t('Rejected')}</CardDescription>
                            <CardTitle className="mt-1 text-3xl text-red-600">{rejectedCount}</CardTitle>
                        </div>
                        <div className="flex size-11 items-center justify-center rounded-lg bg-red-100">
                            <X className="size-5 text-red-600" />
                        </div>
                    </CardHeader>
                    <CardContent className="text-sm text-muted-foreground">
                        {t('Rejected registrations')}
                    </CardContent>
                </Card>
            </div>

            <div className="mt-6 space-y-4">
                {/* Pending Section */}
                {pendingCount > 0 && (
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center gap-2 text-base">
                                <span className="inline-block size-2 rounded-full bg-yellow-400" />
                                {t('Pending Verification')}
                                <span className="text-sm font-normal text-muted-foreground">({pendingCount})</span>
                            </CardTitle>
                            <CardDescription>{t('Faculty representatives waiting for your approval')}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>{t('Event')}</TableHead>
                                        <TableHead>{t('Sport')}</TableHead>
                                        <TableHead>{t('Category')}</TableHead>
                                        <TableHead>{t('Tournament')}</TableHead>
                                        <TableHead>{t('Date')}</TableHead>
                                        <TableHead className="text-right">{t('Actions')}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {registrations.filter(r => r.status === 'pending').map((reg) => (
                                        <TableRow key={reg.id}>
                                            <TableCell className="font-medium">{reg.event?.name || '—'}</TableCell>
                                            <TableCell>{reg.event?.sport?.name || '—'}</TableCell>
                                            <TableCell>{reg.event?.sport_category?.name || '—'}</TableCell>
                                            <TableCell>{reg.event?.tournament?.name || '—'}</TableCell>
                                            <TableCell className="text-sm text-muted-foreground">
                                                {reg.created_at
                                                    ? new Date(reg.created_at).toLocaleDateString('ms-MY', { day: 'numeric', month: 'short' })
                                                    : '—'}
                                            </TableCell>
                                            <TableCell className="text-right space-x-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className="border-emerald-300 text-emerald-700 hover:bg-emerald-50"
                                                    disabled={processing === reg.id}
                                                    onClick={() => handleAction(reg.id, 'approve')}
                                                >
                                                     <Check className="mr-1 size-3" /> {t('Approve')}
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className="border-red-300 text-red-700 hover:bg-red-50"
                                                    disabled={processing === reg.id}
                                                    onClick={() => handleAction(reg.id, 'reject')}
                                                >
                                                    <X className="mr-1 size-3" /> {t('Reject')}
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}

                {/* History Section (confirmed + rejected) */}
                {(approvedCount > 0 || rejectedCount > 0) && (
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base">{t('Verification History')}</CardTitle>
                            <CardDescription>
                                {approvedCount > 0 && <span className="text-emerald-600">{approvedCount} {t('approved')}</span>}
                                {approvedCount > 0 && rejectedCount > 0 && <span> · </span>}
                                {rejectedCount > 0 && <span className="text-red-600">{rejectedCount} {t('rejected')}</span>}
                                {approvedCount === 0 && rejectedCount === 0 && t('No history')}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>{t('Event')}</TableHead>
                                        <TableHead>{t('Sport')}</TableHead>
                                        <TableHead>{t('Category')}</TableHead>
                                        <TableHead>{t('Tournament')}</TableHead>
                                        <TableHead>{t('Date')}</TableHead>
                                        <TableHead>{t('Status')}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {registrations.filter(r => r.status !== 'pending').length === 0 ? (
                                        <TableRow>
                                             <TableCell colSpan={6} className="text-center text-muted-foreground">
                                                {t('No history yet.')}
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        registrations.filter(r => r.status !== 'pending').map((reg) => (
                                            <TableRow key={reg.id}>
                                                <TableCell className="font-medium">{reg.event?.name || '—'}</TableCell>
                                                <TableCell>{reg.event?.sport?.name || '—'}</TableCell>
                                                <TableCell>{reg.event?.sport_category?.name || '—'}</TableCell>
                                                <TableCell>{reg.event?.tournament?.name || '—'}</TableCell>
                                                <TableCell className="text-sm text-muted-foreground">
                                                    {reg.created_at
                                                        ? new Date(reg.created_at).toLocaleDateString('ms-MY', { day: 'numeric', month: 'short' })
                                                        : '—'}
                                                </TableCell>
                                                 <TableCell>
                                                    <span className={`rounded-full px-2 py-0.5 text-xs capitalize ${statusBadge[reg.status]?.class || 'bg-gray-100 text-gray-600'}`}>
                                                        {t(statusBadge[reg.status]?.label) || reg.status}
                                                    </span>
                                                 </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}

                {registrations.length === 0 && (
                    <Card>
                        <CardContent className="py-12 text-center text-muted-foreground">
                            <ShieldCheck className="mx-auto mb-3 size-8 opacity-50" />
                            {t('No registrations yet for your faculty.')}
                        </CardContent>
                    </Card>
                )}

                <Pagination paginator={regsProp} />
            </div>
        </AuthenticatedLayout>
    );
}
