import ParticipantLogo from '@/components/ParticipantLogo';
import PublicEmptyState from '@/components/PublicEmptyState';
import PublicLayout from '@/Layouts/PublicLayout';
import PublicPageHero from '@/components/PublicPageHero';
import { useI18n } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, CalendarDays, CheckCircle2, Clock3, MapPin, Trophy, Users, XCircle } from 'lucide-react';

type Athlete = { id: string; name: string; role: string; faculty: string | null; logo_url: string | null; inverse_logo_url: string | null; sport: string | null; category: string | null; event: string | null };
type Match = { id: string; event: string | null; opponent: string | null; score_for: number | null; score_against: number | null; scheduled_at: string | null; venue: string | null; status: string; outcome: 'win' | 'draw' | 'loss' | null };
type Props = { app_name: string; competition: { name: string; organization: string | null } | null; athlete: Athlete; stats: { matches: number; wins: number; draws: number; losses: number }; matches: Match[]; updated_at?: string };

export default function PublicAthlete({ app_name, competition, athlete, stats, matches = [], updated_at }: Props) {
    const { t, locale } = useI18n();

    return (
        <PublicLayout title={`${athlete.name} | ${competition?.name || app_name}`} appName={app_name} current="athletes">
            <Head><link rel="canonical" href={route('public.athletes.show', athlete.id)} /></Head>
            <main>
                <PublicPageHero eyebrow={competition?.organization || t('Official competition')} title={t('Athlete Profile')} intro={t('Official participation and performance record.')} icon={<Users className="size-4" />} />
                <div className="mx-auto max-w-5xl px-4 py-10 sm:px-6 sm:py-14">
                    <Link href={route('public.athletes')} className="inline-flex min-h-10 items-center gap-2 text-sm font-black text-[var(--public-primary)] hover:underline"><ArrowLeft className="size-4" />{t('Back to athletes')}</Link>

                    <section className="mt-6 rounded-3xl border border-[var(--public-dark-border)] bg-white p-6 shadow-[0_24px_70px_-48px_rgba(7,27,51,.9)] sm:p-8">
                        <div className="flex flex-col gap-5 sm:flex-row sm:items-center">
                            <ParticipantLogo participant={athlete} size="xl" />
                            <div className="min-w-0 flex-1"><h1 className="text-2xl font-black tracking-[-.03em] text-[var(--public-text)] sm:text-3xl">{athlete.name}</h1><p className="mt-2 text-sm font-bold text-[var(--public-dark-faint)]">{athlete.faculty || t('Faculty')}</p><div className="mt-3 flex flex-wrap gap-2">{athlete.sport && <span className="rounded-full bg-[var(--public-primary-soft)] px-3 py-1 text-xs font-black text-[var(--public-primary)]">{athlete.sport}</span>}{athlete.category && <span className="rounded-full border border-[var(--public-dark-border)] px-3 py-1 text-xs font-black text-[var(--public-dark-faint)]">{athlete.category}</span>}</div></div>
                        </div>
                        <div className="mt-8 grid grid-cols-2 gap-3 border-t border-[var(--public-dark-border)] pt-6 sm:grid-cols-4"><PerformanceStat value={stats.matches} label={t('matches')} /><PerformanceStat value={stats.wins} label={t('Wins')} tone="text-emerald-600" /><PerformanceStat value={stats.draws} label={t('Draws')} /><PerformanceStat value={stats.losses} label={t('Losses')} tone="text-rose-600" /></div>
                    </section>

                    <section className="mt-10"><div className="flex items-end justify-between gap-4"><div><p className="text-xs font-black uppercase tracking-[.16em] text-[var(--public-primary)]">{t('Official record')}</p><h2 className="mt-2 text-xl font-black">{t('Team performance')}</h2><p className="mt-1 text-xs font-semibold text-[var(--public-dark-faint)]">{t('Results are based on the athlete’s registered faculty team.')}</p></div><Trophy className="size-6 text-[var(--public-primary)]" /></div>
                        {matches.length === 0 ? <div className="mt-5"><PublicEmptyState text={t('No official matches recorded yet.')} /></div> : <div className="mt-5 space-y-3">{matches.map(match => <MatchRow key={match.id} match={match} locale={locale} t={t} />)}</div>}
                        {updated_at && <p className="mt-5 text-right text-xs font-semibold text-[var(--public-dark-faint)]">{t('Updated')} {formatUpdatedAt(updated_at, locale)}</p>}
                    </section>
                </div>
            </main>
        </PublicLayout>
    );
}

function formatUpdatedAt(value: string, locale: string) {
    return new Intl.DateTimeFormat(locale === 'ms' ? 'ms-MY' : 'en-MY', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }).format(new Date(value));
}

function PerformanceStat({ value, label, tone = 'text-[var(--public-text)]' }: { value: number; label: string; tone?: string }) {
    return <div><strong className={`block text-2xl font-black tabular-nums ${tone}`}>{value}</strong><span className="mt-1 block text-[10px] font-black uppercase tracking-[.14em] text-[var(--public-dark-faint)]">{label}</span></div>;
}

function MatchRow({ match, locale, t }: { match: Match; locale: string; t: (key: string) => string }) {
    const outcome = match.outcome === 'win' ? { label: t('Win'), icon: CheckCircle2, color: 'text-emerald-600 bg-emerald-50' } : match.outcome === 'loss' ? { label: t('Loss'), icon: XCircle, color: 'text-rose-600 bg-rose-50' } : match.outcome === 'draw' ? { label: t('Draw'), icon: Trophy, color: 'text-amber-600 bg-amber-50' } : { label: t(match.status === 'in_progress' ? 'Live' : 'Scheduled'), icon: Clock3, color: 'text-slate-600 bg-slate-100' };
    const Icon = outcome.icon;
    return <article className="rounded-2xl border border-[var(--public-dark-border)] bg-white p-4 shadow-sm"><div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div className="min-w-0"><p className="text-xs font-bold text-[var(--public-dark-faint)]">{match.event}</p><h3 className="mt-1 text-sm font-black">{t('vs')} {match.opponent || t('To be confirmed')}</h3><div className="mt-2 flex flex-wrap gap-3 text-xs font-semibold text-[var(--public-dark-faint)]">{match.scheduled_at && <span className="inline-flex items-center gap-1"><CalendarDays className="size-3.5" />{new Intl.DateTimeFormat(locale === 'ms' ? 'ms-MY' : 'en-MY', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(match.scheduled_at))}</span>}{match.venue && <span className="inline-flex items-center gap-1"><MapPin className="size-3.5" />{match.venue}</span>}</div></div><div className="flex items-center gap-4 sm:shrink-0"><strong className="text-xl font-black tabular-nums">{match.score_for !== null && match.score_against !== null ? `${match.score_for} – ${match.score_against}` : '—'}</strong><span className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-black ${outcome.color}`}><Icon className="size-3.5" />{outcome.label}</span></div></div></article>;
}
