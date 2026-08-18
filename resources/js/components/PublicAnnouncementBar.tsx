import { useI18n } from '@/lib/i18n';
import { type PageProps } from '@/types';
import { usePage } from '@inertiajs/react';
import { CloudSun, MapPin } from 'lucide-react';

type Weather = {
    location: string;
    temperature: number;
    observed_at?: string | null;
    is_stale?: boolean;
} | null;

export default function PublicAnnouncementBar() {
    const { locale, t } = useI18n();
    const { weather = null } = usePage<PageProps & { weather?: Weather }>().props;
    const date = new Intl.DateTimeFormat(locale === 'ms' ? 'ms-MY' : 'en-MY', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
    }).format(new Date());
    const weatherLastUpdated = weather?.observed_at
        ? new Intl.DateTimeFormat(locale === 'ms' ? 'ms-MY' : 'en-MY', {
            hour: '2-digit', minute: '2-digit',
        }).format(new Date(weather.observed_at))
        : null;

    return (
        <div className="bg-[var(--public-dark)] text-white/75">
            <div className="mx-auto flex min-h-10 max-w-7xl items-center justify-between gap-3 px-4 text-[10px] font-bold sm:px-6 sm:text-xs">
                <time dateTime={new Date().toISOString().slice(0, 10)} className="capitalize">{date}</time>
                <div className="flex items-center gap-2">
                    <MapPin aria-hidden="true" className="size-3.5 text-[var(--public-accent)]" />
                    <span>{weather?.location || 'Durian Tunggal'}</span>
                    <span aria-hidden="true" className="h-4 w-px bg-white/15" />
                    <CloudSun aria-hidden="true" className="size-4 text-[var(--public-highlight)]" />
                    <span>{weather ? `${weather.temperature}°C` : t('Weather unavailable')}</span>
                    {weather?.is_stale ? <span className="rounded bg-amber-300/15 px-1.5 py-0.5 text-[9px] font-extrabold text-amber-200">
                        {t('Using recent weather')}{weatherLastUpdated ? ` · ${t('Last updated')} ${weatherLastUpdated}` : ''}
                    </span> : null}
                </div>
            </div>
        </div>
    );
}
