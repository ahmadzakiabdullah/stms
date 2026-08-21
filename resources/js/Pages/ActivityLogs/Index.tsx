import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Head, router } from '@inertiajs/react';
import Pagination from '@/components/Pagination';
import type { Paginated } from '@/types';
import { formatDateTime, useI18n } from '@/lib/i18n';

interface ActivityLogItem {
    id: number;
    log_name: string;
    description: string;
    event: string | null;
    subject_type: string;
    subject_id: string | number;
    attribute_changes: Record<string, unknown> | null;
    causer: { id: string; name: string; email: string } | null;
    properties: Record<string, unknown>;
    created_at: string;
}

interface Props {
    activities: Paginated<ActivityLogItem>;
    filters: { organization_id: string; event: string; from: string; to: string };
    isSuperAdmin: boolean;
    organizations: Array<{ id: string; name: string }>;
}

function subjectLabel(type: string): string {
    const parts = type.split('\\');
    return parts[parts.length - 1] || type;
}

function changeKeys(changes: Record<string, unknown> | null): string {
    return changes ? Object.keys(changes).join(', ') : '';
}

export default function ActivityLogsIndex({ activities, filters, isSuperAdmin, organizations }: Props) {
    const { locale, t } = useI18n();
    const visit = (changes: Partial<Props['filters']>) => router.get(
        route('activity-logs.index'),
        { ...filters, ...changes },
        { preserveState: true, preserveScroll: true, replace: true },
    );

    return (
        <AuthenticatedLayout
            header={
                <h1 className="text-2xl font-semibold tracking-tight">{t('Activity Logs')}</h1>
            }
        >
            <Head title={t('Activity Logs')} />

            <Card>
                <CardHeader>
                    <CardTitle>{t('System Activity Log')}</CardTitle>
                </CardHeader>
                <CardContent className="border-b pt-0">
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        {isSuperAdmin && (
                            <label className="grid gap-1 text-xs font-medium text-muted-foreground">
                                {t('Organization')}
                                <select aria-label="Filter activity by organization" className="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground" value={filters.organization_id} onChange={(event) => visit({ organization_id: event.target.value })}>
                                    <option value="">{t('All organizations')}</option>
                                    {organizations.map((organization) => <option key={organization.id} value={organization.id}>{organization.name}</option>)}
                                </select>
                            </label>
                        )}
                        <label className="grid gap-1 text-xs font-medium text-muted-foreground">
                            {t('Event')}
                            <select aria-label="Filter activity by event" className="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground" value={filters.event} onChange={(event) => visit({ event: event.target.value })}>
                                <option value="">{t('All events')}</option>
                                <option value="created">{t('Created')}</option>
                                <option value="updated">{t('Updated')}</option>
                                <option value="deleted">{t('Deleted')}</option>
                            </select>
                        </label>
                        <label className="grid gap-1 text-xs font-medium text-muted-foreground">
                            {t('From')}
                            <input aria-label="Activity start date" type="date" className="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground" value={filters.from} onChange={(event) => visit({ from: event.target.value })} />
                        </label>
                        <label className="grid gap-1 text-xs font-medium text-muted-foreground">
                            {t('To')}
                            <input aria-label="Activity end date" type="date" className="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground" value={filters.to} onChange={(event) => visit({ to: event.target.value })} />
                        </label>
                    </div>
                </CardContent>
                <CardContent className="p-0">
                    {activities.data.length === 0 ? (
                        <p className="p-6 text-center text-sm text-muted-foreground">{t('No activity logs yet.')}</p>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{t('Description')}</TableHead>
                                    <TableHead>{t('Event')}</TableHead>
                                    <TableHead>{t('Model')}</TableHead>
                                    <TableHead>{t('Changed fields')}</TableHead>
                                    <TableHead>{t('User')}</TableHead>
                                    <TableHead>{t('Date')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {activities.data.map((log) => (
                                    <TableRow key={log.id}>
                                        <TableCell className="max-w-xs truncate font-medium">
                                            {log.description}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="secondary">{log.event || 'custom'}</Badge>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="outline">{subjectLabel(log.subject_type)}</Badge>
                                        </TableCell>
                                        <TableCell className="max-w-xs truncate text-sm text-muted-foreground">
                                            {changeKeys(log.attribute_changes) || '—'}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {log.causer ? log.causer.name : '—'}
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground whitespace-nowrap">
                                            {formatDateTime(log.created_at, locale)}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </CardContent>
                <Pagination paginator={activities} />
            </Card>
        </AuthenticatedLayout>
    );
}
