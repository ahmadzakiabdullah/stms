import { useI18n } from '@/lib/i18n';
import { type PublicThemeSettings } from '@/lib/publicTheme';
import { Link } from '@inertiajs/react';
import SafeImage from '@/components/SafeImage';

type Props = { appName: string; settings: { logo_url?: string | null; inverse_logo_url?: string | null } & PublicThemeSettings; sessionBranding?: { logo_url?: string | null; inverse_logo_url?: string | null } };

export default function PublicFooter({ appName, settings, sessionBranding }: Props) {
    const { t } = useI18n();
    const logoUrl = sessionBranding?.inverse_logo_url ?? sessionBranding?.logo_url ?? settings.inverse_logo_url ?? settings.logo_url;

    const linkClass = 'inline-flex min-h-10 items-center text-sm font-semibold text-white/70 transition-colors hover:text-white focus-visible:text-white';

    return <footer className="bg-[var(--public-dark)] text-white/65">
        <div className="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-[1.2fr_2fr] lg:py-14">
            <div className="flex items-start gap-3">{logoUrl && <SafeImage src={logoUrl} alt="" className="h-11 w-auto max-w-28 object-contain" />}<div><b className="text-white">{appName}</b><p className="mt-2 max-w-sm text-sm leading-6">{t('Official sports information portal')}</p></div></div>
            <div className="grid grid-cols-2 gap-x-6 gap-y-8 sm:grid-cols-3">
                <nav aria-label={t('Competition')} className="flex flex-col items-start gap-1">
                    <h2 className="mb-1 text-xs font-bold uppercase tracking-wider text-white">{t('Competition')}</h2>
                    <Link href={route('public.schedule')} className={linkClass}>{t('Public Schedule & Results')}</Link>
                    <Link href={route('public.sports')} className={linkClass}>{t('Sports')}</Link>
                    <Link href={route('public.faculties')} className={linkClass}>{t('Faculties')}</Link>
                    <Link href={route('public.venues')} className={linkClass}>{t('Venues')}</Link>
                </nav>
                <nav aria-label={t('Information')} className="flex flex-col items-start gap-1">
                    <h2 className="mb-1 text-xs font-bold uppercase tracking-wider text-white">{t('Information')}</h2>
                    <Link href={route('public.general-information')} className={linkClass}>{t('General Information')}</Link>
                    <Link href={route('public.important-dates')} className={linkClass}>{t('Tarikh Penting')}</Link>
                    <Link href={route('public.downloads')} className={linkClass}>{t('Downloads')}</Link>
                    <Link href={route('public.faq')} className={linkClass}>{t('FAQ')}</Link>
                </nav>
                <nav aria-label={t('Contact')} className="col-span-2 flex flex-col items-start gap-1 sm:col-span-1">
                    <h2 className="mb-1 text-xs font-bold uppercase tracking-wider text-white">{t('Contact')}</h2>
                    <Link href={route('public.contact')} className={linkClass}>{t('Public Contact')}</Link>
                    <Link href={route('public.committee')} className={linkClass}>{t('Jawatankuasa Induk')}</Link>
                    <Link href={route('public.student-committee')} className={linkClass}>{t('Jawatankuasa Pelaksana')}</Link>
                </nav>
            </div>
        </div>
        <div className="border-t border-white/15 px-4 py-5 text-center text-xs">© {new Date().getFullYear()} Universiti Teknikal Malaysia Melaka (UTeM)</div>
    </footer>;
}
