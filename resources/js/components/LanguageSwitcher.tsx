import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { router, usePage } from '@inertiajs/react';
import { Check, ChevronDown, Globe } from 'lucide-react';
import { useI18n } from '@/lib/i18n';
import type { PageProps } from '@/types';

interface LanguageSwitcherProps {
    className?: string;
}

export default function LanguageSwitcher({ className }: LanguageSwitcherProps) {
    const { locale = 'en', availableLocales = {} } = usePage<PageProps>().props;
    const { t } = useI18n();

    const entries = Object.entries(availableLocales as Record<string, string>);

    if (entries.length < 2) return null;

    const switchLocale = (code: string) => {
        if (code === locale) return;

        router.post(route('language.update', code), {}, {
            preserveScroll: true,
            preserveState: true,
        });
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="outline" size="sm" className={`gap-1.5 ${className ?? ''}`} aria-label={t('Languages')}>
                    <Globe className="size-4" />
                    <span className="hidden sm:inline">{locale.toUpperCase()}</span>
                    <ChevronDown className="size-3 text-muted-foreground" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-48">
                <DropdownMenuLabel>{t('Languages')}</DropdownMenuLabel>
                <DropdownMenuSeparator />
                {entries.map(([code, label]) => (
                    <DropdownMenuItem
                        key={code}
                        onSelect={() => switchLocale(code)}
                        className="flex items-center justify-between"
                    >
                        {label}
                        {code === locale && <Check className="size-3.5 text-primary" />}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}