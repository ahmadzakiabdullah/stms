import { Clock3 } from 'lucide-react';
import { useI18n } from '@/lib/i18n';

export default function PublicStaleDataNotice({ updatedAt }: { updatedAt?: string | null }) {
    const { locale, t } = useI18n();
    if (!updatedAt) return null;
    const date = new Date(updatedAt);
    if (Number.isNaN(date.getTime())) return null;
    const formatted = new Intl.DateTimeFormat(locale === 'ms' ? 'ms-MY' : 'en-MY', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'Asia/Kuala_Lumpur' }).format(date);
    return <p role="status" className="flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-900"><Clock3 className="size-4 shrink-0" aria-hidden="true" />{t('Data last updated')} {formatted}</p>;
}
