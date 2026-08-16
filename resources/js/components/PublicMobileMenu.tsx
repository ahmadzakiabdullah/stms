import { useI18n } from '@/lib/i18n';
import { Link } from '@inertiajs/react';
import { Menu, X } from 'lucide-react';
import { useState } from 'react';

export type PublicMenuLink = { href: string; label: string; current?: boolean };

export default function PublicMobileMenu({ links }: { links: PublicMenuLink[] }) {
    const { t } = useI18n();
    const [open, setOpen] = useState(false);

    return (
        <div className="relative xl:hidden">
            <button
                type="button"
                aria-label={open ? t('Close menu') : t('Open menu')}
                aria-expanded={open}
                aria-controls="public-mobile-navigation"
                onClick={() => setOpen(current => !current)}
                className="flex size-9 items-center justify-center rounded-lg border border-white/15 bg-white/5 text-white transition hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--public-accent)]"
            >
                {open ? <X aria-hidden="true" className="size-4" /> : <Menu aria-hidden="true" className="size-4" />}
            </button>
            {open && (
                <nav
                    id="public-mobile-navigation"
                    aria-label={t('Public navigation')}
                    className="absolute right-0 top-12 z-50 w-72 overflow-hidden rounded-2xl border border-white/15 bg-[var(--public-dark)] p-2 text-white shadow-2xl"
                >
                    {links.map(link => (
                        <Link
                            key={`${link.href}-${link.label}`}
                            href={link.href}
                            aria-current={link.current ? 'page' : undefined}
                            onClick={() => setOpen(false)}
                            className={`flex min-h-11 items-center rounded-xl px-3 text-sm font-bold transition hover:bg-white/10 ${link.current ? 'bg-white/10 text-[var(--public-highlight)]' : 'text-white/75 hover:text-white'}`}
                        >
                            {link.label}
                        </Link>
                    ))}
                    <div className="mt-2 flex items-center justify-between gap-3 border-t border-white/10 p-2 pt-4">
                        <LocaleSwitcher compact showLabel={false} />
                        <PublicLoginButton />
                    </div>
                </nav>
            )}
        </div>
    );
}
import LocaleSwitcher from '@/components/LocaleSwitcher';
import PublicLoginButton from '@/components/PublicLoginButton';
