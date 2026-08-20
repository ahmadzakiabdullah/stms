import ParticipantLogo from '@/components/ParticipantLogo';
import { SportIcon } from '@/lib/sportIcons';
import { useI18n } from '@/lib/i18n';
import { Clock, MapPin } from 'lucide-react';

export type ScheduleMatchTeam = { name: string; logo_url: string | null; inverse_logo_url: string | null } | null;

export type ScheduleMatch = {
    id: string;
    sport: string | null;
    event: string | null;
    category: string | null;
    stage: string | null;
    round: number | null;
    group: string | null;
    match_number: number;
    scheduled_at: string | null;
    venue: string | null;
    status: 'scheduled' | 'in_progress' | 'completed' | 'cancelled';
    home: ScheduleMatchTeam;
    away: ScheduleMatchTeam;
    score_home: number | null;
    score_away: number | null;
};

type Props = {
    match: ScheduleMatch;
};

const formatDateTime = (value: string | null, locale: string) => {
    if (!value) return null;
    const date = new Date(value);
    return new Intl.DateTimeFormat(locale === 'ms' ? 'ms-MY' : 'en-MY', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
};

const formatTime = (value: string | null, locale: string) => {
    if (!value) return null;
    const date = new Date(value);
    return new Intl.DateTimeFormat(locale === 'ms' ? 'ms-MY' : 'en-MY', {
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
};

const formatDateShort = (value: string | null, locale: string) => {
    if (!value) return null;
    const date = new Date(value);
    return new Intl.DateTimeFormat(locale === 'ms' ? 'ms-MY' : 'en-MY', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(date);
};

const formatDayOfWeek = (value: string | null, locale: string) => {
    if (!value) return null;
    const date = new Date(value);
    return new Intl.DateTimeFormat(locale === 'ms' ? 'ms-MY' : 'en-MY', {
        weekday: 'short',
    }).format(date);
};

const stageConfig: Record<string, { label: string; color: string; bg: string }> = {
    final: { label: 'Final', color: 'text-amber-700', bg: 'bg-amber-50 border-amber-200' },
    bronze: { label: 'Bronze', color: 'text-orange-700', bg: 'bg-orange-50 border-orange-200' },
    semi_final: { label: 'Semi-Final', color: 'text-purple-700', bg: 'bg-purple-50 border-purple-200' },
    group: { label: 'Group', color: 'text-blue-700', bg: 'bg-blue-50 border-blue-200' },
};

const statusConfig: Record<string, { label: string; className: string; dot: string }> = {
    scheduled: { label: 'Scheduled', className: 'bg-slate-100 text-slate-700 border-slate-200', dot: 'bg-slate-400' },
    in_progress: { label: 'Live', className: 'bg-red-50 text-red-700 border-red-200', dot: 'bg-red-500 animate-pulse' },
    completed: { label: 'Completed', className: 'bg-emerald-50 text-emerald-700 border-emerald-200', dot: 'bg-emerald-500' },
    cancelled: { label: 'Cancelled', className: 'bg-gray-100 text-gray-500 border-gray-200', dot: 'bg-gray-400' },
};

export default function ScheduleMatchCard({ match }: Props) {
    const { t, locale } = useI18n();
    const isLive = match.status === 'in_progress';
    const isCompleted = match.status === 'completed';
    const isCancelled = match.status === 'cancelled';

    const stageInfo = match.stage ? stageConfig[match.stage] : null;
    const statusInfo = statusConfig[match.status] || statusConfig.scheduled;

    const displayScore = isCompleted
        ? `${match.score_home ?? '—'} : ${match.score_away ?? '—'}`
        : 'VS';

    const metaParts = [
        stageInfo?.label,
        match.round ? `${t('Round')} ${match.round}` : null,
        match.group,
    ].filter(Boolean);

    const metaText = metaParts.join(' · ') || t('Match') + ` #${match.match_number}`;

    const dateLabel = formatDateShort(match.scheduled_at, locale);
    const timeLabel = formatTime(match.scheduled_at, locale);
    const dayLabel = formatDayOfWeek(match.scheduled_at, locale);

    return (
        <article className={`group relative overflow-hidden rounded-2xl border transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg ${
            isLive
                ? 'border-red-200 bg-white shadow-md shadow-red-50'
                : isCompleted
                    ? 'border-slate-200 bg-white'
                    : 'border-slate-200 bg-white hover:border-[var(--public-primary-border)]'
        }`}>
            {isLive && (
                <div className="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-red-500 via-red-400 to-red-500" />
            )}

            <div className="p-5">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div className="flex items-center gap-2">
                        <span className="flex size-8 items-center justify-center rounded-xl bg-[var(--public-primary-soft)] text-[var(--public-primary)]">
                            <SportIcon name={match.sport || ''} className="text-sm leading-none" />
                        </span>
                        <div className="flex flex-col">
                            <span className="text-xs font-bold text-[var(--public-text)]">
                                {match.sport || t('Competition')}
                            </span>
                            {match.event && (
                                <span className="text-[11px] text-[var(--public-dark-faint)]">
                                    {match.event}
                                </span>
                            )}
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        {stageInfo && (
                            <span className={`inline-flex items-center rounded-lg border px-2 py-0.5 text-[10px] font-black uppercase tracking-wider ${stageInfo.color} ${stageInfo.bg}`}>
                                {stageInfo.label}
                            </span>
                        )}
                        <span className={`inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-[10px] font-black uppercase tracking-wider ${statusInfo.className}`}>
                            <span className={`size-1.5 rounded-full ${statusInfo.dot}`} />
                            {isLive ? t('Live') : isCompleted ? t('Completed') : isCancelled ? t('Cancelled') : t('Scheduled')}
                        </span>
                    </div>
                </div>

                <div className="mt-5 flex items-center justify-center gap-4 sm:gap-6">
                    <div className="flex min-w-0 flex-col items-center gap-2">
                        <ParticipantLogo
                            participant={match.home}
                            size="lg"
                            alt=""
                            className="size-12 shrink-0"
                        />
                        <span className="max-w-36 text-center text-sm font-bold leading-tight text-[var(--public-text)] line-clamp-2">
                            {match.home?.name || t('TBD')}
                        </span>
                    </div>

                    <div className={`flex shrink-0 flex-col items-center justify-center rounded-xl px-4 py-3 min-w-[5rem] ${
                        isLive
                            ? 'bg-red-600 text-white'
                            : isCompleted
                                ? 'bg-[var(--public-dark)] text-white'
                                : 'bg-slate-100 text-slate-700'
                    }`}>
                        <span className="text-lg font-black tracking-tight">
                            {displayScore}
                        </span>
                        {!isCompleted && !isCancelled && match.scheduled_at && (
                            <span className="mt-1 flex items-center gap-1 text-[10px] font-semibold opacity-70">
                                <Clock className="size-3" />
                                {timeLabel}
                            </span>
                        )}
                    </div>

                    <div className="flex min-w-0 flex-col items-center gap-2">
                        <ParticipantLogo
                            participant={match.away}
                            size="lg"
                            alt=""
                            className="size-12 shrink-0"
                        />
                        <span className="max-w-36 text-center text-sm font-bold leading-tight text-[var(--public-text)] line-clamp-2">
                            {match.away?.name || t('TBD')}
                        </span>
                    </div>
                </div>

                <div className="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
                    <div className="flex flex-wrap items-center gap-3">
                        {match.scheduled_at && !isCompleted && !isCancelled && (
                            <div className="flex items-center gap-1.5 text-xs text-[var(--public-dark-faint)]">
                                <Clock className="size-3.5 shrink-0" />
                                <span className="font-semibold">{dayLabel}</span>
                                <span>{dateLabel}</span>
                            </div>
                        )}
                        {match.venue && (
                            <div className="flex items-center gap-1.5 text-xs text-[var(--public-dark-faint)]">
                                <MapPin className="size-3.5 shrink-0" />
                                <span className="font-medium">{match.venue}</span>
                            </div>
                        )}
                    </div>
                    <span className="text-[10px] font-black uppercase tracking-wider text-[var(--public-dark-faint)]">
                        #{match.match_number}
                    </span>
                </div>
            </div>

            {isLive && (
                <div className="flex items-center justify-center gap-2 border-t border-red-100 bg-red-50/50 px-5 py-2.5">
                    <span className="relative flex size-2">
                        <span className="absolute inset-0 animate-ping rounded-full bg-red-400 opacity-75" />
                        <span className="relative size-2 rounded-full bg-red-500" />
                    </span>
                    <span className="text-xs font-black uppercase tracking-wider text-red-700">
                        {t('Match in progress')}
                    </span>
                </div>
            )}
        </article>
    );
}
