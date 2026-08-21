import ParticipantLogo, { type ParticipantLogoSource } from '@/components/ParticipantLogo';
import PublicMatchStatus from '@/components/PublicMatchStatus';
import { useI18n } from '@/lib/i18n';
import { CalendarDays, MapPin } from 'lucide-react';

export type PublicFixtureCardMatch = {
    id: string;
    sport: string | null;
    event: string | null;
    category?: string | null;
    stage?: string | null;
    round?: number | string | null;
    group?: string | null;
    match_number?: number | null;
    scheduled_at: string | null;
    venue: string | null;
    status: string;
    home: (ParticipantLogoSource & { id?: string }) | null;
    away: (ParticipantLogoSource & { id?: string }) | null;
    score_home: number | null;
    score_away: number | null;
    scoring_events?: Array<{ participant_id: string; name: string | null; minute: number | null; second: number | null; event_type: string }>;
};

type Props = { match: PublicFixtureCardMatch; mode?: 'upcoming' | 'result' | 'live' };

const dateParts = (value: string | null, locale: string) => {
    if (!value) return null;
    const date = new Date(value);
    const language = locale === 'ms' ? 'ms-MY' : 'en-MY';
    return {
        date: new Intl.DateTimeFormat(language, { weekday: 'long', day: 'numeric', month: 'short', year: 'numeric' }).format(date),
        time: new Intl.DateTimeFormat(language, { hour: '2-digit', minute: '2-digit' }).format(date),
    };
};

export default function PublicFixtureCard({ match, mode }: Props) {
    const { t, locale } = useI18n();
    const isLive = mode === 'live' || match.status === 'in_progress';
    const isResult = mode === 'result' || match.status === 'completed';
    const parts = dateParts(match.scheduled_at, locale);
    const category = match.category || match.group || match.stage;
    const score = `${match.score_home ?? '—'} – ${match.score_away ?? '—'}`;
    const matchLabel = match.match_number ? `${t('Match')} ${match.match_number}` : t('Match');
    const secondary = match.group || (isResult ? t('Final') : t('To be determined'));
    const scorers = match.scoring_events || [];
    const scorerLabel = (event: (typeof scorers)[number]) => `${event.name || t('Unknown')}${event.minute != null ? ` ${event.minute}'` : ''}`;
    const homeScorers = scorers.filter((event) => event.participant_id === match.home?.id);
    const awayScorers = scorers.filter((event) => event.participant_id === match.away?.id);

    const scorerColumn = (teamName: string, events: typeof scorers, align: 'left' | 'right') => (
        <div className={align === 'right' ? 'text-right' : 'text-left'}>
            <p className="text-[10px] font-black uppercase tracking-wide text-[var(--public-dark-faint)]">{teamName}</p>
            {events.length > 0 ? <div className={`mt-1 grid gap-1 text-xs font-semibold text-[var(--public-text)] ${align === 'right' ? 'justify-items-end' : 'justify-items-start'}`}>{events.map((event, index) => <span key={`${event.participant_id}-${index}`}>{scorerLabel(event)}</span>)}</div> : <p className="mt-1 text-xs text-[var(--public-dark-faint)]">—</p>}
        </div>
    );

    return (
        <article id={`match-${match.id}`} className="rounded-2xl border border-[var(--public-dark-border)] bg-white p-4 shadow-sm transition hover:border-[var(--public-primary-border)] hover:shadow-md sm:p-7">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div className="min-w-0">
                    <h3 className="text-base font-black leading-tight text-[var(--public-text)] sm:text-lg">{match.event || match.sport || t('Competition')}</h3>
                    <div className="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs font-semibold text-[var(--public-dark-faint)]">
                        <span>{matchLabel}</span>
                        {parts && <><span aria-hidden="true">|</span><span className="inline-flex items-center gap-1"><CalendarDays className="size-3.5" />{parts.date}</span></>}
                        {category && <span className="rounded-md bg-red-50 px-2 py-1 text-[10px] font-black uppercase tracking-wide text-red-700">{category}</span>}
                    </div>
                </div>
                {match.venue && <div className="inline-flex items-center gap-1.5 text-xs font-black uppercase tracking-wide text-red-600 sm:max-w-[42%] sm:justify-end sm:text-right"><MapPin className="size-4 shrink-0" />{match.venue}</div>}
            </div>

            <div className="my-5 border-t border-[var(--public-dark-border)]" />

            <div className="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-3 sm:gap-6">
                <div className="flex min-w-0 items-center justify-end gap-3 text-right"><span className="line-clamp-2 text-sm font-black leading-tight text-[var(--public-text)] sm:text-base">{match.home?.name || t('TBD')}</span><ParticipantLogo participant={match.home} size="lg" alt="" /></div>
                <div className="flex flex-col items-center text-center">
                    <span className="text-xs font-bold text-[var(--public-dark-faint)]">{isResult ? t('Final') : isLive ? t('Live') : t('Your Time')}</span>
                    <span className={`mt-1 rounded-lg px-3 py-1 text-xl font-black tracking-tight ${isLive ? 'bg-red-600 text-white' : isResult ? 'bg-[var(--public-dark)] text-white' : 'bg-slate-100 text-[var(--public-text)]'}`}>{isResult || isLive ? score : parts?.time || '—'}</span>
                    <span className="mt-1 text-xs font-semibold text-[var(--public-dark-faint)]">{secondary}</span>
                </div>
                <div className="flex min-w-0 items-center gap-3"><ParticipantLogo participant={match.away} size="lg" alt="" /><span className="line-clamp-2 text-sm font-black leading-tight text-[var(--public-text)] sm:text-base">{match.away?.name || t('TBD')}</span></div>
            </div>

            {isResult && scorers.length > 0 && <div className="mt-4 border-t border-[var(--public-dark-border)] pt-3"><p className="text-[10px] font-black uppercase tracking-wide text-[var(--public-dark-faint)]">{t('Scorers')}</p><div className="mt-2 grid gap-3 sm:grid-cols-2">{scorerColumn(match.home?.name || t('Home'), homeScorers, 'left')}{scorerColumn(match.away?.name || t('Away'), awayScorers, 'right')}</div></div>}
            <div className="mt-4 flex items-center justify-between gap-3 border-t border-[var(--public-dark-border)] pt-3"><PublicMatchStatus status={isLive ? 'in_progress' : isResult ? 'completed' : match.status} compact /><span className="text-[10px] font-black uppercase tracking-wide text-[var(--public-dark-faint)]">{match.sport || t('Competition')}</span></div>
        </article>
    );
}
