import { type PublicMenuLink } from '@/components/PublicMobileMenu';
import { useI18n } from '@/lib/i18n';
import { Link } from '@inertiajs/react';

export default function PublicDesktopNav({ links }: { links: PublicMenuLink[] }) {
    const { t } = useI18n();

    return (
        <nav
            aria-label={t('Public navigation')}
            className="hidden items-center gap-0.5 rounded-xl border border-white/10 bg-white/5 p-1 xl:flex"
        >
            {links.map(link => (
                <Link
                    key={`${link.href}-${link.label}`}
                    href={link.href}
                    aria-current={link.current ? 'page' : undefined}
                    className={`relative whitespace-nowrap rounded-lg px-3 py-2 text-[11px] font-black transition ${link.current ? 'bg-white/10 text-white shadow-sm' : 'text-white/60 hover:bg-white/5 hover:text-white'}`}
                >
                    {link.label}
                    {link.current && <span aria-hidden="true" className="absolute inset-x-3 -bottom-1 h-0.5 rounded-full bg-[var(--public-highlight)]" />}
                </Link>
            ))}
        </nav>
    );
}
