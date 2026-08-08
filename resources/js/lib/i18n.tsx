import { createContext, useContext, useMemo, type ReactNode } from 'react';
import { usePage } from '@inertiajs/react';
import type { PageProps } from '@/types';

export type I18nParams = Record<string, string | number>;

export interface I18nValue {
    locale: string;
    t: (key: string, params?: I18nParams) => string;
}

const I18nContext = createContext<I18nValue>({
    locale: 'en',
    t: (key) => key,
});

function interpolate(template: string, params?: I18nParams): string {
    if (!params) return template;

    return template.replace(/\{\{\s*([\w.]+)\s*\}\}/g, (match, name: string) =>
        Object.prototype.hasOwnProperty.call(params, name) ? String(params[name]) : match,
    );
}

/**
 * Lightweight server-driven i18n provider. Translation dictionaries are the
 * Laravel lang/*.json files, shared with the frontend via Inertia shared props
 * (see HandleInertiaRequests). When a key is missing the English key itself is
 * rendered, so translations degrade gracefully without blank screens.
 */
export function LanguageProvider({ children }: { children: ReactNode }) {
    const { locale = 'en', translations = {} } = usePage<PageProps>().props;

    const value = useMemo<I18nValue>(() => {
        const dictionary = translations as Record<string, unknown>;

        const t = (key: string, params?: I18nParams): string => {
            let candidate: unknown = dictionary[key];

            if (params && typeof params.count === 'number') {
                const pluralKey = `${key}_${params.count === 1 ? 'one' : 'other'}`;
                const plural = dictionary[pluralKey];
                if (typeof plural === 'string' && plural.length > 0) {
                    candidate = plural;
                }
            }

            const resolved = typeof candidate === 'string' && candidate.length > 0 ? candidate : key;

            return interpolate(resolved, params);
        };

        return { locale, t };
    }, [locale, translations]);

    return <I18nContext.Provider value={value}>{children}</I18nContext.Provider>;
}

export function useI18n(): I18nValue {
    return useContext(I18nContext);
}

export function useT(): I18nValue['t'] {
    return useContext(I18nContext).t;
}