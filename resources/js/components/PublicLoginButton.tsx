import { useI18n } from '@/lib/i18n';
import { Link } from '@inertiajs/react';
import { LogIn } from 'lucide-react';

export default function PublicLoginButton() {
    const { t } = useI18n();

    return (
        <Link
            href={route('login')}
            aria-label={t('Log in')}
            className="group inline-flex min-h-9 shrink-0 items-center justify-center gap-1.5 rounded-lg bg-[var(--public-highlight)] px-2.5 text-[11px] font-black text-[var(--public-dark)] shadow-sm transition duration-200 hover:brightness-105 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--public-dark)] sm:px-3 sm:text-xs"
        >
            <span className="flex size-5 items-center justify-center rounded-md bg-black/10 transition group-hover:bg-black/15">
                <LogIn aria-hidden="true" className="size-3" />
            </span>
            <span className="hidden sm:inline">{t('Log in')}</span>
        </Link>
    );
}
