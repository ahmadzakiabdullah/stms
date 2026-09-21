import { AlertTriangle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/lib/i18n';

export default function PublicErrorState({ title = 'Unable to load this content', description = 'Please try again.', onRetry }: { title?: string; description?: string; onRetry?: () => void }) {
    const { t } = useI18n();

    return (
        <div role="alert" className="rounded-2xl border border-red-200 bg-red-50 p-8 text-center text-red-900">
            <AlertTriangle className="mx-auto size-7 text-red-700" aria-hidden="true" />
            <h2 className="mt-3 text-sm font-black">{t(title)}</h2>
            <p className="mt-1 text-sm text-red-800">{t(description)}</p>
            {onRetry && <Button type="button" variant="outline" onClick={onRetry} className="mt-4 border-red-300 bg-white text-red-800 hover:bg-red-100">{t('Try again')}</Button>}
        </div>
    );
}
