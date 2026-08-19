import { useI18n } from '@/lib/i18n';
import { type PublicThemeSettings } from '@/lib/publicTheme';
import { Link } from '@inertiajs/react';

type Props = { appName: string; settings: { logo_url?: string | null } & PublicThemeSettings };

export default function PublicFooter({ appName, settings }: Props) {
    const { t } = useI18n();

    return <footer className="bg-[var(--public-dark)] text-white/55">
        <div className="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-[1.4fr_1fr]">
            <div className="flex items-center gap-3">{settings.logo_url && <img src={settings.logo_url} alt="" className="h-12 w-auto object-contain" />}<div><b className="text-white">{appName}</b><p className="mt-1 text-sm">{t('Official sports information portal')}</p></div></div>
            <nav aria-label={t('Quick links')} className="flex flex-wrap gap-x-6 gap-y-3 text-sm font-bold md:justify-end">
                <Link href={route('public.index')} className="hover:text-[var(--public-highlight)]">{t('Home')}</Link>
                <Link href={route('public.sports')} className="hover:text-[var(--public-highlight)]">{t('Sports')}</Link>
                <Link href={route('public.schedule')} className="hover:text-[var(--public-highlight)]">{t('Schedule')}</Link>
                <Link href={route('public.results')} className="hover:text-[var(--public-highlight)]">{t('Results')}</Link>
                <Link href={route('public.matches')} className="hover:text-[var(--public-highlight)]">{t('Matches')}</Link>
                <Link href={route('public.contact')} className="hover:text-[var(--public-highlight)]">{t('Contact')}</Link>
            </nav>
        </div>
        <div className="border-t border-white/10 px-4 py-5 text-center text-xs">© 2026 Universiti Teknikal Malaysia Melaka (UTeM)</div>
    </footer>;
}