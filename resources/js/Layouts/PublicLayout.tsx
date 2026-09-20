import PublicFooter from '@/components/PublicFooter';
import PublicHeader, { type PublicHeaderCurrent } from '@/components/PublicHeader';
import { publicThemeStyle, type PublicThemeSettings } from '@/lib/publicTheme';
import { useI18n } from '@/lib/i18n';
import { type PageProps } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { type ReactNode } from 'react';

type Props = { children: ReactNode; title: string; appName: string; current?: PublicHeaderCurrent };

export default function PublicLayout({ children, title, appName, current }: Props) {
    const { t } = useI18n();
    const { settings = {} } = usePage<PageProps & { settings?: { logo_url?: string | null; inverse_logo_url?: string | null } & PublicThemeSettings }>().props;

    return <>
        <Head title={title} />
        <div className="public-cosmic relative min-h-screen overflow-hidden bg-[var(--public-background)] text-[var(--public-text)]" style={publicThemeStyle(settings)}>
            <a href="#public-content" className="sr-only z-[100] rounded-md bg-background px-4 py-2 text-sm font-semibold text-foreground focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:outline-none focus:ring-2 focus:ring-ring">
                {t('Skip to content')}
            </a>
            <PublicHeader appName={appName} settings={settings} current={current} />
            <div id="public-content" tabIndex={-1}>
                {children}
            </div>
            <PublicFooter appName={appName} settings={settings} />
        </div>
    </>;
}
