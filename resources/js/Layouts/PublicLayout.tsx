import PublicFooter from '@/components/PublicFooter';
import PublicHeader, { type PublicHeaderCurrent } from '@/components/PublicHeader';
import { publicThemeStyle, type PublicThemeSettings } from '@/lib/publicTheme';
import { usePageLoading } from '@/hooks/usePageLoading';
import { useI18n } from '@/lib/i18n';
import { type PageProps } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { type ReactNode } from 'react';

type Props = {
    children: ReactNode;
    title: string;
    appName: string;
    current?: PublicHeaderCurrent;
    description?: string;
    canonical?: string;
    image?: string | null;
};

export default function PublicLayout({ children, title, appName, current, description, canonical, image = '/images/banner/banner-saf-20-2026.jpeg' }: Props) {
    const { t, locale } = useI18n();
    const loading = usePageLoading();
    const { settings = {} } = usePage<PageProps & { settings?: { logo_url?: string | null; inverse_logo_url?: string | null } & PublicThemeSettings }>().props;
    const metaDescription = (description || title).trim().replace(/\s+/g, ' ').slice(0, 160);
    const socialImage = image && (typeof window !== 'undefined' ? new URL(image, window.location.origin).toString() : image);
    const socialLocale = locale === 'ms' ? 'ms_MY' : 'en_US';

    return <>
        <Head title={title}>
            <meta head-key="description" name="description" content={metaDescription} />
            {canonical && <link head-key="canonical" rel="canonical" href={canonical} />}
            <meta head-key="robots" name="robots" content="index, follow" />
            <meta head-key="og:type" property="og:type" content="website" />
            <meta head-key="og:title" property="og:title" content={title} />
            <meta head-key="og:description" property="og:description" content={metaDescription} />
            {canonical && <meta head-key="og:url" property="og:url" content={canonical} />}
            <meta head-key="og:site_name" property="og:site_name" content={appName} />
            <meta head-key="og:locale" property="og:locale" content={socialLocale} />
            {socialImage && <meta head-key="og:image" property="og:image" content={socialImage} />}
            {socialImage && <meta head-key="og:image:alt" property="og:image:alt" content={title} />}
            <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
            <meta head-key="twitter:title" name="twitter:title" content={title} />
            <meta head-key="twitter:description" name="twitter:description" content={metaDescription} />
            {socialImage && <meta head-key="twitter:image" name="twitter:image" content={socialImage} />}
        </Head>
        <div className="public-cosmic relative min-h-screen overflow-hidden bg-[var(--public-background)] text-[var(--public-text)]" style={publicThemeStyle(settings)}>
            {loading && <div role="status" aria-live="polite" className="sr-only">{t('Loading')}</div>}
            <a href="#public-content" className="sr-only z-[100] rounded-md bg-background px-4 py-2 text-sm font-semibold text-foreground focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:outline-none focus:ring-2 focus:ring-ring">
                {t('Skip to content')}
            </a>
            <PublicHeader appName={appName} settings={settings} current={current} />
            <div id="public-content" tabIndex={-1} aria-busy={loading}>
                {children}
            </div>
            <PublicFooter appName={appName} settings={settings} />
        </div>
    </>;
}
