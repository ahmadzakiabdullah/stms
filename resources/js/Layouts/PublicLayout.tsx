import PublicFooter from '@/components/PublicFooter';
import PublicHeader, { type PublicHeaderCurrent } from '@/components/PublicHeader';
import { publicThemeStyle, type PublicThemeSettings } from '@/lib/publicTheme';
import { type PageProps } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { type ReactNode } from 'react';

type Props = { children: ReactNode; title: string; appName: string; current?: PublicHeaderCurrent };

export default function PublicLayout({ children, title, appName, current }: Props) {
    const { settings = {} } = usePage<PageProps & { settings?: { logo_url?: string | null } & PublicThemeSettings }>().props;

    return <>
        <Head title={title} />
        <div className="public-cosmic relative min-h-screen overflow-hidden bg-[var(--public-background)] text-[var(--public-text)]" style={publicThemeStyle(settings)}>
            <PublicHeader appName={appName} settings={settings} current={current} />
            {children}
            <PublicFooter appName={appName} settings={settings} />
        </div>
    </>;
}