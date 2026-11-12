import ApplicationLogo from '@/components/ApplicationLogo';
import LocaleSwitcher from '@/components/LocaleSwitcher';
import { useI18n } from '@/lib/i18n';
import { Link, usePage } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    const { settings = {}, session_branding: sessionBranding = {} } = usePage().props;
    const { t } = useI18n();
    const logoUrl = settings?.logo_url;
    const safLogoUrl = sessionBranding?.logo_url || sessionBranding?.inverse_logo_url;

    return (
        <div className="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0">
            <div>
                <Link href={route('public.index')} aria-label={t('STMS home')}>
                    <span className="flex items-center justify-center gap-3">
                        {logoUrl ? (
                            <img src={logoUrl} alt="UTeM logo" className="h-20 w-auto" />
                        ) : (
                            <ApplicationLogo className="h-20 w-20 fill-current text-gray-500" />
                        )}
                        {safLogoUrl && (
                            <>
                                <span aria-hidden="true" className="h-12 w-px bg-gray-300" />
                                <img src={safLogoUrl} alt="SAF 20 logo" className="h-20 w-auto max-w-[9rem] object-contain" />
                            </>
                        )}
                    </span>
                </Link>
            </div>

            <div className="mt-4 flex justify-center sm:hidden">
                <img src="/images/mascots/pose-welcome.webp" alt="Maskot SAF 20" className="h-24 w-auto object-contain drop-shadow-lg" />
            </div>

            <div className="relative mt-6 w-full sm:max-w-md">
                <img src="/images/mascots/pose-welcome.webp" alt="Maskot SAF 20" className="pointer-events-none absolute -right-32 bottom-3 z-10 hidden h-44 w-auto object-contain drop-shadow-xl lg:block" />
                <div className="w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:rounded-lg">
                    {children}
                </div>
            </div>

            <div className="mt-4 flex justify-center">
                <LocaleSwitcher compact showLabel={false} />
            </div>
        </div>
    );
}
