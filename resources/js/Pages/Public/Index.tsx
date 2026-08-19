import ParticipantLogo from '@/components/ParticipantLogo';
import PublicEmptyState from '@/components/PublicEmptyState';
import PublicLayout from '@/Layouts/PublicLayout';
import PublicMatchCard, { type PublicMatch } from '@/components/PublicMatchCard';
import PublicSectionHeading from '@/components/PublicSectionHeading';
import PublicTeamRow from '@/components/PublicTeamRow';
import { useI18n } from '@/lib/i18n';
import { Head, Link, router } from '@inertiajs/react';
import { Activity, ArrowRight, CalendarDays, CheckCircle2, Clock3, Medal, RefreshCw, Trophy, Users } from 'lucide-react';
import { type ComponentType, useEffect, useState } from 'react';

type MedalRow = { rank: number; participant_name: string; logo_url?: string | null; inverse_logo_url?: string | null; gold: number; silver: number; bronze: number; total_medals: number };
type Props = {
    app_name: string;
    competition: { name: string; description: string | null; start_date: string | null; end_date: string | null; organization: string | null } | null;
    stats: { sports: number; events: number; faculties: number; completed_matches: number; total_matches: number };
    sports: string[];
    upcoming: PublicMatch[];
    results: PublicMatch[];
    medals: MedalRow[];
    updated_at: string;
};
type Translate = (key: string) => string;

const formatDate = (value: string | null, locale: string, includeTime = false) => {
    if (!value) return null;
    return new Intl.DateTimeFormat(locale === 'ms' ? 'ms-MY' : 'en-MY', {
        day: 'numeric', month: 'short', year: 'numeric',
        ...(includeTime ? { hour: '2-digit', minute: '2-digit' } : {}),
    }).format(new Date(value));
};

export default function PublicIndex({ app_name, competition, stats, upcoming, results, medals, updated_at }: Props) {
    const { t, locale } = useI18n();
    const [refreshing, setRefreshing] = useState(false);
    const [refreshStatus, setRefreshStatus] = useState<'idle' | 'success' | 'error'>('idle');
    const progress = stats.total_matches ? Math.round((stats.completed_matches / stats.total_matches) * 100) : 0;
    const refresh = (announceSuccess = false) => {
        setRefreshing(true);
        setRefreshStatus('idle');
        router.reload({
            only: ['stats', 'upcoming', 'results', 'medals', 'updated_at', 'weather'],
            preserveScroll: true,
            onSuccess: () => setRefreshStatus(announceSuccess ? 'success' : 'idle'),
            onError: () => setRefreshStatus('error'),
            onFinish: () => setRefreshing(false),
        });
    };

    useEffect(() => {
        const timer = window.setInterval(() => document.visibilityState === 'visible' && refresh(), 30000);
        return () => window.clearInterval(timer);
    }, []);

    const cards = [
        [stats.sports, t('Sports'), t('Sports programme'), Trophy],
        [stats.events, t('Events'), t('Official events'), Medal],
        [stats.faculties, t('Faculties'), t('Participating faculties'), Users],
        [stats.total_matches, t('Matches'), t('Fixtures scheduled'), CalendarDays],
    ] as const;

    return (
        <PublicLayout title={competition?.name || app_name} appName={app_name} current="home">
            <Head><link rel="canonical" href={route('public.index')} /></Head>
            <main>
                <section className="relative isolate overflow-hidden bg-[var(--public-dark)] pb-16 pt-16 text-white sm:pb-20 sm:pt-20">
                    <CosmicBackground />
                    <div className="mx-auto grid min-h-[520px] max-w-7xl items-center gap-12 px-4 sm:px-6 lg:grid-cols-[1.08fr_.92fr] xl:gap-20">
                        <div className="relative z-10 max-w-3xl">
                            <p className="flex items-center gap-2 text-[10px] font-black uppercase tracking-[.24em] text-[var(--public-accent)] sm:text-xs">
                                <span className="h-px w-8 bg-[var(--public-accent)]" />
                                {competition?.organization || t('Universiti Teknikal Malaysia Melaka')}
                            </p>
                            <h1 className="mt-6 text-4xl font-black leading-[.98] tracking-[-.05em] sm:text-6xl xl:text-7xl">{competition?.name || app_name}</h1>
                            <p className="mt-6 max-w-xl text-base leading-7 text-white/65 sm:text-lg">{competition?.description || t('Follow schedules, latest results and medal standings in one official view.')}</p>
                            <div className="mt-8 flex flex-wrap items-center gap-3">
                                <Link href="#schedule" className="public-cosmic-bezel inline-flex min-h-11 items-center gap-2 rounded-xl bg-[var(--public-highlight)] px-5 text-sm font-black text-[var(--public-dark)] transition hover:-translate-y-0.5 hover:brightness-105">{t('View schedule')}<ArrowRight className="size-4" /></Link>
                                <Link href="#results" className="inline-flex min-h-11 items-center rounded-xl border border-white/15 bg-white/5 px-5 text-sm font-bold text-white transition hover:bg-white/10">{t('View results')}</Link>
                            </div>
                            {competition?.start_date && <div className="mt-8 inline-flex items-center gap-3 rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white/75"><span className="flex size-8 items-center justify-center rounded-lg bg-white/10 text-[var(--public-highlight)]"><CalendarDays className="size-4" /></span><span><small className="block text-[9px] font-black uppercase tracking-[.16em] text-white/75">{t('Competition dates')}</small>{formatDate(competition.start_date, locale)}{competition.end_date && ` — ${formatDate(competition.end_date, locale)}`}</span></div>}
                        </div>

                        <aside className="relative mx-auto w-full max-w-lg rounded-[1.75rem] border border-white/15 bg-white/[.07] p-3 shadow-2xl backdrop-blur-md">
                            <div className="rounded-[1.35rem] border border-white/10 bg-black/15 p-5 sm:p-7">
                                <div className="flex items-start justify-between gap-5">
                                    <div><p className="text-[10px] font-black uppercase tracking-[.2em] text-[var(--public-accent)]">{t('Competition progress')}</p><p className="public-display mt-3 text-6xl font-extrabold tracking-[-.04em] sm:text-7xl">{progress}%</p></div>
                                    <span className="flex size-11 items-center justify-center rounded-2xl bg-[var(--public-highlight)] text-[var(--public-dark)]"><Activity className="size-5" /></span>
                                </div>
                                <div className="mt-5 h-2 overflow-hidden rounded-full bg-white/10" role="progressbar" aria-label={t('Competition progress')} aria-valuenow={progress} aria-valuemin={0} aria-valuemax={100}><div className="h-full rounded-full bg-gradient-to-r from-[var(--public-primary)] via-[var(--public-accent)] to-[var(--public-highlight)]" style={{ width: `${progress}%` }} /></div>
                                <div className="mt-6 grid grid-cols-2 gap-3"><ProgressMetric value={stats.completed_matches} label={t('Fixtures completed')} icon={CheckCircle2} /><ProgressMetric value={stats.total_matches} label={t('Total Matches')} icon={Trophy} /></div>

                                <div className="mt-5 border-t border-white/10 pt-5">
                                    <div className="flex items-center justify-between gap-3"><p className="text-[10px] font-black uppercase tracking-[.18em] text-white/75">{t('Next fixture')}</p><Clock3 className="size-4 text-[var(--public-accent)]" /></div>
                                    {upcoming[0] ? <div className="mt-4"><p className="truncate text-xs font-bold text-[var(--public-accent)]">{upcoming[0].sport} · {upcoming[0].event}</p><div className="mt-3 grid grid-cols-[1fr_auto_1fr] items-center gap-3"><PublicTeamRow team={upcoming[0].home} surface="dark" size="lg" /><span className="rounded-lg bg-white/10 px-2.5 py-1.5 text-[10px] font-black">VS</span><PublicTeamRow team={upcoming[0].away} surface="dark" size="lg" right /></div><p className="mt-3 flex items-center gap-1.5 text-xs text-white/75"><CalendarDays className="size-3.5" />{upcoming[0].scheduled_at ? formatDate(upcoming[0].scheduled_at, locale, true) : t('To be determined')}</p></div> : <p className="mt-3 text-sm text-white/75">{t('Schedule will be shown after publication.')}</p>}
                                </div>
                            </div>
                        </aside>
                    </div>
                </section>

                <section id="overview" className="relative mx-auto max-w-7xl scroll-mt-24 px-4 py-20 sm:px-6 sm:py-24">
                    <PublicSectionHeading eyebrow={t('At a glance')} title={t('Competition overview')} description={t('Everything you need for SAF 2026')} />
                    <div className="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">{cards.map(([value, label, description, Icon], index) => <article key={label} className={`group rounded-[1.6rem] border border-[var(--public-dark-border)] bg-white p-6 shadow-[0_24px_70px_-48px_rgba(7,27,51,.9)] transition duration-300 hover:-translate-y-1 hover:border-[var(--public-primary-border)] ${index % 2 ? 'lg:translate-y-6' : ''}`}><span className="flex size-11 items-center justify-center rounded-2xl bg-[var(--public-primary-soft)] text-[var(--public-primary)] transition group-hover:bg-[var(--public-primary)] group-hover:text-white"><Icon className="size-5" /></span><b className="mt-9 block text-5xl font-black tracking-[-.06em]">{value}</b><h3 className="mt-5 text-xs font-black uppercase tracking-[.16em]">{label}</h3><p className="mt-1 text-sm text-[var(--public-dark-faint)]">{description}</p></article>)}</div>
                </section>

                <section className="border-y border-[var(--public-dark-border)] bg-white py-20 sm:py-24"><div className="mx-auto max-w-7xl px-4 sm:px-6">
                    <div className="flex flex-col justify-between gap-5 sm:flex-row sm:items-end"><PublicSectionHeading eyebrow={t('Latest')} title={t('Live competition updates')} description={`${t('Updated')} ${formatDate(updated_at, locale, true)}`} /><div className="flex flex-col items-start gap-2 sm:items-end"><button type="button" onClick={() => refresh(true)} disabled={refreshing} className="inline-flex min-h-11 items-center justify-center gap-2 self-start rounded-xl border border-[var(--public-dark-border)] bg-white px-4 text-sm font-black transition hover:border-[var(--public-primary-border)] hover:text-[var(--public-primary)] disabled:opacity-50 sm:self-auto"><RefreshCw className={`size-4 ${refreshing ? 'animate-spin' : ''}`} />{t(refreshing ? 'Refreshing' : 'Refresh')}</button><p role="status" aria-live="polite" className={`min-h-5 text-xs font-semibold ${refreshStatus === 'error' ? 'text-red-700' : 'text-[var(--public-primary)]'}`}>{refreshStatus === 'success' ? t('Refresh complete') : refreshStatus === 'error' ? t('Unable to refresh. Please try again.') : ''}</p></div></div>
                    <div className="mt-12 grid gap-14 lg:grid-cols-2"><MatchColumn id="schedule" title={t('Schedule')} matches={upcoming.slice(0, 5)} variant="upcoming" t={t} /><MatchColumn id="results" title={t('Results')} matches={results.slice(0, 5)} variant="result" t={t} /></div>
                </div></section>

                <section id="medals" className="relative scroll-mt-24 overflow-hidden bg-[var(--public-dark-soft)] py-20 sm:py-24"><div className="absolute -right-32 -top-32 size-96 rounded-full bg-[var(--public-highlight-soft)] blur-3xl" /><div className="relative mx-auto max-w-7xl px-4 sm:px-6"><PublicSectionHeading eyebrow={t('Standings')} title={t('Medal standings')} description={t('Official standings based on confirmed results.')} /><div className="mt-12 grid gap-4 lg:grid-cols-3">{medals.slice(0, 6).map(row => <MedalCard key={row.participant_name} row={row} t={t} />)}</div>{medals.length === 0 && <div className="mt-12"><PublicEmptyState text={t('Medal standings are not available yet.')} /></div>}</div></section>
            </main>
        </PublicLayout>
    );
}

function CosmicBackground() {
    return <div aria-hidden="true" className="absolute inset-0 -z-10 overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-[var(--public-dark)] via-[var(--public-primary)] to-[var(--public-dark)]" />
        <div className="absolute -right-24 -top-32 size-[34rem] rounded-full bg-[var(--public-accent)] opacity-20 blur-3xl" />
        <div className="absolute -bottom-48 -left-32 size-[30rem] rounded-full bg-[var(--public-primary)] opacity-30 blur-3xl" />
        <div className="public-cosmic-orbit absolute right-[8%] top-[14%] size-[32rem] rounded-full border border-white/10" />
        <div className="public-cosmic-orbit public-cosmic-orbit-delayed absolute right-[14%] top-[22%] size-[22rem] rounded-full border border-white/10" />
        <div className="public-cosmic-grid absolute inset-0 opacity-20" />
    </div>;
}
function ProgressMetric({ value, label, icon: Icon }: { value: number; label: string; icon: ComponentType<{ className?: string }> }) { return <div className="rounded-2xl border border-white/10 bg-white/5 p-4"><Icon className="size-4 text-[var(--public-highlight)]" /><b className="public-display mt-4 block text-3xl font-extrabold">{value}</b><span className="text-xs text-white/75">{label}</span></div>; }
function MatchColumn({ id, title, matches, variant, t }: { id: string; title: string; matches: PublicMatch[]; variant: 'upcoming' | 'result'; t: Translate }) { return <div id={id} className="scroll-mt-24"><div className="mb-5 flex items-center gap-3"><span className="flex size-10 items-center justify-center rounded-xl bg-[var(--public-primary-soft)] text-[var(--public-primary)]">{variant === 'result' ? <CheckCircle2 className="size-5" /> : <Clock3 className="size-5" />}</span><h3 className="text-xl font-black">{title}</h3></div><div className="grid gap-3">{matches.map(match => <PublicMatchCard key={match.id} match={match} variant={variant} />)}{matches.length === 0 && <PublicEmptyState text={variant === 'result' ? t('No official results recorded yet.') : t('Schedule will be shown after publication.')} />}</div></div>; }
function MedalCard({ row, t }: { row: MedalRow; t: Translate }) { return <article className="flex items-center gap-4 rounded-2xl border border-[var(--public-dark-border)] bg-white p-5"><span className="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-[var(--public-dark)] text-lg font-black text-[var(--public-highlight)]">{row.rank}</span><ParticipantLogo participant={{ name: row.participant_name, logo_url: row.logo_url, inverse_logo_url: row.inverse_logo_url }} size="xl" alt="" /><div className="min-w-0 flex-1"><h3 className="truncate font-black">{row.participant_name}</h3><p className="mt-1 text-xs text-[var(--public-dark-faint)]">{row.gold} {t('Gold')} · {row.silver} {t('Silver')} · {row.bronze} {t('Bronze')}</p></div><b className="text-2xl">{row.total_medals}</b></article>; }