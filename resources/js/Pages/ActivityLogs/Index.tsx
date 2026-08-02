import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Head, router } from '@inertiajs/react';
import Pagination from '@/components/Pagination';
import type { Paginated } from '@/types';

interface ActivityLogItem {
    id: number;
    log_name: string;
    description: string;
    subject_type: string;
    subject_id: string | number;
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

export default function ActivityLogsIndex({ activities, filters, isSuperAdmin, organizations }: Props) {
    const visit = (changes: Partial<Props['filters']>) => router.get(
        route('activity-logs.index'),
        { ...filters, ...changes },
        { preserveState: true, preserveScroll: true, replace: true },
    );

    return (
        <AuthenticatedLayout
            header={
                <h1 className="text-2xl font-semibold tracking-tight">Activity Logs</h1>
            }
        >
            <Head title="Activity Logs" />

            <Card>
                <CardHeader>
                    <CardTitle>System Activity Log</CardTitle>
                </CardHeader>
                <CardContent className="border-b pt-0">
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        {isSuperAdmin && (
                            <label className="grid gap-1 text-xs font-medium text-muted-foreground">
                                Organization
                                <select aria-label="Filter activity by organization" className="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground" value={filters.organization_id} onChange={(event) => visit({ organization_id: event.target.value })}>
                                    <option value="">All organizations</option>
                                    {organizations.map((organization) => <option key={organization.id} value={organization.id}>{organization.name}</option>)}
                                </select>
                            </label>
                        )}
                        <label className="grid gap-1 text-xs font-medium text-muted-foreground">
                            Event
                            <select aria-label="Filter activity by event" className="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground" value={filters.event} onChange={(event) => visit({ event: event.target.value })}>
                                <option value="">All events</option>
                                <option value="created">Created</option>
                                <option value="updated">Updated</option>
                                <option value="deleted">Deleted</option>
                            </select>
                        </label>
                        <label className="grid gap-1 text-xs font-medium text-muted-foreground">
                            From
                            <input aria-label="Activity start date" type="date" className="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground" value={filters.from} onChange={(event) => visit({ from: event.target.value })} />
                        </label>
                        <label className="grid gap-1 text-xs font-medium text-muted-foreground">
                            To
                            <input aria-label="Activity end date" type="date" className="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground" value={filters.to} onChange={(event) => visit({ to: event.target.value })} />
                        </label>
                    </div>
                </CardContent>
                <CardContent className="p-0">
                    {activities.data.length === 0 ? (
                        <p className="p-6 text-center text-sm text-muted-foreground">No activity logs yet.</p>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Description</TableHead>
                                    <TableHead>Model</TableHead>
                                    <TableHead>User</TableHead>
                                    <TableHead>Date</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {activities.data.map((log) => (
                                    <TableRow key={log.id}>
                                        <TableCell className="max-w-xs truncate font-medium">
                                            {log.description}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="outline">{subjectLabel(log.subject_type)}</Badge>
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {log.causer ? log.causer.name : '—'}
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground whitespace-nowrap">
                                            {log.created_at}
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
