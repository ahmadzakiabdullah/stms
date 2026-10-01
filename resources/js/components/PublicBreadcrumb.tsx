import { useI18n } from '@/lib/i18n';
import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';

type Parent = { href: string; label: string };
type Props = { current: string; parent?: Parent; surface?: 'dark' | 'light' };

export default function PublicBreadcrumb({ current, parent, surface = 'dark' }: Props) {
    const { t } = useI18n();
    const divider = <ChevronRight aria-hidden="true" className="size-3.5 shrink-0 opacity-60" />;
    const muted = surface === 'dark' ? 'text-white/70' : 'text-[var(--public-dark-faint)]';
    const link = surface === 'dark' ? 'hover:text-white focus-visible:ring-[var(--public-highlight)]' : 'hover:text-[var(--public-primary)] focus-visible:ring-[var(--public-primary)]';
    const currentText = surface === 'dark' ? 'text-white' : 'text-[var(--public-text)]';

    return (
        <nav aria-label={t('Breadcrumb')} className={`mb-5 text-xs font-semibold ${muted}`}>
            <ol className="flex flex-wrap items-center gap-2">
                <li><Link href={route('public.index')} className={`inline-flex min-h-11 items-center rounded-sm focus-visible:outline-none focus-visible:ring-2 ${link}`}>{t('Public Home')}</Link></li>
                {parent && <>
                    <li>{divider}</li>
                    <li><Link href={parent.href} className={`inline-flex min-h-11 items-center rounded-sm focus-visible:outline-none focus-visible:ring-2 ${link}`}>{parent.label}</Link></li>
                </>}
                <li>{divider}</li>
                <li aria-current="page" className={`max-w-[min(70vw,36rem)] truncate ${currentText}`}>{current}</li>
            </ol>
        </nav>
    );
}
