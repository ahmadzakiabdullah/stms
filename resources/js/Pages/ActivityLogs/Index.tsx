import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Head } from '@inertiajs/react';
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
}

function subjectLabel(type: string): string {
    const parts = type.split('\\');
    return parts[parts.length - 1] || type;
}

export default function ActivityLogsIndex({ activities }: Props) {
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
