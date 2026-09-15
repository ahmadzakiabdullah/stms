import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createElement, type ComponentType, type ReactNode } from 'react';
import { createRoot } from 'react-dom/client';

import { LanguageProvider } from '@/lib/i18n';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.tsx`,
            import.meta.glob('./Pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <App {...props}>
                {({ Component, props: pageProps, key }) => {
                    // The Inertia page context is established by <App>. Keep
                    // providers that read usePage() inside that context.
                    const page = (
                        <LanguageProvider>
                            <Component {...pageProps} key={key} />
                        </LanguageProvider>
                    );

                    const layout = (Component as typeof Component & {
                        layout?:
                            | ((page: ReactNode) => ReactNode)
                            | ComponentType<any>[];
                    }).layout;

                    if (typeof layout === 'function') {
                        return layout(page);
                    }

                    if (Array.isArray(layout)) {
                        return layout
                            .concat(page)
                            .reverse()
                            .reduce(
                                (children, Layout) =>
                                    createElement(Layout, {
                                        children,
                                        ...pageProps,
                                    }),
                            );
                    }

                    return page;
                }}
            </App>,
        );
    },
    progress: {
        color: '#4B5563',
    },
});
