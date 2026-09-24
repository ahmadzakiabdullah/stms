import LocaleSwitcher from '@/components/LocaleSwitcher';
import PublicAnnouncementBar from '@/components/PublicAnnouncementBar';
import PublicDesktopNav from '@/components/PublicDesktopNav';
import PublicLoginButton from '@/components/PublicLoginButton';
import PublicMobileMenu, { type PublicMenuItem } from '@/components/PublicMobileMenu';
import SafeImage from '@/components/SafeImage';
import { useI18n } from '@/lib/i18n';
import { type PublicThemeSettings } from '@/lib/publicTheme';
import { Link } from '@inertiajs/react';

export type PublicHeaderCurrent = 'home' | 'sports' | 'schedule' | 'athletes' | 'contact' | 'information';
type Props = { appName: string; settings: { logo_url?: string | null; inverse_logo_url?: string | null } & PublicThemeSettings; sessionBranding?: { logo_url?: string | null; inverse_logo_url?: string | null }; current?: PublicHeaderCurrent };

export default function PublicHeader({ appName, settings, sessionBranding, current }: Props) {
    const { t } = useI18n();
    const organizationLogoUrl = settings.inverse_logo_url ?? settings.logo_url;
    const sessionLogoUrl = sessionBranding?.inverse_logo_url ?? sessionBranding?.logo_url;
    const information = { label: t('Information'), links: [
        { href: route('public.general-information'), label: t('General Information') },
        { href: route('public.committee'), label: t('Jawatankuasa Induk') },
        { href: route('public.student-committee'), label: t('Jawatankuasa Pelaksana') },
        { href: route('public.game-chairpersons'), label: t('Pengerusi Permainan') },
        { href: route('public.important-dates'), label: t('Tarikh Penting') },
    ] };
    const competition = { label: t('Competition'), links: [
            { href: route('public.sports'), label: t('Sports'), current: current === 'sports' },
            { href: route('public.faculties'), label: t('Faculties') },
            { href: route('public.venues'), label: t('Venues') },
    ] };
    const items: PublicMenuItem[] = [
        { type: 'link', link: { href: route('public.index'), label: t('Public Home'), current: current === 'home' } },
        { type: 'group', group: { ...information, current: current === 'information' } },
        { type: 'group', group: competition },
        { type: 'link', link: { href: route('public.schedule'), label: t('Public Schedule & Results'), current: current === 'schedule' } },
        { type: 'link', link: { href: route('public.athletes'), label: t('Athletes & Teams'), current: current === 'athletes' } },
        { type: 'link', link: { href: route('public.contact'), label: t('Public Contact'), current: current === 'contact' } },
    ];

    return <div className="absolute inset-x-0 top-0 z-50">
        <PublicAnnouncementBar />
        <header className="px-3 pt-3 sm:px-6 sm:pt-5">
            <div className="mx-auto flex min-h-[68px] max-w-7xl items-center justify-between gap-4 rounded-2xl border border-white/15 bg-[color:var(--public-dark)] px-4 py-2.5 text-white shadow-2xl sm:px-5 xl:grid xl:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)]">
                <Link href={route('public.index')} className="flex min-h-11 min-w-0 flex-col items-start justify-center gap-1 rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--public-highlight)]">
                    {(organizationLogoUrl || sessionLogoUrl) && <span className="flex shrink-0 items-center gap-2">
                        {organizationLogoUrl && <SafeImage src={organizationLogoUrl} alt="" className="h-10 w-auto max-w-[7rem] object-contain" />}
                        {organizationLogoUrl && sessionLogoUrl && <span aria-hidden="true" className="h-8 w-px bg-white/20" />}
                        {sessionLogoUrl && <SafeImage src={sessionLogoUrl} alt="" className="h-10 w-auto max-w-[7rem] object-contain" />}
                    </span>}
                    <span className="min-w-0"><b className="block max-w-[15rem] truncate text-sm leading-tight sm:max-w-xs sm:text-base">{appName}</b></span>
                </Link>
                <PublicDesktopNav items={items} />
                <div className="flex shrink-0 items-center justify-end xl:hidden"><PublicMobileMenu items={items} /></div>
                <div className="hidden items-center justify-end gap-2 xl:flex"><span className="mr-1 h-7 w-px bg-white/10" /><LocaleSwitcher compact showLabel={false} /><PublicLoginButton /></div>
            </div>
        </header>
    </div>;
}
