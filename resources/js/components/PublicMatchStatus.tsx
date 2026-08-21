import { useI18n } from '@/lib/i18n';

type Props = {
    status: string;
    compact?: boolean;
};

const statusStyles: Record<string, { label: string; className: string; dot: string }> = {
    scheduled: { label: 'Scheduled', className: 'border-slate-200 bg-slate-100 text-slate-700', dot: 'bg-slate-400' },
    in_progress: { label: 'Live', className: 'border-red-200 bg-red-50 text-red-700', dot: 'bg-red-500 animate-pulse' },
    completed: { label: 'Completed', className: 'border-emerald-200 bg-emerald-50 text-emerald-700', dot: 'bg-emerald-500' },
    cancelled: { label: 'Cancelled', className: 'border-slate-200 bg-slate-100 text-slate-500', dot: 'bg-slate-400' },
    postponed: { label: 'Postponed', className: 'border-amber-200 bg-amber-50 text-amber-800', dot: 'bg-amber-500' },
};

export default function PublicMatchStatus({ status, compact = false }: Props) {
    const { t } = useI18n();
    const config = statusStyles[status] ?? statusStyles.scheduled;
    const label = t(config.label);

    return (
        <span
            className={`inline-flex min-h-7 items-center gap-1.5 rounded-lg border px-2.5 py-1 text-[10px] font-black uppercase tracking-wider ${config.className} ${compact ? 'min-h-6 px-2 text-[9px]' : ''}`}
            aria-label={label}
        >
            <span className={`size-1.5 rounded-full ${config.dot}`} aria-hidden="true" />
            {label}
        </span>
    );
}
