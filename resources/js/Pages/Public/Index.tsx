import LocaleSwitcher from '@/components/LocaleSwitcher';
import { useI18n } from '@/lib/i18n';
import { type PageProps } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Activity, ArrowRight, CalendarDays, ChevronRight, Clock3, Contact, LogIn, MapPin, Medal, RefreshCw, Sparkles, Trophy, Users } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

type Team = { name: string; logo_url: string | null } | null;
type Match = { id: string; sport: string | null; event: string | null; stage: string | null; match_number: number; scheduled_at: string | null; venue: string | null; status: string; home: Team; away: Team; score_home: number | null; score_away: number | null };
type MedalRow = { rank: number; participant_name: string; team_name: string | null; logo_url: string | null; gold: number; silver: number; bronze: number; total_medals: number };
type Props = { app_name: string; competition: { name: string; description: string | null; start_date: string | null; end_date: string | null; organization: string | null } | null; stats: { sports: number; events: number; faculties: number; completed_matches: number; total_matches: number }; sports: string[]; upcoming: Match[]; results: Match[]; medals: MedalRow[]; updated_at: string };
type SharedProps = { settings?: { logo_url?: string | null } };

const LIVE_PROPS = ['results', 'upcoming', 'medals', 'stats', 'updated_at'] as const;

const dateLabel = (value: string | null, locale: string, withTime = false, missing = 'To be determined') => {
    if (!value) return missing;
    return new Intl.DateTimeFormat(locale === 'ms' ? 'ms-MY' : 'en-MY', {
        day: 'numeric', month: 'short', year: 'numeric', ...(withTime ? { hour: '2-digit', minute: '2-digit' } : {}),
    }).format(new Date(value));
};

function MatchCard({ match, result, locale, t }: { match: Match; result?: boolean; locale: string; t: (key: string) => string }) {
    const home = match.home?.name || t('To be determined');
    const away = match.away?.name || t('To be determined');
    return <article className="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_8px_30px_-24px_rgba(15,23,42,.5)] transition duration-300 hover:-translate-y-1 hover:border-emerald-300 hover:shadow-xl">
        <div className="absolute inset-x-0 top-0 h-0.5 origin-left scale-x-0 bg-emerald-500 transition-transform duration-300 group-hover:scale-x-100" />
        <div className="flex items-start justify-between gap-4">
            <div><p className="text-[11px] font-black uppercase tracking-[.18em] text-emerald-700">{match.sport || t('Sport')}</p><h3 className="mt-1 font-bold text-slate-950">{match.event}</h3></div>
            <span className={`shrink-0 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider ${match.status === 'in_progress' ? 'bg-red-50 text-red-600 ring-1 ring-red-200' : 'bg-slate-100 text-slate-500'}`}>{match.status === 'in_progress' ? t('In progress') : result ? t('Completed') : `#${match.match_number}`}</span>
        </div>
        <div className="my-5 grid grid-cols-[1fr_auto_1fr] items-center gap-3">
            <Team team={match.home} name={home} align="right" />
            <div className="min-w-[72px] rounded-xl bg-slate-950 px-3 py-2.5 text-center font-mono text-lg font-black text-white shadow-inner">{result ? `${match.score_home ?? 0} – ${match.score_away ?? 0}` : 'VS'}</div>
            <Team team={match.away} name={away} />
        </div>
        <div className="flex flex-wrap items-center justify-center gap-x-4 gap-y-2 border-t border-slate-100 pt-3 text-xs text-slate-500">
            <span className="flex items-center gap-1.5"><Clock3 className="size-3.5 text-emerald-600" />{result ? t('Completed') : dateLabel(match.scheduled_at, locale, true, t('To be determined'))}</span>
            <span className="flex items-center gap-1.5"><MapPin className="size-3.5 text-emerald-600" />{match.venue || t('Location to be announced')}</span>
        </div>
    </article>;
}

function Team({ team, name, align = 'left' }: { team: Team; name: string; align?: 'left' | 'right' }) {
    return <div className={`flex min-w-0 items-center gap-2 ${align === 'right' ? 'flex-row-reverse text-right' : ''}`}>
        {team?.logo_url && <img src={team.logo_url} alt="" className="size-[70px] shrink-0 rounded-lg object-contain" />}
        <span className="line-clamp-2 text-sm font-bold leading-tight text-slate-800">{name}</span>
    </div>;
}

export default function PublicIndex({ app_name, competition, stats, sports, upcoming, results, medals, updated_at }: Props) {
    const { t, locale } = useI18n();
    const { settings = {} } = usePage<PageProps & SharedProps>().props;
    const [sport, setSport] = useState('all');
    const [refreshing, setRefreshing] = useState(false);
    const filteredUpcoming = useMemo(() => sport === 'all' ? upcoming : upcoming.filter(item => item.sport === sport), [upcoming, sport]);
    const filteredResults = useMemo(() => sport === 'all' ? results : results.filter(item => item.sport === sport), [results, sport]);
    const progress = stats.total_matches ? Math.round((stats.completed_matches / stats.total_matches) * 100) : 0;

    const refresh = (silent = false) => {
        if (!silent) setRefreshing(true);
        router.reload({ only: [...LIVE_PROPS], preserveScroll: true, preserveState: true, onFinish: () => setRefreshing(false) });
    };

    useEffect(() => {
        const interval = window.setInterval(() => document.visibilityState === 'visible' && refresh(true), 30000);
        return () => window.clearInterval(interval);
    }, []);

    const shortcuts = [
        [route('public.medal-tally'), t('Medal standings'), t('Competition standings'), Medal, 'bg-amber-50 text-amber-700'],
        [route('public.schedules'), t('Schedule'), t('Upcoming Events'), CalendarDays, 'bg-blue-50 text-blue-700'],
        [route('public.results'), t('Results'), t('Latest results'), Trophy, 'bg-emerald-50 text-emerald-700'],
        [route('public.contact'), t('Contact Us'), t('SAF UTeM Secretariat'), Contact, 'bg-violet-50 text-violet-700'],
    ] as const;

    return <><Head title={competition?.name || app_name}><link rel="canonical" href={route('public.index')} /></Head>
        <div className="min-h-screen bg-[#f5f7f6] text-slate-950">
            <div className="bg-emerald-700 px-4 py-2 text-center text-[11px] font-bold uppercase tracking-[.18em] text-emerald-50"><span className="inline-flex items-center gap-2"><Sparkles className="size-3.5" />{t('Official sports information portal')}</span></div>
            <header className="sticky top-0 z-50 border-b border-slate-200/80 bg-white/95 backdrop-blur-xl">
                <div className="mx-auto flex h-20 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6">
                    <Link href={route('public.index')} className="flex min-w-0 items-center gap-3">{settings.logo_url && <img src={settings.logo_url} alt="UTeM" className="h-14 w-auto shrink-0 object-contain" />}<span className="min-w-0"><small className="block text-[10px] font-black uppercase tracking-[.2em] text-emerald-600">{t('Official portal')}</small><b className="block truncate text-sm sm:text-base">{app_name}</b></span></Link>
                    <nav className="hidden items-center gap-7 text-sm font-semibold text-slate-600 lg:flex"><Link href={route('public.sports')} className="hover:text-emerald-700">{t('Sports')}</Link><Link href={route('public.medal-tally')} className="hover:text-emerald-700">{t('Medal standings')}</Link><Link href={route('public.schedules')} className="hover:text-emerald-700">{t('Schedule')}</Link><Link href={route('public.results')} className="hover:text-emerald-700">{t('Results')}</Link><Link href={route('public.contact')} className="hover:text-emerald-700">{t('Contact')}</Link></nav>
                    <div className="flex items-center gap-2"><LocaleSwitcher compact showLabel={false} /><Link href={route('login')} className="inline-flex items-center gap-2 rounded-full bg-slate-950 px-3.5 py-2 text-xs font-bold text-white transition hover:bg-emerald-700 sm:px-4 sm:text-sm"><LogIn className="size-4" /><span className="hidden sm:inline">{t('Log in')}</span></Link></div>
                </div>
                <nav className="flex gap-1 overflow-x-auto border-t border-slate-100 px-3 py-2 text-xs font-semibold text-slate-600 lg:hidden"><Link href={route('public.medal-tally')} className="whitespace-nowrap rounded-full px-3 py-1.5 hover:bg-slate-100">{t('Medals')}</Link><Link href={route('public.schedules')} className="whitespace-nowrap rounded-full px-3 py-1.5 hover:bg-slate-100">{t('Schedule')}</Link><Link href={route('public.results')} className="whitespace-nowrap rounded-full px-3 py-1.5 hover:bg-slate-100">{t('Results')}</Link><Link href={route('public.contact')} className="whitespace-nowrap rounded-full px-3 py-1.5 hover:bg-slate-100">{t('Contact')}</Link></nav>
            </header>

            <main>
                <section className="relative isolate overflow-hidden bg-slate-950 text-white">
                    <div className="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_80%_20%,rgba(16,185,129,.24),transparent_35%),radial-gradient(circle_at_10%_80%,rgba(6,78,59,.35),transparent_30%)]" />
                    <div className="mx-auto grid max-w-7xl items-end gap-10 px-4 py-16 sm:px-6 md:grid-cols-5 md:py-24">
                        <div className="md:col-span-3"><div className="mb-6 inline-flex items-center gap-2 rounded-full border border-emerald-400/30 bg-emerald-400/10 px-3 py-1.5 text-[11px] font-bold uppercase tracking-[.18em] text-emerald-300"><span className="size-2 animate-pulse rounded-full bg-emerald-400" />{t('Official information hub')}</div><p className="text-xs font-bold uppercase tracking-[.28em] text-slate-400">{competition?.organization || t('Universiti Teknikal Malaysia Melaka')}</p><h1 className="mt-3 max-w-4xl text-4xl font-black leading-[1.04] tracking-tight sm:text-6xl">{competition?.name || app_name}</h1><p className="mt-6 max-w-2xl text-base leading-7 text-slate-300 sm:text-lg">{competition?.description || t('Follow schedules, latest results and medal standings in one official view.')}</p>
                            <div className="mt-8 flex flex-wrap gap-3"><Link href={route('public.schedules')} className="inline-flex items-center gap-2 rounded-full bg-emerald-400 px-5 py-3 text-sm font-black text-slate-950 transition hover:bg-emerald-300">{t('View schedule')} <ArrowRight className="size-4" /></Link><Link href={route('public.results')} className="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/5 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/10">{t('Latest results')}</Link></div>
                            {competition?.start_date && <div className="mt-8 flex flex-wrap gap-5 text-sm text-slate-400"><span className="flex items-center gap-2"><CalendarDays className="size-4 text-emerald-400" />{dateLabel(competition.start_date, locale)}{competition.end_date && ` — ${dateLabel(competition.end_date, locale)}`}</span><span className="flex items-center gap-2"><RefreshCw className="size-4 text-emerald-400" />{t('Updated')} {dateLabel(updated_at, locale, true)}</span></div>}
                        </div>
                        <div className="rounded-3xl border border-white/10 bg-white/[.06] p-6 backdrop-blur md:col-span-2"><div className="flex items-end justify-between"><div><p className="text-xs font-bold uppercase tracking-[.18em] text-slate-400">{t('Competition progress')}</p><b className="mt-2 block text-5xl font-black text-white">{progress}%</b></div><Activity className="size-10 text-emerald-400" /></div><div className="mt-6 h-2 overflow-hidden rounded-full bg-white/10"><div className="h-full rounded-full bg-gradient-to-r from-emerald-500 to-emerald-300 transition-all duration-700" style={{ width: `${progress}%` }} /></div><div className="mt-5 grid grid-cols-2 gap-3"><div className="rounded-2xl bg-white/[.06] p-4"><b className="text-2xl">{stats.completed_matches}</b><span className="mt-1 block text-xs text-slate-400">{t('Fixtures completed')}</span></div><div className="rounded-2xl bg-white/[.06] p-4"><b className="text-2xl">{stats.total_matches}</b><span className="mt-1 block text-xs text-slate-400">{t('Total Matches')}</span></div></div></div>
                    </div>
                </section>

                <section className="relative z-10 mx-auto -mt-6 max-w-7xl px-4 sm:px-6"><div className="grid overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl sm:grid-cols-2 lg:grid-cols-4">{shortcuts.map(([href, title, subtitle, Icon, color])=><Link key={href} href={href} className="group flex items-center gap-4 border-b border-slate-100 p-5 transition hover:bg-slate-50 sm:border-r lg:border-b-0"><span className={`rounded-xl p-3 ${color}`}><Icon className="size-5" /></span><span className="min-w-0"><b className="block text-sm">{title}</b><small className="mt-0.5 block truncate text-slate-500">{subtitle}</small></span><ChevronRight className="ml-auto size-4 text-slate-300 transition group-hover:translate-x-1 group-hover:text-emerald-600" /></Link>)}</div></section>

                <section aria-label="Competition summary" className="mx-auto max-w-7xl px-4 py-14 sm:px-6"><div className="grid grid-cols-2 gap-3 lg:grid-cols-4">{[[stats.sports,t('Sports'),Trophy],[stats.events,t('Events'),Medal],[stats.faculties,t('Faculties'),Users],[stats.completed_matches,t('Results'),Activity]].map(([value,label,Icon]: any)=><div key={label} className="rounded-2xl border border-slate-200 bg-white p-5"><div className="flex items-center justify-between"><span className="text-sm font-semibold text-slate-500">{label}</span><Icon className="size-5 text-emerald-600" /></div><b className="mt-5 block text-3xl tracking-tight">{value}</b></div>)}</div></section>

                <section className="mx-auto max-w-7xl px-4 pb-16 sm:px-6"><div className="mb-7 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between"><div><p className="text-xs font-black uppercase tracking-[.2em] text-emerald-700">{t('Live competition')}</p><h2 className="mt-2 text-3xl font-black tracking-tight">{t('Match schedule')}</h2><p className="mt-2 text-sm text-slate-500">{t('Follow upcoming fixtures and plan your support.')}</p></div><button onClick={() => refresh()} disabled={refreshing} className="inline-flex w-fit items-center gap-2 rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:border-emerald-400 disabled:opacity-50"><RefreshCw className={`size-4 ${refreshing ? 'animate-spin' : ''}`} />{t('Refresh')}</button></div>
                    {sports.length > 1 && <div className="mb-6 flex gap-2 overflow-x-auto pb-2"><button onClick={() => setSport('all')} className={`whitespace-nowrap rounded-full px-4 py-2 text-xs font-bold ${sport === 'all' ? 'bg-slate-950 text-white' : 'border bg-white text-slate-600'}`}>{t('All sports')}</button>{sports.map(item=><button key={item} onClick={() => setSport(item)} className={`whitespace-nowrap rounded-full px-4 py-2 text-xs font-bold ${sport === item ? 'bg-slate-950 text-white' : 'border bg-white text-slate-600'}`}>{item}</button>)}</div>}
                    {filteredUpcoming.length ? <div className="grid gap-4 lg:grid-cols-2">{filteredUpcoming.slice(0, 6).map(match=><MatchCard key={match.id} match={match} locale={locale} t={t}/>)}</div> : <Empty text={t('Schedule will be shown after publication.')} />}
                    <div className="mt-7 text-center"><Link href={route('public.schedules')} className="inline-flex items-center gap-2 text-sm font-black text-emerald-700 hover:text-emerald-800">{t('View full schedule')} <ArrowRight className="size-4" /></Link></div>
                </section>

                <section className="border-y border-slate-200 bg-white py-16"><div className="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-[1.5fr_1fr]"><div><div className="mb-7 flex items-end justify-between"><div><p className="text-xs font-black uppercase tracking-[.2em] text-emerald-700">{t('Latest')}</p><h2 className="mt-2 text-3xl font-black tracking-tight">{t('Match results')}</h2></div><Link href={route('public.results')} className="hidden items-center gap-2 text-sm font-bold text-emerald-700 sm:flex">{t('View all')} <ArrowRight className="size-4" /></Link></div>{filteredResults.length ? <div className="grid gap-4">{filteredResults.slice(0, 4).map(match=><MatchCard key={match.id} match={match} result locale={locale} t={t}/>)}</div> : <Empty text={t('No official results recorded yet.')} />}</div>
                    <aside><div className="mb-7 flex items-end justify-between"><div><p className="text-xs font-black uppercase tracking-[.2em] text-emerald-700">{t('Standings')}</p><h2 className="mt-2 text-3xl font-black tracking-tight">{t('Medal standings')}</h2></div></div><div className="overflow-hidden rounded-2xl border border-slate-200"><div className="grid grid-cols-[36px_1fr_repeat(4,38px)] bg-slate-950 px-3 py-3 text-center text-[10px] font-bold uppercase text-slate-400"><span>#</span><span className="text-left">{t('Contingent')}</span><span className="text-amber-300">E</span><span>P</span><span className="text-orange-300">G</span><span>{t('Total')}</span></div>{medals.slice(0, 8).map(row=><div key={row.participant_name} className="grid grid-cols-[36px_1fr_repeat(4,38px)] items-center border-t px-3 py-3 text-center text-xs"><b>{row.rank}</b><div className="flex min-w-0 items-center gap-2 text-left">{row.logo_url && <img src={row.logo_url} alt="" className="size-14 shrink-0 object-contain" />}<span className="truncate font-bold">{row.participant_name}</span></div><b className="text-amber-600">{row.gold}</b><span>{row.silver}</span><span className="text-orange-700">{row.bronze}</span><b>{row.total_medals}</b></div>)}{medals.length === 0 && <Empty text={t('Medal standings are not available yet.')} compact />}</div><Link href={route('public.medal-tally')} className="mt-5 inline-flex items-center gap-2 text-sm font-black text-emerald-700">{t('View full standings')} <ArrowRight className="size-4" /></Link></aside></div></section>
            </main>
            <footer className="bg-slate-950 text-slate-400"><div className="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 md:grid-cols-2"><div><b className="text-white">{app_name}</b><p className="mt-2 max-w-md text-sm leading-6">{t('Official sports information portal')}</p></div><div className="flex flex-wrap gap-x-6 gap-y-2 text-sm md:justify-end"><Link href={route('public.medal-tally')} className="hover:text-white">{t('Medals')}</Link><Link href={route('public.schedules')} className="hover:text-white">{t('Schedule')}</Link><Link href={route('public.results')} className="hover:text-white">{t('Results')}</Link><Link href={route('public.contact')} className="hover:text-white">{t('Contact')}</Link></div></div><div className="border-t border-white/10 px-4 py-5 text-center text-xs">© 2026 Universiti Teknikal Malaysia Melaka (UTeM)</div></footer>
        </div></>;
}

function Empty({ text, compact = false }: { text: string; compact?: boolean }) { return <div className={`rounded-2xl border border-dashed border-slate-300 bg-slate-50 text-center text-sm text-slate-500 ${compact ? 'p-6' : 'p-10'}`}>{text}</div>; }
