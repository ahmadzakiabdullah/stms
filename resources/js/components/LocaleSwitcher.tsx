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

        router.post(route('locale.update'), { locale: value }, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
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
                            className={`px-2 py-1 text-xs font-semibold transition ${isActive ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground hover:bg-muted'}`}
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
                className={`rounded-md border border-input bg-background px-2 py-1 ${compact ? 'h-8 text-xs' : 'text-sm'}`}
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
