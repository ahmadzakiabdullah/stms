import { type PublicMenuItem } from '@/components/PublicMobileMenu';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/lib/i18n';
import { Link } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import { useEffect, useState } from 'react';

type Props = { items: PublicMenuItem[] };

export default function PublicDesktopNav({ items }: Props) {
    const { t } = useI18n();
    const [openGroup, setOpenGroup] = useState<string | null>(null);

    useEffect(() => {
        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') setOpenGroup(null);
        };

        const onPointerDown = (event: PointerEvent) => {
            if (!(event.target instanceof Element) || !event.target.closest('[data-public-menu-group]')) setOpenGroup(null);
        };

        document.addEventListener('keydown', onKeyDown);
        document.addEventListener('pointerdown', onPointerDown);

        return () => {
            document.removeEventListener('keydown', onKeyDown);
            document.removeEventListener('pointerdown', onPointerDown);
        };
    }, []);

    return (
        <nav
            aria-label={t('Public navigation')}
            className="hidden items-center gap-0.5 rounded-xl border border-white/10 bg-white/5 p-1 xl:flex"
        >
            {items.map(item => {
                if (item.type === 'link') {
                    return (
                        <Button
                            asChild
                            variant="ghost"
                            size="sm"
                            key={`${item.link.href}-${item.link.label}`}
                        >
                            <Link href={item.link.href} aria-current={item.link.current ? 'page' : undefined} className={`relative min-h-11 whitespace-nowrap rounded-lg px-3 py-2 text-xs font-black transition sm:min-h-0 ${item.link.current ? 'bg-white/10 text-white' : 'text-white/60 hover:bg-white/5 hover:text-white'}`}>
                                {item.link.label}
                                {item.link.current && <span aria-hidden="true" className="absolute inset-x-3 -bottom-1 h-0.5 rounded-full bg-[var(--public-highlight)]" />}
                            </Link>
                        </Button>
                    );
                }

                const { group } = item;
                const active = group.current || group.links.some(link => link.current);

                return (
                    <div key={group.label} data-public-menu-group className="group relative">
                        <Button
                            variant="ghost"
                            size="sm"
                            type="button"
                            aria-haspopup="true"
                            aria-expanded={openGroup === group.label}
                            onClick={() => setOpenGroup(current => current === group.label ? null : group.label)}
                            className={`min-h-11 gap-1 rounded-lg px-3 text-xs font-black sm:min-h-0 ${active ? 'bg-white/10 text-white' : 'text-white/60 hover:bg-white/5 hover:text-white'}`}
                        >
                            {group.label}
                            <ChevronDown aria-hidden="true" className="size-3.5 transition-transform group-hover:rotate-180 group-focus-within:rotate-180" />
                        </Button>
                        <div className={`${openGroup === group.label ? 'visible translate-y-0 opacity-100' : 'invisible translate-y-1 opacity-0'} absolute left-1/2 top-full z-50 w-48 -translate-x-1/2 pt-2 transition-all duration-150 group-hover:visible group-hover:translate-y-0 group-hover:opacity-100 group-focus-within:visible group-focus-within:translate-y-0 group-focus-within:opacity-100`}>
                            <div className="rounded-xl border border-border bg-popover p-2 text-popover-foreground shadow-xl ring-1 ring-foreground/10">
                                <p className="px-2 py-1 text-xs font-black uppercase tracking-[.14em] text-muted-foreground">{group.label}</p>
                                <div className="mt-1 space-y-0.5 border-t border-border pt-1">
                                    {group.links.map(link => (
                                        <Link
                                            key={link.href}
                                            href={link.href}
                                            aria-current={link.current ? 'page' : undefined}
                                            className={`flex min-h-10 items-center rounded-lg px-2 text-sm font-semibold outline-none transition hover:bg-accent hover:text-accent-foreground focus-visible:bg-accent focus-visible:text-accent-foreground ${link.current ? 'bg-accent text-accent-foreground' : 'text-foreground'}`}
                                        >
                                            {link.label}
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>
                );
            })}
        </nav>
    );
}
