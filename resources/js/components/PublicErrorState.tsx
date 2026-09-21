import { AlertTriangle } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { useI18n } from '@/lib/i18n';

export default function PublicErrorState({ title = 'Unable to load this content', description = 'Please try again.', onRetry }: { title?: string; description?: string; onRetry?: () => void }) {
    const { t } = useI18n();

    return (
        <Alert variant="destructive" className="p-8 text-center">
            <AlertTriangle className="mx-auto size-7 text-red-700" aria-hidden="true" />
            <AlertTitle className="mt-3 text-sm">{t(title)}</AlertTitle>
            <AlertDescription className="mt-1 text-red-800">{t(description)}</AlertDescription>
            {onRetry && <Button type="button" variant="outline" onClick={onRetry} className="mt-4 border-red-300 bg-white text-red-800 hover:bg-red-100">{t('Try again')}</Button>}
        </Alert>
    );
}
