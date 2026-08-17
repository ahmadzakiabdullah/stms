import LocaleSwitcher from '@/components/LocaleSwitcher';
import PublicLoginButton from '@/components/PublicLoginButton';
import PublicMobileMenu from '@/components/PublicMobileMenu';
import PublicDesktopNav from '@/components/PublicDesktopNav';
import PublicAnnouncementBar from '@/components/PublicAnnouncementBar';
import ParticipantLogo from '@/components/ParticipantLogo';
import { useI18n } from '@/lib/i18n';
import { publicThemeStyle, type PublicThemeSettings } from '@/lib/publicTheme';
import { type PageProps } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Activity, ArrowRight, CalendarDays, CheckCircle2, Clock3, MapPin, Medal, RefreshCw, Trophy, Users } from 'lucide-react';
import { type ComponentType, useEffect, useState } from 'react';

type Team = { name: string; logo_url: string | null; inverse_logo_url: string | null } | null;
type Match = { id: string; sport: string | null; event: string | null; stage: string | null; round: string | null; group: string | null; scheduled_at: string | null; venue: string | null; status: string; home: Team; away: Team; score_home: number | null; score_away: number | null };
type MedalRow = { rank: number; participant_name: string; logo_url?: string | null; inverse_logo_url?: string | null; gold: number; silver: number; bronze: number; total_medals: number };
type Props = {
    app_name: string;
    competition: { name: string; description: string | null; start_date: string | null; end_date: string | null; organization: string | null } | null;
    stats: { sports: number; events: number; faculties: number; completed_matches: number; total_matches: number };
    sports: string[];
    upcoming: Match[];
    results: Match[];
    medals: MedalRow[];
    updated_at: string;
};
type SharedProps = { settings?: { logo_url?: string | null } & PublicThemeSettings };
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
    const { settings = {} } = usePage<PageProps & SharedProps>().props;
    const [refreshing, setRefreshing] = useState(false);
    const [refreshStatus, setRefreshStatus] = useState<'idle' | 'success' | 'error'>('idle');
    const progress = stats.total_matches ? Math.round((stats.completed_matches / stats.total_matches) * 100) : 0;
    const refresh = (announceSuccess = false) => {
        setRefreshing(true);
        setRefreshStatus('idle');
        router.reload({
            only: ['stats', 'upcoming', 'results', 'medals', 'updated_at'],
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

    return <>
        <Head title={competition?.name || app_name}><link rel="canonical" href={route('public.index')} /></Head>
        <div className="public-cosmic min-h-screen overflow-hidden bg-[var(--public-background)] text-[var(--public-text)]" style={publicThemeStyle(settings)}>
            <PublicAnnouncementBar />
            <header className="absolute left-0 right-0 top-10 z-50 px-3 pt-3 sm:px-6 sm:pt-5">
                <div className="mx-auto flex min-h-[68px] max-w-7xl items-center justify-between gap-4 rounded-2xl border border-white/15 bg-[color:var(--public-dark)] px-4 py-2.5 text-white shadow-2xl shadow-black/10 sm:px-5 xl:grid xl:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)]">
                    <Link href={route('public.index')} className="flex min-w-0 items-center gap-3 rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#d7ef59]">
                        {settings.logo_url && <img src={settings.logo_url} alt="UTeM" className="h-11 w-auto shrink-0 object-contain" />}
                        <span className="min-w-0"><small className="block text-[9px] font-black uppercase tracking-[.2em] text-[#d7ef59]">{t('Official portal')}</small><b className="block max-w-[7rem] truncate text-sm sm:max-w-xs sm:text-base">{app_name}</b></span>
                    </Link>
                    <PublicDesktopNav links={[{ href: route('public.index'), label: t('Home'), current: true }, { href: '#overview', label: t('Sports') }, { href: '#schedule', label: t('Schedule') }, { href: '#results', label: t('Results') }, { href: '#medals', label: t('Medal standings') }, { href: route('public.contact'), label: t('Contact') }]} />
                    <div className="flex shrink-0 items-center justify-end xl:hidden"><PublicMobileMenu links={[{ href: route('public.index'), label: t('Home'), current: true }, { href: '#overview', label: t('Sports') }, { href: '#schedule', label: t('Schedule') }, { href: '#results', label: t('Results') }, { href: '#medals', label: t('Medal standings') }, { href: route('public.contact'), label: t('Contact') }]} /></div>
                    <div className="hidden items-center justify-end gap-2 xl:flex"><span className="mr-1 h-7 w-px bg-white/10" /><LocaleSwitcher compact showLabel={false} /><PublicLoginButton /></div>
                </div>
            </header>

            <main>
                <section className="relative isolate overflow-hidden bg-[var(--public-dark)] pb-16 pt-36 text-white sm:pb-20 sm:pt-44">
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
                                    {upcoming[0] ? <div className="mt-4"><p className="truncate text-xs font-bold text-[var(--public-accent)]">{upcoming[0].sport} · {upcoming[0].event}</p><div className="mt-3 grid grid-cols-[1fr_auto_1fr] items-center gap-3"><TeamLabel team={upcoming[0].home} surface="dark" /><span className="rounded-lg bg-white/10 px-2.5 py-1.5 text-[10px] font-black">VS</span><TeamLabel team={upcoming[0].away} away surface="dark" /></div><p className="mt-3 flex items-center gap-1.5 text-xs text-white/75"><CalendarDays className="size-3.5" />{upcoming[0].scheduled_at ? formatDate(upcoming[0].scheduled_at, locale, true) : t('To be determined')}</p></div> : <p className="mt-3 text-sm text-white/75">{t('Schedule will be shown after publication.')}</p>}
                                </div>
                            </div>
                        </aside>
                    </div>
                </section>

                <section id="overview" className="relative mx-auto max-w-7xl scroll-mt-24 px-4 py-20 sm:px-6 sm:py-24">
                    <SectionHeading eyebrow={t('At a glance')} title={t('Competition overview')} description={t('Everything you need for SAF 2026')} />
                    <div className="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">{cards.map(([value, label, description, Icon], index) => <article key={label} className={`group rounded-[1.6rem] border border-[#10251f]/10 bg-white p-6 shadow-[0_24px_70px_-48px_rgba(16,37,31,.9)] transition duration-300 hover:-translate-y-1 hover:border-[#54a98f]/50 ${index % 2 ? 'lg:translate-y-6' : ''}`}><span className="flex size-11 items-center justify-center rounded-2xl bg-[#edf5d5] text-[#23745f] transition group-hover:bg-[#23745f] group-hover:text-white"><Icon className="size-5" /></span><b className="mt-9 block text-5xl font-black tracking-[-.06em]">{value}</b><h3 className="mt-5 text-xs font-black uppercase tracking-[.16em]">{label}</h3><p className="mt-1 text-sm text-[#52655e]">{description}</p></article>)}</div>
                </section>

                <section className="border-y border-[#10251f]/10 bg-white py-20 sm:py-24"><div className="mx-auto max-w-7xl px-4 sm:px-6">
                    <div className="flex flex-col justify-between gap-5 sm:flex-row sm:items-end"><SectionHeading eyebrow={t('Latest')} title={t('Live competition updates')} description={`${t('Updated')} ${formatDate(updated_at, locale, true)}`} /><div className="flex flex-col items-start gap-2 sm:items-end"><button type="button" onClick={() => refresh(true)} disabled={refreshing} className="inline-flex min-h-11 items-center justify-center gap-2 self-start rounded-xl border border-[#10251f]/15 bg-white px-4 text-sm font-black transition hover:border-[#23745f] hover:text-[#23745f] disabled:opacity-50 sm:self-auto"><RefreshCw className={`size-4 ${refreshing ? 'animate-spin' : ''}`} />{t(refreshing ? 'Refreshing' : 'Refresh')}</button><p role="status" aria-live="polite" className={`min-h-5 text-xs font-semibold ${refreshStatus === 'error' ? 'text-red-700' : 'text-[#23745f]'}`}>{refreshStatus === 'success' ? t('Refresh complete') : refreshStatus === 'error' ? t('Unable to refresh. Please try again.') : ''}</p></div></div>
                    <div className="mt-12 grid gap-14 lg:grid-cols-2"><MatchColumn id="schedule" title={t('Schedule')} matches={upcoming.slice(0, 5)} locale={locale} t={t} /><MatchColumn id="results" title={t('Results')} matches={results.slice(0, 5)} locale={locale} t={t} result /></div>
                </div></section>

                <section id="medals" className="relative scroll-mt-24 overflow-hidden bg-[#edf1e8] py-20 sm:py-24"><div className="absolute -right-32 -top-32 size-96 rounded-full bg-[#d7ef59]/20 blur-3xl" /><div className="relative mx-auto max-w-7xl px-4 sm:px-6"><SectionHeading eyebrow={t('Standings')} title={t('Medal standings')} description={t('Official standings based on confirmed results.')} /><div className="mt-12 grid gap-4 lg:grid-cols-3">{medals.slice(0, 6).map(row => <MedalCard key={row.participant_name} row={row} t={t} />)}</div>{medals.length === 0 && <EmptyState text={t('Medal standings are not available yet.')} />}</div></section>
            </main>

            <footer className="bg-[#091a16] text-white/55"><div className="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-[1.4fr_1fr]"><div className="flex items-center gap-3">{settings.logo_url && <img src={settings.logo_url} alt="" className="h-12 w-auto object-contain" />}<div><b className="text-white">{app_name}</b><p className="mt-1 text-sm">{t('Official sports information portal')}</p></div></div><nav aria-label={t('Quick links')} className="flex flex-wrap gap-x-6 gap-y-3 text-sm font-bold md:justify-end"><Link href="#schedule" className="hover:text-[#d7ef59]">{t('Schedule')}</Link><Link href="#results" className="hover:text-[#d7ef59]">{t('Results')}</Link><Link href="#medals" className="hover:text-[#d7ef59]">{t('Medal standings')}</Link><Link href={route('public.contact')} className="hover:text-[#d7ef59]">{t('Contact')}</Link></nav></div><div className="border-t border-white/10 px-4 py-5 text-center text-xs">© 2026 Universiti Teknikal Malaysia Melaka (UTeM)</div></footer>
        </div>
    </>;
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
function SectionHeading({ eyebrow, title, description }: { eyebrow: string; title: string; description: string | null }) { return <div className="max-w-2xl"><p className="text-[11px] font-black uppercase tracking-[.22em] text-[#23745f]">{eyebrow}</p><h2 className="mt-3 text-3xl font-black tracking-[-.04em] sm:text-5xl">{title}</h2>{description && <p className="mt-4 text-sm leading-6 text-[#52655e] sm:text-base">{description}</p>}</div>; }
function MatchColumn({ id, title, matches, locale, t, result = false }: { id: string; title: string; matches: Match[]; locale: string; t: Translate; result?: boolean }) { return <div id={id} className="scroll-mt-24"><div className="mb-5 flex items-center gap-3"><span className="flex size-10 items-center justify-center rounded-xl bg-[#edf5d5] text-[#23745f]">{result ? <CheckCircle2 className="size-5" /> : <Clock3 className="size-5" />}</span><h3 className="text-xl font-black">{title}</h3></div><div className="grid gap-3">{matches.map(match => <MatchCard key={match.id} match={match} locale={locale} t={t} result={result} />)}{matches.length === 0 && <EmptyState text={result ? t('No official results recorded yet.') : t('Schedule will be shown after publication.')} />}</div></div>; }
function MatchCard({ match, locale, t, result }: { match: Match; locale: string; t: Translate; result: boolean }) { return <article className="rounded-2xl border border-[#10251f]/10 bg-[#f8faf6] p-4 transition hover:border-[#23745f]/40"><div className="flex flex-wrap items-center justify-between gap-2 text-[10px] font-black uppercase tracking-[.14em] text-[#23745f]"><span>{match.sport} · {match.event}</span><span>{match.scheduled_at ? formatDate(match.scheduled_at, locale, true) : t('To be determined')}</span></div><div className="mt-4 grid grid-cols-[1fr_auto_1fr] items-center gap-3"><TeamLabel team={match.home} /><div className="rounded-xl bg-[#10251f] px-3 py-2 text-center text-xs font-black text-white">{result ? `${match.score_home ?? '–'} : ${match.score_away ?? '–'}` : 'VS'}</div><TeamLabel team={match.away} away /></div>{match.venue && <p className="mt-4 flex items-center gap-1.5 border-t border-[#10251f]/10 pt-3 text-xs text-[#52655e]"><MapPin className="size-3.5" />{match.venue}</p>}</article>; }
function TeamLabel({ team, away = false, surface = 'light' }: { team: Team; away?: boolean; surface?: 'light' | 'dark' }) { return <div className={`flex min-w-0 items-center gap-2 ${away ? 'flex-row-reverse text-right' : ''}`}>{team && <ParticipantLogo participant={team} surface={surface} size="lg" alt="" />}<b className="min-w-0 truncate text-sm">{team?.name || '–'}</b></div>; }
function MedalCard({ row, t }: { row: MedalRow; t: Translate }) { return <article className="flex items-center gap-4 rounded-2xl border border-[#10251f]/10 bg-white p-5"><span className="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-[#10251f] text-lg font-black text-[#d7ef59]">{row.rank}</span><ParticipantLogo participant={{ name: row.participant_name, logo_url: row.logo_url, inverse_logo_url: row.inverse_logo_url }} size="xl" alt="" /><div className="min-w-0 flex-1"><h3 className="truncate font-black">{row.participant_name}</h3><p className="mt-1 text-xs text-[#52655e]">{row.gold} {t('Gold')} · {row.silver} {t('Silver')} · {row.bronze} {t('Bronze')}</p></div><b className="text-2xl">{row.total_medals}</b></article>; }
function EmptyState({ text }: { text: string }) { return <div className="rounded-2xl border border-dashed border-[#10251f]/20 bg-[#f8faf6] p-8 text-center text-sm text-[#52655e]">{text}</div>; }
