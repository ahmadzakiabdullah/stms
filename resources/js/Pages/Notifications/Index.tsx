import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { EmptyState } from '@/components/EmptyState';
import { PageHeader } from '@/components/PageHeader';
import Pagination from '@/components/Pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Head, Link, router } from '@inertiajs/react';
import { Activity, Bell, CheckCheck, CircleAlert, Inbox } from 'lucide-react';
import { type Paginated } from '@/types';
import { formatDateTime, useI18n } from '@/lib/i18n';

interface NotificationItem {
    id: string;
    data: {
        message?: string;
        type?: string;
        severity?: 'info' | 'success' | 'warning' | 'critical';
        event_name?: string;
        faculty_name?: string;
        faculty_name_ms?: string | null;
        organization_id?: string;
        organization_name?: string;
        home_name?: string | null;
        home_name_ms?: string | null;
        away_name?: string | null;
        away_name_ms?: string | null;
        winner_name?: string | null;
        winner_name_ms?: string | null;
        score_home?: number | null;
        score_away?: number | null;
        match_number?: number | null;
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
    const { locale, t } = useI18n();

    const localized = (english?: string | null, malay?: string | null) => locale === 'ms' ? (malay || english || '-') : (english || '-');
    const notificationTypeLabel = (type: string) => t({
        confirmed: 'Registration approved',
        rejected: 'Registration rejected',
        result_recorded: 'Result recorded',
        result_updated: 'Result updated',
        result_removed: 'Result removed',
    }[type] ?? type);
    const notificationMessage = (data: NotificationItem['data']) => {
        const event = data.event_name || t('Unknown event');
        const faculty = localized(data.faculty_name, data.faculty_name_ms);

        if (data.type === 'new_registration') return `${faculty} ${t('registered for')} '${event}'.`;
        if (data.type === 'confirmed') return `${t('Registration for')} '${event}' ${t('has been approved')}.`;
        if (data.type === 'rejected') return `${t('Registration for')} '${event}' ${t('has been rejected')}.`;

        if (data.type?.startsWith('result_')) {
            const action = data.type.replace('result_', '');
            const actionLabel = t(action === 'recorded' ? 'Result recorded' : action === 'updated' ? 'Result updated' : action === 'removed' ? 'Result removed' : 'Match result changed');
            const matchLabel = `${event}${data.match_number ? ` (${t('Match')} #${data.match_number})` : ''}`;
            const teams = `${localized(data.home_name, data.home_name_ms)} ${t('vs')} ${localized(data.away_name, data.away_name_ms)}`;
            const score = data.score_home !== null && data.score_home !== undefined && data.score_away !== null && data.score_away !== undefined ? ` (${data.score_home} - ${data.score_away})` : '';
            const winner = data.winner_name ? `. ${t('Winner')}: ${localized(data.winner_name, data.winner_name_ms)}` : '';
            const removedNote = action === 'removed' ? ` ${t('The result was deleted and rankings may change.')}` : '';
            return `${actionLabel}: ${matchLabel} — ${teams}${score}${winner}${removedNote}`;
        }

        return data.message || t('Notification');
    };

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
                <PageHeader
                    title={t('Notifications')}
                    description={t('Actionable updates for your account')}
                    actions={
                        <>
                            {isSuperAdmin && (
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={route('activity-logs.index')}>
                                        <Activity className="mr-2 size-4" /> {t('System Activity')}
                                    </Link>
                                </Button>
                            )}
                            <Button variant="outline" size="sm" onClick={() => router.post(route('notifications.mark-all-read'), {}, { preserveScroll: true })}>
                                <CheckCheck className="mr-2 size-4" /> {t('Mark All as Read')}
                            </Button>
                        </>
                    }
                />
            }
        >
            <Head title={t('Notifications')} />

            <div className="mb-4 flex flex-wrap gap-2" role="tablist" aria-label={t('Notification views')}>
                {isSuperAdmin && (
                    <Button
                        role="tab"
                        aria-selected={filters.tab === 'action'}
                        variant={filters.tab === 'action' ? 'default' : 'outline'}
                        onClick={() => selectTab('action')}
                    >
                        <CircleAlert className="mr-2 size-4" /> {t('Action Required')}
                        {counts.action_required > 0 && <Badge variant="secondary" className="ml-2">{counts.action_required}</Badge>}
                    </Button>
                )}
                <Button
                    role="tab"
                    aria-selected={filters.tab === 'inbox'}
                    variant={filters.tab === 'inbox' ? 'default' : 'outline'}
                    onClick={() => selectTab('inbox')}
                >
                    <Inbox className="mr-2 size-4" /> {t('My Notifications')}
                    {counts.unread > 0 && <Badge variant="secondary" className="ml-2">{counts.unread}</Badge>}
                </Button>
            </div>

            <Card>
                <CardContent className="border-b p-4">
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <label className="grid gap-1 text-xs font-medium text-muted-foreground">
                            {t('Read status')}
                            <Select
                                value={filters.status}
                                onValueChange={(value) => visit({ status: value as Filters['status'] })}
                            >
                                <SelectTrigger aria-label={t('Filter notifications by read status')} className="h-9 w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">{t('All')}</SelectItem>
                                    <SelectItem value="unread">{t('Unread')}</SelectItem>
                                    <SelectItem value="read">{t('Read')}</SelectItem>
                                </SelectContent>
                            </Select>
                        </label>
                        <label className="grid gap-1 text-xs font-medium text-muted-foreground">
                            {t('Type')}
                            <Select
                                value={filters.type || 'all'}
                                onValueChange={(value) => visit({ type: value === 'all' ? '' : value })}
                            >
                                <SelectTrigger aria-label={t('Filter notifications by type')} className="h-9 w-full">
                                    <SelectValue placeholder={t('All types')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">{t('All types')}</SelectItem>
                                    {notificationTypes.map((type) => <SelectItem key={type.value} value={type.value}>{notificationTypeLabel(type.value)}</SelectItem>)}
                                </SelectContent>
                            </Select>
                        </label>
                        {isSuperAdmin && (
                            <label className="grid gap-1 text-xs font-medium text-muted-foreground">
                                {t('Organization')}
                                <Select
                                    value={filters.organization_id || 'all'}
                                    onValueChange={(value) => visit({ organization_id: value === 'all' ? '' : value })}
                                >
                                    <SelectTrigger aria-label={t('Filter notifications by organization')} className="h-9 w-full">
                                        <SelectValue placeholder={t('All organizations')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">{t('All organizations')}</SelectItem>
                                        {organizations.map((organization) => <SelectItem key={organization.id} value={organization.id}>{organization.name}</SelectItem>)}
                                    </SelectContent>
                                </Select>
                            </label>
                        )}
                    </div>
                </CardContent>

                <CardContent className="p-0">
                    {notifications.data.length === 0 ? (
                        <EmptyState
                            icon={Bell}
                            title={t('Nothing to show')}
                            description={filters.tab === 'action' ? t('There are no pending notifications requiring attention.') : t('No notifications match the selected filters.')}
                        />
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
                                        <span className={`mt-0.5 rounded-full px-2 py-0.5 text-xs font-medium capitalize ${severityStyles[severity]}`}>
                                            {t(severity === 'critical' ? 'Critical' : severity === 'success' ? 'Success' : severity === 'warning' ? 'Warning' : 'Info')}
                                        </span>
                                        <span className="min-w-0 flex-1">
                                            <span className={`block text-sm ${!notification.read_at ? 'font-medium' : ''}`}>
                                                {notificationMessage(notification.data)}
                                            </span>
                                            <span className="mt-1 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                                <span>{formatDateTime(notification.created_at, locale)}</span>
                                                {notification.data?.organization_name && (
                                                    <Badge variant="outline">{notification.data.organization_name}</Badge>
                                                )}
                                            </span>
                                        </span>
                                        {notification.data?.action_url && (
                                            <Button size="sm" variant="outline" asChild onClick={(event: React.MouseEvent) => event.stopPropagation()}>
                                                <Link href={notification.data.action_url}>{t('Review')}</Link>
                                            </Button>
                                        )}
                                        {!notification.read_at && <span className="mt-2 size-2 shrink-0 rounded-full bg-primary" aria-label={t('Unread')} />}
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
