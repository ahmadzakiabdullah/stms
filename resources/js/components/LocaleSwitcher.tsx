import { router, usePage } from '@inertiajs/react';
import { type PageProps } from '@/types';
import { useI18n } from '@/lib/i18n';

interface LocaleSwitcherProps {
    compact?: boolean;
    showLabel?: boolean;
}

export default function LocaleSwitcher({ compact = false, showLabel = true }: LocaleSwitcherProps) {
    const { t } = useI18n();
    const { locale = 'en', locales = [] } = usePage<PageProps>().props;

    const handleChange = (value: string) => {
        if (!value || value === locale) {
            return;
        }

        // Update the document language immediately so assistive technology and
        // browser automation do not observe a stale shell during the Inertia
        // redirect/reload transition.
        document.documentElement.lang = value;

        router.post(route('locale.update'), { locale: value }, {
            preserveScroll: true,
            // Locale is page-wide. Remount so every component reads the fresh
            // locale prop instead of retaining state from the old language.
            preserveState: false,
            replace: true,
            onSuccess: () => {
                // Ensure the HTML lang attribute and all page props are refreshed.
                window.location.reload();
            },
        });
    };

    // Header compact mode: render a visible EN/BM segmented control instead of a tiny select.
    if (compact && !showLabel) {
        return (
            <div className="inline-flex items-center overflow-hidden rounded-md border border-input" role="group" aria-label={t('Language')}>
                {locales.map((item) => {
                    const isActive = item.code === locale;

                    return (
                        <button
                            key={item.code}
                            type="button"
                            onClick={() => handleChange(item.code)}
                            className={`min-h-11 px-2 py-1 text-xs font-semibold transition sm:min-h-0 ${isActive ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground hover:bg-muted'}`}
                            aria-pressed={isActive}
                        >
                            {item.code.toUpperCase()}
                        </button>
                    );
                })}
            </div>
        );
    }

    return (
        <label className={`inline-flex items-center gap-2 ${compact ? 'text-xs' : 'text-sm'}`}>
            {showLabel && <span className="text-muted-foreground">{t('Language')}</span>}
            <select
                value={locale}
                onChange={(event) => handleChange(event.target.value)}
                className={`min-h-11 rounded-md border border-input bg-background px-2 py-1 sm:min-h-0 ${compact ? 'h-8 text-xs' : 'text-sm'}`}
                aria-label={t('Language')}
            >
                {locales.map((item) => (
                    <option key={item.code} value={item.code}>
                        {item.label}
                    </option>
                ))}
            </select>
        </label>
    );
}
