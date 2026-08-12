import ApplicationLogo from '@/components/ApplicationLogo';
import LocaleSwitcher from '@/components/LocaleSwitcher';
import { useI18n } from '@/lib/i18n';
import { Link, usePage } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    const { settings = {} } = usePage().props;
    const { t } = useI18n();
    const logoUrl = settings?.logo_url;

    return (
        <div className="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0">
            <div>
                <Link href={route('public.index')} aria-label={t('STMS home')}>
                    {logoUrl ? (
                        <img src={logoUrl} alt="Logo" className="h-20 w-auto" />
                    ) : (
                        <ApplicationLogo className="h-20 w-20 fill-current text-gray-500" />
                    )}
                </Link>
            </div>

            <div className="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg">
                {children}
            </div>

            <div className="mt-4 flex justify-center">
                <LocaleSwitcher compact showLabel={false} />
            </div>
        </div>
    );
}
