import PublicTeamRow from '@/components/PublicTeamRow';
import { useI18n } from '@/lib/i18n';
import { MapPin } from 'lucide-react';

export type PublicMatchTeam = { name: string; logo_url: string | null; inverse_logo_url: string | null } | null;

export type PublicMatch = {
    id: string;
    sport: string | null;
    event: string | null;
    stage: string | null;
    round: string | null;
    group: string | null;
    scheduled_at: string | null;
    venue: string | null;
    status: string;
    home: PublicMatchTeam;
    away: PublicMatchTeam;
    score_home: number | null;
    score_away: number | null;
};

export type PublicMatchVariant = 'upcoming' | 'result' | 'live';

const formatDate = (value: string | null, locale: string) => {
    if (!value) return null;
    return new Intl.DateTimeFormat(locale === 'ms' ? 'ms-MY' : 'en-MY', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

export default function PublicMatchCard({ match, variant = 'upcoming' }: { match: PublicMatch; variant?: PublicMatchVariant }) {
    const { t, locale } = useI18n();
    const isResult = variant === 'result';
    const isLive = variant === 'live';
    const score = isResult ? `${match.score_home ?? '—'} : ${match.score_away ?? '—'}` : 'VS';
    const meta = match.stage || match.round || match.group || '';

    return (
        <article className="rounded-2xl border border-[var(--public-dark-border)] bg-white p-4 shadow-sm transition hover:border-[var(--public-primary-border)] hover:shadow-md">
            <div className="flex flex-wrap items-center justify-between gap-2 text-[10px] font-black uppercase tracking-[.14em] text-[var(--public-primary)]">
                <span>{match.sport || t('Competition')}{match.event ? ` · ${match.event}` : ''}</span>
                <span className="normal-case tracking-normal text-[var(--public-dark-faint)]">
                    {isLive
                        ? <span className="inline-flex items-center gap-1.5 font-black uppercase text-red-600"><span className="size-1.5 animate-pulse rounded-full bg-current" />{t('Live')}</span>
                        : meta || formatDate(match.scheduled_at, locale) || t('To be determined')}
                </span>
            </div>
            <div className="mt-4 grid grid-cols-[1fr_auto_1fr] items-center gap-3">
                <PublicTeamRow team={match.home} />
                <div className="rounded-xl bg-[var(--public-dark)] px-3 py-2 text-center text-xs font-black text-white">{score}</div>
                <PublicTeamRow team={match.away} right />
            </div>
            {match.venue ? (
                <p className="mt-4 flex items-center gap-1.5 border-t border-[var(--public-dark-border)] pt-3 text-xs text-[var(--public-dark-faint)]">
                    <MapPin className="size-3.5 shrink-0" />{match.venue}
                </p>
            ) : null}
        </article>
    );
}