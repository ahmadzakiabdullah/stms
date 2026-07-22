import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Head, Link, router, usePage } from '@inertiajs/react';
import Pagination from '@/components/Pagination';
import { type Paginated } from '@/types';

interface NotificationItem {
    id: string;
    data: { message?: string; type?: string; event_name?: string; faculty_name?: string };
    read_at: string | null;
    created_at: string;
}

interface Props {
    notifications: Paginated<NotificationItem>;
}

export default function NotificationsIndex({ notifications }: Props) {
    const { flash } = usePage().props;

    const markAsRead = (id: string) => {
        router.post(route('notifications.mark-read', id), {}, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold tracking-tight">Notifications</h1>
                    <Button variant="outline" size="sm" onClick={() => router.post(route('notifications.mark-all-read'), {}, { preserveScroll: true })}>
                        Mark All as Read
                    </Button>
                </div>
            }
        >
            <Head title="Notifications" />

            {flash?.success && <div className="mb-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-700">{flash.success}</div>}

            <Card>
                <CardContent className="p-0">
                    {notifications.data.length === 0 ? (
                        <p className="p-6 text-center text-sm text-muted-foreground">No notifications.</p>
                    ) : (
                        <div className="divide-y">
                            {notifications.data.map((n) => (
                                <div
                                    key={n.id}
                                    className={`flex cursor-pointer items-start gap-3 px-4 py-3 transition hover:bg-muted/50 ${!n.read_at ? 'bg-muted/30' : ''}`}
                                    onClick={() => markAsRead(n.id)}
                                >
                                    <div className="min-w-0 flex-1">
                                        <p className={`text-sm ${!n.read_at ? 'font-medium' : ''}`}>{n.data?.message || 'Notification'}</p>
                                        <p className="mt-0.5 text-xs text-muted-foreground">{n.created_at}</p>
                                    </div>
                                    {!n.read_at && <span className="mt-1 size-2 shrink-0 rounded-full bg-primary" />}
                                </div>
                            ))}
                        </div>
                    )}
                </CardContent>
                <Pagination paginator={notifications} />
            </Card>
        </AuthenticatedLayout>
    );
}
