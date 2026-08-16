import LocaleSwitcher from '@/components/LocaleSwitcher';
import PublicLoginButton from '@/components/PublicLoginButton';
import PublicMobileMenu from '@/components/PublicMobileMenu';
import PublicDesktopNav from '@/components/PublicDesktopNav';
import PublicAnnouncementBar from '@/components/PublicAnnouncementBar';
import { useI18n } from '@/lib/i18n';
import { publicThemeStyle, type PublicThemeSettings } from '@/lib/publicTheme';
import { type PageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { type ReactNode } from 'react';

type Props = {
    children: ReactNode;
    title: string;
    appName: string;
    withCanonical?: boolean;
};

export default function PublicLayout({ children, title, appName, withCanonical = false }: Props) {
    const { t } = useI18n();
    const { settings = {} } = usePage<PageProps & { settings?: { logo_url?: string | null } & PublicThemeSettings }>().props;

    return <>
        <Head title={title}>{withCanonical && <link rel="canonical" href={route('public.contact')} />}</Head>
        <div className="public-cosmic min-h-screen overflow-hidden bg-[var(--public-background)] text-[var(--public-text)]" style={publicThemeStyle(settings)}>
            <PublicAnnouncementBar />
            <header className="absolute left-0 right-0 top-10 z-50 px-3 pt-3 sm:px-6 sm:pt-5">
                <div className="mx-auto flex min-h-[68px] max-w-7xl items-center justify-between gap-4 rounded-2xl border border-white/15 bg-[color:var(--public-dark)] px-4 py-2.5 text-white shadow-2xl shadow-black/10 sm:px-5 xl:grid xl:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)]">
                    <Link href={route('public.index')} className="flex min-w-0 items-center gap-3 rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#d7ef59]">
                        {settings.logo_url && <img src={settings.logo_url} alt="UTeM" className="h-11 w-auto shrink-0 object-contain" />}
                        <span className="min-w-0"><small className="block text-[9px] font-black uppercase tracking-[.2em] text-[#d7ef59]">{t('Official portal')}</small><b className="block max-w-[7rem] truncate text-sm sm:max-w-xs sm:text-base">{appName}</b></span>
                    </Link>
                    <PublicDesktopNav links={[{ href: route('public.index'), label: t('Home') }, { href: `${route('public.index')}#overview`, label: t('Sports') }, { href: `${route('public.index')}#schedule`, label: t('Schedule') }, { href: `${route('public.index')}#results`, label: t('Results') }, { href: `${route('public.index')}#medals`, label: t('Medal standings') }, { href: route('public.contact'), label: t('Contact'), current: true }]} />
                    <div className="flex shrink-0 items-center justify-end xl:hidden">
                        <PublicMobileMenu links={[{ href: route('public.index'), label: t('Home') }, { href: `${route('public.index')}#overview`, label: t('Sports') }, { href: `${route('public.index')}#schedule`, label: t('Schedule') }, { href: `${route('public.index')}#results`, label: t('Results') }, { href: `${route('public.index')}#medals`, label: t('Medal standings') }, { href: route('public.contact'), label: t('Contact'), current: true }]} />
                    </div>
                    <div className="hidden items-center justify-end gap-2 xl:flex">
                        <span className="mr-1 h-7 w-px bg-white/10" />
                        <LocaleSwitcher compact showLabel={false} />
                        <PublicLoginButton />
                    </div>
                </div>
            </header>
            {children}
            <footer className="bg-[#091a16] text-white/55">
                <div className="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-[1.4fr_1fr]">
                    <div className="flex items-center gap-3">{settings.logo_url && <img src={settings.logo_url} alt="" className="h-12 w-auto object-contain" />}<div><b className="text-white">{appName}</b><p className="mt-1 text-sm">{t('Official sports information portal')}</p></div></div>
                    <nav aria-label={t('Quick links')} className="flex flex-wrap gap-x-6 gap-y-3 text-sm font-bold md:justify-end"><Link href={route('public.index')} className="hover:text-[#d7ef59]">{t('Home')}</Link><Link href={route('public.contact')} className="hover:text-[#d7ef59]">{t('Contact')}</Link><Link href={route('login')} className="hover:text-[#d7ef59]">{t('Log in')}</Link></nav>
                </div>
                <div className="border-t border-white/10 px-4 py-5 text-center text-xs">© 2026 Universiti Teknikal Malaysia Melaka (UTeM)</div>
            </footer>
        </div>
    </>;
}
