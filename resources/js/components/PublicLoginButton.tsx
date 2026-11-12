import { useI18n } from '@/lib/i18n';
import { Link, usePage } from '@inertiajs/react';
import { LayoutDashboard, LogIn } from 'lucide-react';
import type { PageProps } from '@/types';

export default function PublicLoginButton() {
    const { t } = useI18n();
    const isAuthenticated = Boolean(usePage<PageProps>().props.auth?.user);
    const label = isAuthenticated ? t('Dashboard') : t('Log in');

    return (
        <Link
            href={route(isAuthenticated ? 'dashboard' : 'login')}
            aria-label={label}
            className="group inline-flex min-h-11 shrink-0 items-center justify-center gap-1.5 rounded-lg bg-[var(--public-highlight)] px-3 text-xs font-black text-[var(--public-dark)] shadow-sm transition duration-200 hover:brightness-105 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--public-dark)] sm:min-h-9 sm:px-3 sm:text-xs"
        >
            <span className="flex size-5 items-center justify-center rounded-md bg-black/10 transition group-hover:bg-black/15">
                {isAuthenticated ? <LayoutDashboard aria-hidden="true" className="size-3" /> : <LogIn aria-hidden="true" className="size-3" />}
            </span>
            <span className="hidden sm:inline">{label}</span>
        </Link>
    );
}
