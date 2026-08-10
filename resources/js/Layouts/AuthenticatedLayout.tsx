import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Link, router, usePage } from '@inertiajs/react';
import {
    Activity,
    Award,
    BarChart3,
    Bell,
    Building2,
    Calendar,
    ChevronDown,
    ClipboardList,
    FileCheck2,
    KeySquare,
    LayoutDashboard,
    List,
    LogOut,
    Menu,
    Scale,
    Search,
    Settings,
    ShieldCheck,
    Swords,
    Target,
    Trophy,
    UserCircle,
    Users,
} from 'lucide-react';
import { type LucideIcon } from 'lucide-react';
import { ReactNode, useEffect, useState } from 'react';
import { type PageProps, type User } from '@/types';
import LocaleSwitcher from '@/components/LocaleSwitcher';
import { useI18n } from '@/lib/i18n';

interface NavItem {
    label: string;
    icon: LucideIcon;
    href: string;
    active: string;
    roles?: string[];
}

interface NavSection {
    title: string | null;
    items: NavItem[];
}

const systemRoles = {
    administrators: ['super-admin', 'org-admin'],
    competition: ['super-admin', 'org-admin', 'admin-sport'],
    reports: ['super-admin', 'org-admin', 'staff'],
    faculty: ['faculty-representative'],
    dean: ['dean'],
};

const navSections: NavSection[] = [
    {
        title: 'Overview',
        items: [
            { label: 'Dashboard', icon: LayoutDashboard, href: 'dashboard', active: 'dashboard', roles: ['super-admin', 'org-admin', 'admin-sport', 'staff', 'faculty-representative'] },
            { label: 'Dean Dashboard', icon: ShieldCheck, href: 'dean.dashboard', active: 'dean.dashboard', roles: systemRoles.dean },
            { label: 'Notifications', icon: Bell, href: 'notifications.index', active: 'notifications.index' },
        ],
    },
    {
        title: 'Competition Setup',
        items: [
            { label: 'Sessions', icon: Calendar, href: 'sessions.index', active: 'sessions.index', roles: systemRoles.administrators },
            { label: 'Sports', icon: Award, href: 'sports.index', active: 'sports.index', roles: systemRoles.administrators },
            { label: 'Categories', icon: List, href: 'sport-categories.index', active: 'sport-categories.index', roles: systemRoles.administrators },
            { label: 'Tournaments', icon: Trophy, href: 'tournaments.index', active: 'tournaments.index', roles: systemRoles.administrators },
            { label: 'Events', icon: Target, href: 'events.index', active: 'events.index', roles: systemRoles.competition },
        ],
    },
    {
        title: 'Registration',
        items: [
            { label: 'Participants', icon: Users, href: 'participants.index', active: 'participants.index', roles: systemRoles.administrators },
            { label: 'Registrations & Squads', icon: ClipboardList, href: 'event-participants.index', active: 'event-participants.index', roles: systemRoles.administrators },
            { label: 'Participation Confirmation', icon: FileCheck2, href: 'participation-confirmations.index', active: 'participation-confirmations.index', roles: [...systemRoles.administrators, ...systemRoles.faculty, ...systemRoles.dean] },
        ],
    },
    {
        title: 'Competition Operations',
        items: [
            { label: 'Matches', icon: Swords, href: 'matches.index', active: 'matches.index', roles: systemRoles.competition },
            { label: 'Results', icon: Trophy, href: 'results.index', active: 'results.index', roles: systemRoles.competition },
            { label: 'Rankings', icon: Award, href: 'rankings.index', active: 'rankings.index', roles: systemRoles.competition },
        ],
    },
    {
        title: 'Reports',
        items: [
            { label: 'Analytics', icon: BarChart3, href: 'reports.index', active: 'reports.index', roles: systemRoles.reports },
        ],
    },
    {
        title: 'Administration',
        items: [
            { label: 'Organizations', icon: Building2, href: 'organizations.index', active: 'organizations.index', roles: ['super-admin'] },
            { label: 'Users', icon: UserCircle, href: 'users.index', active: 'users.index', roles: systemRoles.administrators },
            { label: 'Roles & Permissions', icon: KeySquare, href: 'roles.index', active: 'roles.index', roles: ['super-admin'] },
            { label: 'Settings', icon: Settings, href: 'settings.index', active: 'settings.index', roles: systemRoles.administrators },
            { label: 'Activity Logs', icon: Activity, href: 'activity-logs.index', active: 'activity-logs.index', roles: systemRoles.administrators },
        ],
    },
];
function initials(name = 'User'): string {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
}

interface SidebarProps {
    user: User;
    mobile?: boolean;
    onNavigate?: () => void;
    isSuperAdmin?: boolean;
    isFacultyRep?: boolean;
    isDean?: boolean;
    app?: { name: string } | null;
}

function Sidebar({ user, mobile = false, onNavigate = () => {}, isSuperAdmin = false, isFacultyRep = false, isDean = false, app = null }: SidebarProps) {
    const { settings = {} as Record<string, string> } = usePage<PageProps>().props;
    const { t } = useI18n();
    const logoUrl = (settings as Record<string, string>)?.logo_url;

    return (
        <aside className={mobile ? 'flex h-full flex-col bg-sidebar' : 'hidden h-screen w-72 shrink-0 border-r bg-sidebar lg:sticky lg:top-0 lg:flex lg:flex-col'}>            <Link
                href={route('dashboard')}
                onClick={onNavigate}
                className="flex h-16 items-center gap-3 border-b px-5 transition hover:bg-sidebar-accent/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-ring"
                aria-label={t('Go to dashboard')}
            >
                {logoUrl ? (
                    <img src={logoUrl} alt="Logo" className="h-9 w-auto rounded object-contain" />
                ) : (
                    <div className="flex size-9 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                        <ShieldCheck className="size-5" />
                    </div>
                )}
                <div>
                    <div className="text-sm font-semibold leading-none">{app?.name || 'STMS Portal'}</div>
                    <div className="mt-1 text-xs text-muted-foreground">
                        {user?.organization?.name || t('Multi-Tenant Sports Platform')}
                    </div>
                </div>
            </Link>

            <nav className="flex-1 space-y-2 px-3 py-4 overflow-y-auto">
                {navSections.map((section, sectionIdx) => {
                    const userRoles = new Set(user.roles?.map((role) => role.name) ?? []);
                    const visibleItems = section.items.filter(
                        (item) => !item.roles || item.roles.some((role) => userRoles.has(role))
                    );

                    if (visibleItems.length === 0) return null;

                    return (
                        <div key={sectionIdx}>
                            {section.title && (
                                <div className="px-3 pt-2 pb-1 text-[10px] font-semibold uppercase tracking-[0.5px] text-muted-foreground/70">
                                    {t(section.title)}
                                </div>
                            )}
                            <div className="space-y-1">
                                {visibleItems.map((item) => {
                                    const Icon = item.icon;
                                    const isActive = item.active && route().current(item.active);

                                    return (
                                        <Link
                                            key={item.label}
                                            href={route(item.href)}
                                            onClick={onNavigate}
                                            className={
                                                'group relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition ' +
                                                (isActive
                                                    ? 'bg-sidebar-accent text-sidebar-accent-foreground'
                                                    : 'text-muted-foreground hover:bg-sidebar-accent/60 hover:text-sidebar-accent-foreground')
                                            }
                                        >
                                            {isActive && (
                                                <span className="absolute left-0 top-1/2 h-5 w-1 -translate-y-1/2 rounded-r-full bg-primary" />
                                            )}
                                            <Icon className={'size-4 ' + (isActive ? 'text-primary' : 'group-hover:text-sidebar-accent-foreground')} />
                                            <span>{t(item.label)}</span>
                                        </Link>
                                    );
                                })}
                            </div>
                        </div>
                    );
                })}

                <div className="pt-2 mt-2 border-t border-sidebar-border">
                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        onClick={onNavigate}
                        className="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-muted-foreground transition hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                    >
                        <LogOut className="size-4" />
                        <span>{t('Logout')}</span>
                    </Link>
                </div>
            </nav>

            <div className="space-y-2 border-t p-4">
                {isSuperAdmin && user?.organization && (
                    <div className="flex items-center gap-2 rounded-lg bg-primary/5 px-3 py-2 ring-1 ring-primary/10">
                        <Building2 className="size-3.5 shrink-0 text-primary" />
                        <span className="truncate text-xs font-medium text-foreground">{user.organization.name}</span>
                    </div>
                )}
                <div className="flex items-center gap-3 rounded-lg bg-background p-3 ring-1 ring-border">
                    <Avatar>
                        <AvatarFallback>{initials(user.name)}</AvatarFallback>
                    </Avatar>
                    <div className="min-w-0 flex-1">
                        <div className="truncate text-sm font-medium">{user.name}</div>
                        <div className="truncate text-xs text-muted-foreground">{user.email}</div>
                    </div>
                </div>
            </div>
        </aside>
    );
}

interface AuthenticatedLayoutProps {
    header?: ReactNode;
    children: ReactNode;
}

export default function AuthenticatedLayout({ header, children }: AuthenticatedLayoutProps) {
    const { auth, app, isSuperAdmin = false, isFacultyRep = false, isDean = false } = usePage<PageProps>().props;
    const { t } = useI18n();
    const user = auth?.user;

    if (!user) {
        if (typeof window !== 'undefined' && typeof route !== 'undefined') {
            window.location.href = route('login');
        }
        return null;
    }

    const [mobileOpen, setMobileOpen] = useState(false);
    const [userMenuOpen, setUserMenuOpen] = useState(false);
    const [notifOpen, setNotifOpen] = useState(false);
    const { notification_count = 0, notifications = [] } = usePage<PageProps>().props;
    const [notifCount, setNotifCount] = useState(notification_count);
    const [notifItems, setNotifItems] = useState<any[]>(notifications as any[]);

    useEffect(() => {
        setNotifCount(notification_count);
        setNotifItems(notifications as any[]);
    }, [notification_count, notifications]);

    useEffect(() => {
        let active = true;
        const tick = async () => {
            try {
                const res = await fetch(route('notifications.unread-count'), {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    // An expired session may redirect to login. Do not let a polling request follow a redirect chain.
                    redirect: 'manual',
                });
                if (!active || !res.ok || res.type === 'opaqueredirect') return;
                const json = await res.json();
                if (typeof json?.count === 'number') setNotifCount(json.count);
            } catch {
                // ignore transient polling errors
            }
        };
        tick();
        const id = setInterval(tick, 30000);
        return () => {
            active = false;
            clearInterval(id);
        };
    }, []);

    const toggleNotif = () => {
        const next = !notifOpen;
        setNotifOpen(next);
        if (next) {
            router.reload({ only: ['notifications', 'notification_count'], preserveScroll: true });
        }
    };

    return (
        <div className="min-h-screen bg-background text-foreground">
            <div className="flex min-h-screen">
                <Sidebar user={user} isSuperAdmin={isSuperAdmin} isFacultyRep={isFacultyRep} isDean={isDean} app={app} />

                {mobileOpen && (
                    <div className="fixed inset-0 z-40 lg:hidden">
                        <button
                            type="button"
                            aria-label="Close navigation"
                            className="absolute inset-0 bg-black/20"
                            onClick={() => setMobileOpen(false)}
                        />
                        <div className="relative h-full w-72 shadow-xl">
                            <Sidebar user={user} mobile isSuperAdmin={isSuperAdmin} isFacultyRep={isFacultyRep} isDean={isDean} app={app} onNavigate={() => setMobileOpen(false)} />
                        </div>
                    </div>
                )}

                <div className="flex min-w-0 flex-1 flex-col">
                    <header className="sticky top-0 z-30 border-b bg-background/95 backdrop-blur">
                        <div className="flex h-16 items-center gap-3 px-4 sm:px-6">
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="lg:hidden"
                                onClick={() => setMobileOpen(true)}
                                aria-label={t('Open mobile menu')}
                            >
                                <Menu className="size-5" />
                            </Button>

                            <div className="hidden min-w-0 flex-1 items-center gap-2 rounded-lg border bg-muted/30 px-3 py-2 text-sm text-muted-foreground md:flex">
                                <Search className="size-4" />
                                <span>{t('Quick search')}</span>
                            </div>

                            <div className="ml-auto flex items-center gap-2">
                                <div className="relative">
                                    <Button variant="ghost" size="icon" onClick={toggleNotif} className="relative" aria-label={t('Toggle notifications')}>
                                        <Bell className="size-5" />
                                        {notifCount > 0 && (
                                            <span className="absolute -right-0.5 -top-0.5 flex size-4 items-center justify-center rounded-full bg-destructive text-[9px] font-bold text-destructive-foreground">
                                                {notifCount > 9 ? '9+' : notifCount}
                                            </span>
                                        )}
                                    </Button>
                                    {notifOpen && (
                                        <>
                                            <div className="fixed inset-0 z-40" onClick={() => setNotifOpen(false)} />
                                            <div className="absolute right-0 z-50 mt-1 w-80 rounded-lg bg-popover p-2 text-popover-foreground shadow-md ring-1 ring-foreground/10">
                                                <div className="flex items-center justify-between px-1 py-1">
                                                    <span className="text-xs font-semibold">{t('Notifications')}</span>
                                                    {notifCount > 0 && (
                                                        <button
                                                            type="button"
                                                            onClick={() => { router.post(route('notifications.mark-all-read'), {}, { preserveScroll: true }); setNotifCount(0); setNotifOpen(false); }}
                                                            className="text-[10px] text-primary hover:underline"
                                                        >
                                                            {t('Mark all read')}
                                                        </button>
                                                    )}
                                                </div>
                                                <div className="mt-1 max-h-72 space-y-1 overflow-y-auto">
                                                    {notifItems.length === 0 && (
                                                        <p className="p-3 text-center text-xs text-muted-foreground">{t('No notifications')}</p>
                                                    )}
                                                    {notifItems.map((n: any) => (
                                                        <button
                                                            key={n.id}
                                                            type="button"
                                                            onClick={() => {
                                                                router.post(route('notifications.mark-read', n.id), {}, { preserveScroll: true });
                                                                if (!n.read_at) setNotifCount((c) => Math.max(0, c - 1));
                                                                setNotifItems((items) => items.map((i) => (i.id === n.id ? { ...i, read_at: new Date().toISOString() } : i)));
                                                            }}
                                                            className={`w-full rounded-md px-2 py-2 text-left text-xs transition hover:bg-muted ${!n.read_at ? 'bg-muted/50 font-medium' : ''}`}
                                                        >
                                                            <p>{n.data?.message || t('Notification')}</p>
                                                            <p className="mt-0.5 text-[10px] text-muted-foreground">{n.created_at}</p>
                                                        </button>
                                                    ))}
                                                </div>
                                                {notifItems.length > 0 && (
                                                    <Link href={route('notifications.index')} className="block rounded-md px-2 py-1.5 text-center text-xs text-primary hover:bg-muted">
                                                        {t('View all notifications')}
                                                    </Link>
                                                )}
                                            </div>
                                        </>
                                    )}
                                </div>

                                <LocaleSwitcher compact showLabel={false} />

                                <div className="relative">
                                    <Button variant="outline" className="gap-2" onClick={() => setUserMenuOpen(!userMenuOpen)} aria-label={t('Toggle user menu')}>
                                        <Avatar className="size-6">
                                            <AvatarFallback className="text-xs">
                                                {initials(user.name)}
                                            </AvatarFallback>
                                        </Avatar>
                                        <span className="hidden max-w-36 truncate sm:inline">
                                            {user.name}
                                        </span>
                                        <ChevronDown className="size-4 text-muted-foreground" />
                                    </Button>
                                    {userMenuOpen && (
                                        <>
                                            <div className="fixed inset-0 z-40" onClick={() => setUserMenuOpen(false)} />
                                            <div className="absolute right-0 z-50 mt-1 w-56 rounded-lg bg-popover p-1 text-popover-foreground shadow-md ring-1 ring-foreground/10">
                                                <div className="px-1.5 py-1">
                                                    <div className="truncate text-sm font-medium">{user.name}</div>
                                                    <div className="truncate text-xs text-muted-foreground">{user.email}</div>
                                                </div>
                                                <div className="-mx-1 my-1 h-px bg-border" />
                                                <Link
                                                    href={route('profile.edit')}
                                                    className="relative flex cursor-default select-none items-center gap-1.5 rounded-md px-1.5 py-1 text-sm outline-none hover:bg-accent hover:text-accent-foreground"
                                                    onClick={() => setUserMenuOpen(false)}
                                                >
                                                    <UserCircle className="size-4" />
                                                    {t('Profile')}
                                                </Link>
                                                <Link
                                                    href={route('logout')}
                                                    method="post"
                                                    as="button"
                                                    className="relative flex cursor-default select-none items-center gap-1.5 rounded-md px-1.5 py-1 text-sm outline-none hover:bg-accent hover:text-accent-foreground"
                                                    onClick={() => setUserMenuOpen(false)}
                                                >
                                                    <LogOut className="size-4" />
                                                    {t('Logout')}
                                                </Link>
                                            </div>
                                        </>
                                    )}
                                </div>
                            </div>
                        </div>
                    </header>

                    {header && (
                        <>
                            <div className="px-4 pt-6 sm:px-6">{header}</div>
                            <Separator className="mt-6" />
                        </>
                    )}

                    <main className="flex-1 px-4 py-6 sm:px-6">{children}</main>
                </div>
            </div>
        </div>
    );
}
