import LocaleSwitcher from '@/components/LocaleSwitcher';
import PublicAnnouncementBar from '@/components/PublicAnnouncementBar';
import PublicDesktopNav from '@/components/PublicDesktopNav';
import PublicLoginButton from '@/components/PublicLoginButton';
import PublicMobileMenu from '@/components/PublicMobileMenu';
import SafeImage from '@/components/SafeImage';
import { useI18n } from '@/lib/i18n';
import { type PublicThemeSettings } from '@/lib/publicTheme';
import { Link } from '@inertiajs/react';

export type PublicHeaderCurrent = 'home' | 'sports' | 'schedule' | 'athletes' | 'contact';
type Props = { appName: string; settings: { logo_url?: string | null; inverse_logo_url?: string | null } & PublicThemeSettings; current?: PublicHeaderCurrent };

export default function PublicHeader({ appName, settings, current }: Props) {
    const { t } = useI18n();
    const logoUrl = settings.inverse_logo_url ?? settings.logo_url;
    const links = [
        { href: route('public.index'), label: t('Home'), current: current === 'home' },
        { href: route('public.schedule'), label: t('Schedule & Results'), current: current === 'schedule' },
        { href: route('public.athletes'), label: t('Athletes & Teams'), current: current === 'athletes' },
        { href: route('public.contact'), label: t('Contact'), current: current === 'contact' },
    ];
    const groups = [
        { label: t('Competition'), links: [
            { href: route('public.sports'), label: t('Sports'), current: current === 'sports' },
            { href: route('public.faculties'), label: t('Faculties') },
            { href: route('public.venues'), label: t('Venues') },
        ] },
        { label: t('Information'), links: [
            { href: route('public.news'), label: t('News') },
            { href: route('public.downloads'), label: t('Downloads') },
            { href: route('public.faq'), label: t('FAQ') },
            { href: route('public.about'), label: t('About') },
        ] },
    ];

    return <div className="absolute inset-x-0 top-0 z-50">
        <PublicAnnouncementBar />
        <header className="px-3 pt-3 sm:px-6 sm:pt-5">
            <div className="mx-auto flex min-h-[68px] max-w-7xl items-center justify-between gap-4 rounded-2xl border border-white/15 bg-[color:var(--public-dark)] px-4 py-2.5 text-white shadow-2xl sm:px-5 xl:grid xl:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)]">
                <Link href={route('public.index')} className="flex min-h-11 min-w-0 items-center gap-3 rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--public-highlight)]">
                    {logoUrl && <SafeImage src={logoUrl} alt="" className="h-11 w-auto shrink-0 object-contain" />}
                    <span className="min-w-0"><small className="block text-xs font-black uppercase tracking-[.2em] text-[var(--public-accent)]">{t('Official portal')}</small><b className="block max-w-[10rem] truncate text-sm sm:max-w-xs sm:text-base">{appName}</b></span>
                </Link>
                <PublicDesktopNav links={links} groups={groups} />
                <div className="flex shrink-0 items-center justify-end xl:hidden"><PublicMobileMenu links={links} groups={groups} /></div>
                <div className="hidden items-center justify-end gap-2 xl:flex"><span className="mr-1 h-7 w-px bg-white/10" /><LocaleSwitcher compact showLabel={false} /><PublicLoginButton /></div>
            </div>
        </header>
    </div>;
}
