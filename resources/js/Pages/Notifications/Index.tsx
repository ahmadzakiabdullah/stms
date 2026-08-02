import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Pagination from '@/components/Pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Activity, Bell, CheckCheck, CircleAlert, Inbox } from 'lucide-react';
import { type Paginated } from '@/types';

interface NotificationItem {
    id: string;
    data: {
        message?: string;
        type?: string;
        severity?: 'info' | 'success' | 'warning' | 'critical';
        event_name?: string;
        faculty_name?: string;
        organization_id?: string;
        organization_name?: string;
        action_url?: string;
    };
    read_at: string | null;
    created_at: string;
}

interface Filters {
    tab: 'action' | 'inbox';
    status: 'all' | 'unread' | 'read';
    type: string;
    organization_id: string;
}

interface Props {
    notifications: Paginated<NotificationItem>;
    filters: Filters;
    counts: { action_required: number; unread: number };
    isSuperAdmin: boolean;
    organizations: Array<{ id: string; name: string }>;
    notificationTypes: Array<{ value: string; label: string }>;
}

const severityStyles = {
    info: 'bg-blue-100 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300',
    success: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300',
    warning: 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300',
    critical: 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-300',
};

export default function NotificationsIndex({
    notifications,
    filters,
    counts,
    isSuperAdmin,
    organizations,
    notificationTypes,
}: Props) {
    const { flash } = usePage().props;

    const visit = (changes: Partial<Filters>) => {
        router.get(route('notifications.index'), { ...filters, ...changes }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const selectTab = (tab: Filters['tab']) => {
        visit({ tab, status: tab === 'action' ? 'unread' : 'all', type: '' });
    };

    const markAsRead = (id: string) => {
        router.post(route('notifications.mark-read', id), {}, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Notifications</h1>
                        <p className="text-sm text-muted-foreground">Actionable updates for your account</p>
                    </div>
                    <div className="flex items-center gap-2">
                        {isSuperAdmin && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={route('activity-logs.index')}>
                                    <Activity className="mr-2 size-4" /> System Activity
                                </Link>
                            </Button>
                        )}
                        <Button variant="outline" size="sm" onClick={() => router.post(route('notifications.mark-all-read'), {}, { preserveScroll: true })}>
                            <CheckCheck className="mr-2 size-4" /> Mark All as Read
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title="Notifications" />

            {flash?.success && <div className="mb-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-700">{flash.success}</div>}

            <div className="mb-4 flex flex-wrap gap-2" role="tablist" aria-label="Notification views">
                {isSuperAdmin && (
                    <Button
                        role="tab"
                        aria-selected={filters.tab === 'action'}
                        variant={filters.tab === 'action' ? 'default' : 'outline'}
                        onClick={() => selectTab('action')}
                    >
                        <CircleAlert className="mr-2 size-4" /> Action Required
                        {counts.action_required > 0 && <Badge variant="secondary" className="ml-2">{counts.action_required}</Badge>}
                    </Button>
                )}
                <Button
                    role="tab"
                    aria-selected={filters.tab === 'inbox'}
                    variant={filters.tab === 'inbox' ? 'default' : 'outline'}
                    onClick={() => selectTab('inbox')}
                >
                    <Inbox className="mr-2 size-4" /> My Notifications
                    {counts.unread > 0 && <Badge variant="secondary" className="ml-2">{counts.unread}</Badge>}
                </Button>
            </div>

            <Card>
                <CardContent className="border-b p-4">
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <label className="grid gap-1 text-xs font-medium text-muted-foreground">
                            Read status
                            <select
                                aria-label="Filter notifications by read status"
                                className="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                                value={filters.status}
                                onChange={(event) => visit({ status: event.target.value as Filters['status'] })}
                            >
                                <option value="all">All</option>
                                <option value="unread">Unread</option>
                                <option value="read">Read</option>
                            </select>
                        </label>
                        <label className="grid gap-1 text-xs font-medium text-muted-foreground">
                            Type
                            <select
                                aria-label="Filter notifications by type"
                                className="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                                value={filters.type}
                                onChange={(event) => visit({ type: event.target.value })}
                            >
                                <option value="">All types</option>
                                {notificationTypes.map((type) => <option key={type.value} value={type.value}>{type.label}</option>)}
                            </select>
                        </label>
                        {isSuperAdmin && (
                            <label className="grid gap-1 text-xs font-medium text-muted-foreground">
                                Organization
                                <select
                                    aria-label="Filter notifications by organization"
                                    className="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                                    value={filters.organization_id}
                                    onChange={(event) => visit({ organization_id: event.target.value })}
                                >
                                    <option value="">All organizations</option>
                                    {organizations.map((organization) => <option key={organization.id} value={organization.id}>{organization.name}</option>)}
                                </select>
                            </label>
                        )}
                    </div>
                </CardContent>

                <CardContent className="p-0">
                    {notifications.data.length === 0 ? (
                        <div className="p-10 text-center">
                            <Bell className="mx-auto mb-3 size-8 text-muted-foreground" />
                            <p className="font-medium">Nothing to show</p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {filters.tab === 'action' ? 'There are no pending notifications requiring attention.' : 'No notifications match the selected filters.'}
                            </p>
                        </div>
                    ) : (
                        <div className="divide-y">
                            {notifications.data.map((notification) => {
                                const severity = notification.data?.severity ?? 'info';
                                return (
                                    <button
                                        type="button"
                                        key={notification.id}
                                        className={`flex w-full items-start gap-3 px-4 py-4 text-left transition hover:bg-muted/50 ${!notification.read_at ? 'bg-muted/30' : ''}`}
                                        onClick={() => !notification.read_at && markAsRead(notification.id)}
                                    >
                                        <span className={`mt-0.5 rounded-full px-2 py-0.5 text-[11px] font-medium capitalize ${severityStyles[severity]}`}>
                                            {severity}
                                        </span>
                                        <span className="min-w-0 flex-1">
                                            <span className={`block text-sm ${!notification.read_at ? 'font-medium' : ''}`}>
                                                {notification.data?.message || 'Notification'}
                                            </span>
                                            <span className="mt-1 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                                <span>{new Date(notification.created_at).toLocaleString()}</span>
                                                {notification.data?.organization_name && (
                                                    <Badge variant="outline">{notification.data.organization_name}</Badge>
                                                )}
                                            </span>
                                        </span>
                                        {notification.data?.action_url && (
                                            <Button size="sm" variant="outline" asChild onClick={(event: React.MouseEvent) => event.stopPropagation()}>
                                                <Link href={notification.data.action_url}>Review</Link>
                                            </Button>
                                        )}
                                        {!notification.read_at && <span className="mt-2 size-2 shrink-0 rounded-full bg-primary" aria-label="Unread" />}
                                    </button>
                                );
                            })}
                        </div>
                    )}
                </CardContent>
                <Pagination paginator={notifications} />
            </Card>
        </AuthenticatedLayout>
    );
}
