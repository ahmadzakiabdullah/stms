import LocaleSwitcher from '@/components/LocaleSwitcher';
import PublicLoginButton from '@/components/PublicLoginButton';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/lib/i18n';
import { Link } from '@inertiajs/react';
import { Menu, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

export type PublicMenuLink = { href: string; label: string; current?: boolean };
export type PublicMenuGroup = { label: string; links: PublicMenuLink[] };

export default function PublicMobileMenu({ links, groups = [] }: { links: PublicMenuLink[]; groups?: PublicMenuGroup[] }) {
    const { t } = useI18n();
    const [open, setOpen] = useState(false);
    const containerRef = useRef<HTMLDivElement>(null);
    const buttonRef = useRef<HTMLButtonElement>(null);
    const panelRef = useRef<HTMLElement>(null);

    useEffect(() => {
        if (!open) return;

        const focusableSelector = 'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])';

        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                setOpen(false);
                buttonRef.current?.focus();

                return;
            }

            if (event.key !== 'Tab' || !panelRef.current) return;

            const focusable = panelRef.current.querySelectorAll<HTMLElement>(focusableSelector);
            if (focusable.length === 0) return;

            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        };

        const onPointerDown = (event: MouseEvent | TouchEvent) => {
            if (containerRef.current && !containerRef.current.contains(event.target as Node)) {
                setOpen(false);
            }
        };

        document.addEventListener('keydown', onKeyDown);
        document.addEventListener('mousedown', onPointerDown);
        document.addEventListener('touchstart', onPointerDown);

        panelRef.current?.querySelector<HTMLElement>(focusableSelector)?.focus();

        return () => {
            document.removeEventListener('keydown', onKeyDown);
            document.removeEventListener('mousedown', onPointerDown);
            document.removeEventListener('touchstart', onPointerDown);
        };
    }, [open]);

    return (
        <div ref={containerRef} className="relative xl:hidden">
            <Button
                variant="ghost"
                size="icon-lg"
                ref={buttonRef}
                type="button"
                aria-label={open ? t('Close menu') : t('Open menu')}
                aria-expanded={open}
                aria-haspopup="true"
                aria-controls="public-mobile-navigation"
                onClick={() => setOpen(current => !current)}
                className="min-h-11 min-w-11 border-white/15 bg-white/5 text-white hover:bg-white/10 focus-visible:ring-[var(--public-accent)] sm:min-h-11 sm:min-w-11"
            >
                {open ? <X aria-hidden="true" className="size-5" /> : <Menu aria-hidden="true" className="size-5" />}
            </Button>
            {open && (
                <nav
                    ref={panelRef}
                    id="public-mobile-navigation"
                    aria-label={t('Public navigation')}
                    className="absolute right-0 top-14 z-50 w-72 overflow-hidden rounded-2xl border border-white/15 bg-[var(--public-dark)] p-2 text-white shadow-2xl"
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
                    {groups.map(group => (
                        <section key={group.label} className="mt-2 border-t border-white/10 pt-2" aria-labelledby={`public-mobile-${group.label.toLowerCase()}`}>
                            <h2 id={`public-mobile-${group.label.toLowerCase()}`} className="px-3 py-2 text-xs font-black uppercase tracking-[.16em] text-[var(--public-accent)]">
                                {group.label}
                            </h2>
                            <div className="space-y-0.5">
                                {group.links.map(link => (
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
                            </div>
                        </section>
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
