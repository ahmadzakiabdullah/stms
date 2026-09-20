import { type PublicMenuLink } from '@/components/PublicMobileMenu';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useI18n } from '@/lib/i18n';
import { Link } from '@inertiajs/react';

type Props = { links: PublicMenuLink[]; groups?: { label: string; links: PublicMenuLink[] }[] };

export default function PublicDesktopNav({ links, groups = [] }: Props) {
    const { t } = useI18n();

    return (
        <nav
            aria-label={t('Public navigation')}
            className="hidden items-center gap-0.5 rounded-xl border border-white/10 bg-white/5 p-1 xl:flex"
        >
            {links.map(link => (
                <Button
                    asChild
                    variant="ghost"
                    size="sm"
                    key={`${link.href}-${link.label}`}
                >
                    <Link href={link.href} aria-current={link.current ? 'page' : undefined} className={`relative min-h-11 whitespace-nowrap rounded-lg px-3 py-2 text-xs font-black transition sm:min-h-0 ${link.current ? 'bg-white/10 text-white' : 'text-white/60 hover:bg-white/5 hover:text-white'}`}>
                        {link.label}
                        {link.current && <span aria-hidden="true" className="absolute inset-x-3 -bottom-1 h-0.5 rounded-full bg-[var(--public-highlight)]" />}
                    </Link>
                </Button>
            ))}
            {groups.map(group => {
                const active = group.links.some(link => link.current);

                return (
                    <DropdownMenu key={group.label}>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="sm" className={`min-h-11 rounded-lg px-3 text-xs font-black sm:min-h-0 ${active ? 'bg-white/10 text-white' : 'text-white/60 hover:bg-white/5 hover:text-white'}`} aria-current={active ? 'page' : undefined}>
                                {group.label}
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="center">
                            <DropdownMenuLabel>{group.label}</DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            {group.links.map(link => <DropdownMenuItem key={link.href} asChild><Link href={link.href} aria-current={link.current ? 'page' : undefined}>{link.label}</Link></DropdownMenuItem>)}
                        </DropdownMenuContent>
                    </DropdownMenu>
                );
            })}
        </nav>
    );
}
