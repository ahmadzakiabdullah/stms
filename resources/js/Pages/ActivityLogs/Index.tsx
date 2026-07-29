import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { PageProps } from '@/types';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import Pagination from '@/components/Pagination';
import { Badge } from '@/components/ui/badge';

interface ActivityLog {
    id: number;
    log_name: string;
    description: string;
    subject_type: string | null;
    subject_id: string | number | null;
    causer: string;
    properties: Record<string, any>;
    created_at: string;
    created_at_human: string;
}

interface ActivityLogsProps extends PageProps {
    logs: {
        data: ActivityLog[];
        links: any[];
        meta: {
            current_page: number;
            last_page: number;
            total: number;
        };
    };
}

export default function ActivityLogs({ auth, logs }: ActivityLogsProps) {
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Log Aktiviti (Activity Logs)</h2>}
        >
            <Head title="Activity Logs" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <Card>
                        <CardHeader>
                            <CardTitle>Rekod Aktiviti Sistem</CardTitle>
                            <CardDescription>Jejak perubahan dan aktiviti pengguna dalam sistem.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="rounded-md border mb-4 overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Masa</TableHead>
                                            <TableHead>Pengguna (Causer)</TableHead>
                                            <TableHead>Tindakan</TableHead>
                                            <TableHead>Subjek (Model)</TableHead>
                                            <TableHead>Data Tambahan</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {logs.data.length === 0 ? (
                                            <TableRow>
                                                <TableCell colSpan={5} className="text-center py-6 text-muted-foreground">
                                                    Tiada rekod aktiviti ditemui.
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            logs.data.map((log) => (
                                                <TableRow key={log.id}>
                                                    <TableCell className="whitespace-nowrap">
                                                        <div className="text-sm font-medium">{log.created_at}</div>
                                                        <div className="text-xs text-muted-foreground">{log.created_at_human}</div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge variant="outline">{log.causer}</Badge>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge
                                                            variant={
                                                                log.description === 'created' ? 'default' :
                                                                log.description === 'updated' ? 'secondary' :
                                                                log.description === 'deleted' ? 'destructive' : 'outline'
                                                            }
                                                            className="capitalize"
                                                        >
                                                            {log.description}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell>
                                                        {log.subject_type ? (
                                                            <span>{log.subject_type} <span className="text-xs text-muted-foreground">#{log.subject_id}</span></span>
                                                        ) : '-'}
                                                    </TableCell>
                                                    <TableCell className="max-w-xs truncate">
                                                        <pre className="text-[10px] bg-slate-100 p-1 rounded overflow-hidden text-ellipsis">
                                                            {JSON.stringify(log.properties)}
                                                        </pre>
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        )}
                                    </TableBody>
                                </Table>
                            </div>

                            <Pagination links={logs.links} />
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
