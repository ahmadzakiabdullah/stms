import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { EmptyState } from '@/components/EmptyState';
import { PageHeader } from '@/components/PageHeader';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Head, router } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
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
    filters: { organization_id: string; event: string; from: string; to: string; search: string };
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
    const [search, setSearch] = useState(filters.search ?? '');
    const visit = (changes: Partial<Props['filters']>) => router.get(
        route('activity-logs.index'),
        { ...filters, ...changes },
        { preserveState: true, preserveScroll: true, replace: true },
    );
    const applySearch = (event: FormEvent<HTMLFormElement>) => { event.preventDefault(); visit({ search: search.trim() }); };

    return (
        <AuthenticatedLayout
            header={
                <PageHeader title={t('Activity Logs')} />
            }
        >
            <Head title={t('Activity Logs')} />

            <Card>
                <CardHeader>
                    <CardTitle>{t('System Activity Log')}</CardTitle>
                </CardHeader>
                <CardContent className="border-b pt-0">
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <form onSubmit={applySearch} className="flex items-end gap-2 sm:col-span-2 lg:col-span-4">
                            <label className="grid flex-1 gap-1 text-xs font-medium text-muted-foreground">{t('Search')}<Input value={search} onChange={(event) => setSearch(event.target.value)} placeholder={t('Search description, model or user...')} aria-label={t('Search activity logs')} /></label>
                            <Button type="submit" variant="secondary">{t('Search')}</Button>
                        </form>
                        {isSuperAdmin && (
                            <label className="grid gap-1 text-xs font-medium text-muted-foreground">
                                {t('Organization')}
                                <Select value={filters.organization_id || 'all'} onValueChange={(value) => visit({ organization_id: value === 'all' ? '' : value })}>
                                    <SelectTrigger aria-label="Filter activity by organization" className="h-9 w-full">
                                        <SelectValue placeholder={t('All organizations')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">{t('All organizations')}</SelectItem>
                                        {organizations.map((organization) => <SelectItem key={organization.id} value={organization.id}>{organization.name}</SelectItem>)}
                                    </SelectContent>
                                </Select>
                            </label>
                        )}
                        <label className="grid gap-1 text-xs font-medium text-muted-foreground">
                            {t('Event')}
                            <Select value={filters.event || 'all'} onValueChange={(value) => visit({ event: value === 'all' ? '' : value })}>
                                <SelectTrigger aria-label="Filter activity by event" className="h-9 w-full">
                                    <SelectValue placeholder={t('All events')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">{t('All events')}</SelectItem>
                                    <SelectItem value="created">{t('Created')}</SelectItem>
                                    <SelectItem value="updated">{t('Updated')}</SelectItem>
                                    <SelectItem value="deleted">{t('Deleted')}</SelectItem>
                                </SelectContent>
                            </Select>
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
                        <EmptyState description={filters.search ? t('No activity logs match your search.') : t('No activity logs yet.')} />
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
