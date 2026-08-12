import LocaleSwitcher from '@/components/LocaleSwitcher';
import { useI18n } from '@/lib/i18n';
import { type PageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { CalendarDays, Clock3, Filter, Home, LogIn, Mail, MapPin, Medal, RotateCcw, Search, Trophy, Volleyball } from 'lucide-react';
import { useMemo, useState } from 'react';

type Team = { name: string; logo_url: string | null } | null;
type Match = { id: string; sport: string | null; event: string | null; stage: string | null; round: number | null; group: string | null; match_number: number; scheduled_at: string | null; venue: string | null; status: string; home: Team; away: Team; score_home: number | null; score_away: number | null };
type MedalRow = { rank: number; participant_name: string; team_name: string | null; logo_url: string | null; gold: number; silver: number; bronze: number; total_medals: number };
type Props = { page: 'medal-tally' | 'sports' | 'schedules' | 'results' | 'contact-us'; app_name: string; competition: { name: string; organization: string | null } | null; upcoming: Match[]; results: Match[]; medals: MedalRow[]; sports: string[]; sports_catalog: Array<{ name: string; events: string[] }>; stats: { sports: number; events: number; faculties: number; completed_matches: number; total_matches: number }; contact: { address: string | null }; updated_at: string };
type SharedProps = { settings?: { logo_url?: string | null } };

const pages = {
    'medal-tally': { title: 'Medal Tally', eyebrow: 'Contingent performance', description: 'Official standings based on verified gold, silver and bronze medals.', icon: Medal },
    sports: { title: 'Sports', eyebrow: 'Competition programme', description: 'Explore the sports and events included in the official SAF programme.', icon: Volleyball },
    schedules: { title: 'Competition Schedules', eyebrow: 'Upcoming matches', description: 'Dates, times and venues for the Inter-Faculty Sports competition.', icon: CalendarDays },
    results: { title: 'Official Results', eyebrow: 'Latest results', description: 'Completed match scores verified by the secretariat.', icon: Trophy },
    'contact-us': { title: 'Contact Us', eyebrow: 'SAF UTeM Secretariat', description: 'Official channels for competition, schedule and contingent management enquiries.', icon: Mail },
} as const;

const formatDate = (value: string | null, locale: string, missing: string, time = false) => value ? new Intl.DateTimeFormat(locale === 'ms' ? 'ms-MY' : 'en-MY', { day: 'numeric', month: 'long', year: 'numeric', ...(time ? { hour: '2-digit', minute: '2-digit' } : {}) }).format(new Date(value)) : missing;

function MatchCard({ match, result, t, locale }: { match: Match; result?: boolean; t: (key: string) => string; locale: string }) {
    return <article className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
        <div className="flex items-start justify-between gap-3"><div><p className="text-xs font-bold uppercase tracking-wider text-emerald-700">{match.sport || t('Sport')}</p><h3 className="mt-1 font-bold text-slate-900">{match.event}</h3></div><span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-500">{t('Match')} #{match.match_number}</span></div>
        <div className="my-5 grid grid-cols-[1fr_auto_1fr] items-center gap-3"><Team team={match.home} missing={t('To be determined')} align="right"/><span className="min-w-16 rounded-xl bg-slate-950 px-3 py-2 text-center font-mono font-bold text-white">{result ? `${match.score_home ?? 0} – ${match.score_away ?? 0}` : 'VS'}</span><Team team={match.away} missing={t('To be determined')}/></div>
        <div className="flex flex-wrap gap-3 border-t pt-3 text-xs text-slate-500"><span className="flex items-center gap-1"><Clock3 className="size-3.5" />{result ? t('Finished') : formatDate(match.scheduled_at, locale, t('To be determined'), true)}</span><span className="flex items-center gap-1"><MapPin className="size-3.5" />{match.venue || t('Venue to be announced')}</span></div>
    </article>;
}

function Team({ team, missing, align = 'left' }: { team: Team; missing: string; align?: 'left' | 'right' }) {
    return <div className={`flex min-w-0 items-center gap-2 ${align === 'right' ? 'flex-row-reverse text-right' : ''}`}>
        {team?.logo_url && <img src={team.logo_url} alt="" className="size-[70px] shrink-0 object-contain"/>}
        <b className="min-w-0 truncate">{team?.name || missing}</b>
    </div>;
}

export default function PublicInformation({ page, app_name, competition, upcoming, results, medals, sports_catalog, stats, contact, updated_at }: Props) {
    const { t, locale } = useI18n();
    const { settings = {} } = usePage<PageProps & SharedProps>().props;
    const meta = pages[page];
    const Icon = meta.icon;
    return <><Head title={`${t(meta.title)} — ${app_name}`} /><div className="min-h-screen bg-[#f6f8f7] text-slate-950">
        <header className="border-b border-slate-200 bg-white"><div className="mx-auto flex min-h-20 max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6"><Link href={route('public.index')} className="flex items-center gap-3">{settings.logo_url && <img src={settings.logo_url} alt="UTeM" className="h-14 w-auto" />}<span><small className="block text-[10px] font-bold uppercase tracking-[.2em] text-emerald-600">{t('Official Portal')}</small><b>{app_name}</b></span></Link><nav className="hidden items-center gap-6 text-sm font-medium text-slate-600 lg:flex"><Link href={route('public.sports')}>{t('Sports')}</Link><Link href={route('public.medal-tally')}>{t('Medals')}</Link><Link href={route('public.schedules')}>{t('Schedule')}</Link><Link href={route('public.results')}>{t('Results')}</Link><Link href={route('public.contact')}>{t('Contact')}</Link></nav><div className="flex items-center gap-2"><LocaleSwitcher compact showLabel={false}/><Link href={route('login')} className="flex items-center gap-2 rounded-full border px-4 py-2 text-sm"><LogIn className="size-4"/> {t('Log in')}</Link></div></div></header>
        <main><section className="relative overflow-hidden bg-slate-950 text-white"><div className="absolute right-0 top-0 size-72 rounded-full bg-emerald-500/20 blur-3xl"/><div className="relative mx-auto max-w-7xl px-4 py-14 sm:px-6 sm:py-20"><Link href={route('public.index')} className="mb-8 inline-flex items-center gap-2 text-sm text-slate-400 hover:text-white"><Home className="size-4"/> {t('Portal Home')}</Link><div className="flex max-w-3xl items-start gap-5"><div className="rounded-2xl bg-emerald-400/10 p-4 text-emerald-300"><Icon className="size-8"/></div><div><p className="text-xs font-bold uppercase tracking-[.24em] text-emerald-300">{t(meta.eyebrow)}</p><h1 className="mt-2 text-4xl font-black tracking-tight sm:text-5xl">{t(meta.title)}</h1><p className="mt-4 max-w-2xl text-slate-300">{t(meta.description)}</p><p className="mt-5 text-xs text-slate-500">{competition?.name} · {t('Last updated')} {formatDate(updated_at, locale, t('To be determined'), true)}</p></div></div></div></section>
            {page === 'medal-tally' && <MedalTally medals={medals} stats={stats} t={t}/>} 
            {page === 'sports' && <SportsPage sports={sports_catalog} stats={stats} t={t}/>} 
            {page === 'schedules' && <ScheduleCentre items={upcoming} t={t} locale={locale}/>}
            {page === 'results' && <Cards items={results} result empty={t('No official results have been recorded yet.')} t={t} locale={locale}/>}
            {page === 'contact-us' && <section className="mx-auto grid max-w-5xl gap-6 px-4 py-12 sm:px-6 md:grid-cols-2"><div className="rounded-3xl bg-emerald-700 p-8 text-white"><Mail className="size-9"/><h2 className="mt-6 text-2xl font-black">{t('SAF UTeM Secretariat')}</h2><p className="mt-3 text-emerald-100">{t('For competition, schedule and participation enquiries, please contact the secretariat through UTeM Sports Centre.')}</p></div><div className="rounded-3xl border bg-white p-8"><p className="text-xs font-bold uppercase tracking-wider text-emerald-700">{t('Correspondence address')}</p><address className="mt-4 whitespace-pre-line not-italic leading-7 text-slate-600">{contact.address || t('The secretariat address will be updated.')}</address></div></section>}
        </main><footer className="border-t bg-white"><div className="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-8 text-sm text-slate-500 sm:flex-row sm:justify-between sm:px-6"><span>© 2026 {t('Universiti Teknikal Malaysia Melaka')}</span><Link href={route('public.contact')} className="font-semibold text-emerald-700">{t('Contact the secretariat')}</Link></div></footer>
    </div></>;
}

function MedalTally({ medals, stats, t }: { medals: MedalRow[]; stats: Props['stats']; t: (key: string) => string }) {
    const [query, setQuery] = useState('');
    const filtered = medals.filter((row) => row.participant_name.toLowerCase().includes(query.toLowerCase()));
    const top = medals.slice(0, 3);
    const totalMedals = medals.reduce((sum, row) => sum + row.total_medals, 0);
    const progress = stats.total_matches > 0 ? Math.round((stats.completed_matches / stats.total_matches) * 100) : 0;

    return <section className="mx-auto max-w-6xl px-4 py-10 sm:px-6 sm:py-12">
        <div className="grid gap-3 sm:grid-cols-4">
            {[[t('Contingents'), stats.faculties], [t('Sports'), stats.sports], [t('Events'), stats.events], [t('Total medals'), totalMedals]].map(([label, value]) => <div key={String(label)} className="rounded-2xl border bg-white p-4 shadow-sm"><p className="text-xs font-bold uppercase tracking-wider text-slate-500">{label}</p><p className="mt-1 text-3xl font-black">{value}</p></div>)}
        </div>
        <div className="mt-4 rounded-2xl border bg-white p-4 shadow-sm"><div className="flex justify-between text-sm font-semibold"><span>{t('Competition progress')}</span><span>{stats.completed_matches}/{stats.total_matches} · {progress}%</span></div><div className="mt-2 h-2 overflow-hidden rounded-full bg-slate-100"><div className="h-full rounded-full bg-emerald-600" style={{ width: `${progress}%` }}/></div><p className="mt-2 text-xs text-slate-500">{t('Based on completed official matches')}</p></div>
        {top.length > 0 && <div className="mt-8 grid gap-4 md:grid-cols-3">{top.map((row, index) => <div key={row.participant_name} className={`rounded-2xl border p-5 text-center shadow-sm ${index === 0 ? 'border-amber-300 bg-amber-50' : 'bg-white'}`}><div className="text-3xl">{['🥇', '🥈', '🥉'][index]}</div>{row.logo_url ? <img src={row.logo_url} alt="" className="mx-auto mt-2 size-16 object-contain"/> : <div className="mx-auto mt-2 flex size-16 items-center justify-center rounded-full bg-slate-100 text-sm font-black text-slate-500">{row.participant_name.slice(0, 2)}</div>}<h2 className="mt-3 font-black">{row.participant_name}</h2><p className="mt-2 text-sm"><b className="text-amber-600">{row.gold}</b> · {row.silver} · <b className="text-orange-600">{row.bronze}</b></p><p className="mt-1 text-xs text-slate-500">{row.total_medals} {t('medals')}</p></div>)}</div>}
        <div className="mt-8 overflow-hidden rounded-2xl border bg-white shadow-sm"><div className="flex flex-col gap-3 border-b bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between"><div><h2 className="text-xl font-black">{t('Official medal standings')}</h2><p className="mt-1 text-xs text-slate-500">{t('Ranked by gold, then silver and bronze.')}</p></div><div className="relative"><Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400"/><input value={query} onChange={(event) => setQuery(event.target.value)} placeholder={t('Search contingent')} className="h-10 w-full rounded-xl border bg-white pl-9 pr-3 text-sm outline-none focus:border-emerald-500 sm:w-60"/></div></div><div className="overflow-x-auto"><table className="w-full min-w-[640px] text-left"><thead className="bg-slate-950 text-xs uppercase tracking-wider text-slate-300"><tr><th className="p-4 text-center">#</th><th className="p-4">{t('Contingent')}</th><th className="p-4 text-center text-amber-300">{t('Gold')}</th><th className="p-4 text-center">{t('Silver')}</th><th className="p-4 text-center text-orange-300">{t('Bronze')}</th><th className="p-4 text-center">{t('Total')}</th></tr></thead><tbody>{filtered.map(row=><tr key={row.participant_name} className="border-t transition hover:bg-emerald-50/40"><td className="p-4 text-center text-xl font-black">{row.rank}</td><td className="p-4"><div className="flex items-center gap-3">{row.logo_url ? <img src={row.logo_url} alt="" className="size-10 object-contain"/> : <span className="flex size-10 items-center justify-center rounded-full bg-slate-100 text-xs font-black">{row.participant_name.slice(0, 2)}</span>}<b>{row.participant_name}</b></div></td><td className="p-4 text-center text-xl font-black text-amber-500">{row.gold}</td><td className="p-4 text-center text-xl font-bold text-slate-500">{row.silver}</td><td className="p-4 text-center text-xl font-bold text-orange-600">{row.bronze}</td><td className="p-4 text-center text-xl font-black">{row.total_medals}</td></tr>)}</tbody></table></div>{filtered.length === 0 && <Empty text={t('Medal standings are not available yet.')}/>}</div>
    </section>;
}

function SportsPage({ sports, stats, t }: { sports: Props['sports_catalog']; stats: Props['stats']; t: (key: string) => string }) {
    return <section className="mx-auto max-w-6xl px-4 py-10 sm:px-6 sm:py-12"><div className="mb-6 flex items-end justify-between"><div><p className="text-xs font-black uppercase tracking-[.2em] text-emerald-700">{t('Official programme')}</p><h2 className="mt-2 text-3xl font-black">{t('Sports and events')}</h2></div><span className="text-sm text-slate-500">{stats.sports} {t('sports')} · {stats.events} {t('events')}</span></div>{sports.length ? <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">{sports.map((sport) => <article key={sport.name} className="rounded-2xl border bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"><div className="flex items-start gap-3"><span className="flex size-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700"><Volleyball className="size-5"/></span><div className="min-w-0"><h3 className="text-lg font-black">{sport.name}</h3><p className="text-xs text-slate-500">{sport.events.length} {t('events')}</p></div></div><div className="mt-4 space-y-2 border-t pt-4">{sport.events.map((event) => <div key={event} className="rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700">{event}</div>)}</div></article>)}</div> : <Empty text={t('The sports programme has not been published yet.')}/>}</section>;
}

function ScheduleCentre({ items, t, locale }: { items: Match[]; t: (key: string) => string; locale: string }) {
    const [sport, setSport] = useState('all');
    const [venue, setVenue] = useState('all');
    const [group, setGroup] = useState('all');
    const sports = useMemo(() => [...new Set(items.map(item => item.sport).filter(Boolean) as string[])].sort(), [items]);
    const venues = useMemo(() => [...new Set(items.map(item => item.venue).filter(Boolean) as string[])].sort(), [items]);
    const groups = useMemo(() => [...new Set(items.map(item => item.group).filter(Boolean) as string[])].sort(), [items]);
    const filtered = useMemo(() => items.filter(item =>
        (sport === 'all' || item.sport === sport) &&
        (venue === 'all' || item.venue === venue) &&
        (group === 'all' || item.group === group)
    ), [items, sport, venue, group]);
    const grouped = useMemo(() => filtered.reduce<Record<string, Match[]>>((dates, item) => {
        const key = item.scheduled_at ? item.scheduled_at.slice(0, 10) : 'tbd';
        (dates[key] ??= []).push(item);
        return dates;
    }, {}), [filtered]);
    const reset = () => { setSport('all'); setVenue('all'); setGroup('all'); };
    const hasFilters = sport !== 'all' || venue !== 'all' || group !== 'all';

    return <section className="mx-auto max-w-6xl px-4 py-10 sm:px-6 sm:py-12">
        <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div className="border-b border-slate-200 bg-slate-50/80 p-4 sm:p-6">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div><div className="flex items-center gap-2 text-xs font-black uppercase tracking-[.18em] text-emerald-700"><Filter className="size-4"/>{t('Filter fixtures')}</div><h2 className="mt-2 text-2xl font-black tracking-tight">{t('Find your match')}</h2></div>
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-[220px_180px_auto]">
                        <label className="text-xs font-bold text-slate-600"><span className="mb-1.5 block">{t('Venue')}</span><select value={venue} onChange={event => setVenue(event.target.value)} className="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"><option value="all">{t('All venues')}</option>{venues.map(item => <option key={item}>{item}</option>)}</select></label>
                        <label className="text-xs font-bold text-slate-600"><span className="mb-1.5 block">{t('Group')}</span><select value={group} onChange={event => setGroup(event.target.value)} className="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"><option value="all">{t('All groups')}</option>{groups.map(item => <option key={item}>{item}</option>)}</select></label>
                        <button type="button" onClick={reset} disabled={!hasFilters} className="mt-auto inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 transition hover:border-slate-400 disabled:cursor-not-allowed disabled:opacity-40"><RotateCcw className="size-4"/>{t('Reset')}</button>
                    </div>
                </div>
                <div className="mt-5 flex gap-2 overflow-x-auto pb-1"><button type="button" onClick={() => setSport('all')} className={`whitespace-nowrap rounded-full px-4 py-2 text-xs font-bold transition ${sport === 'all' ? 'bg-slate-950 text-white' : 'border border-slate-300 bg-white text-slate-600 hover:border-emerald-400'}`}>{t('All sports')}</button>{sports.map(item => <button type="button" key={item} onClick={() => setSport(item)} className={`whitespace-nowrap rounded-full px-4 py-2 text-xs font-bold transition ${sport === item ? 'bg-emerald-700 text-white' : 'border border-slate-300 bg-white text-slate-600 hover:border-emerald-400'}`}>{item}</button>)}</div>
            </div>
            <div className="flex items-center justify-between border-b border-slate-200 px-4 py-3 text-xs text-slate-500 sm:px-6"><span>{filtered.length} {t('matches')}</span>{hasFilters && <span className="font-semibold text-emerald-700">{t('Filters applied')}</span>}</div>
            {Object.keys(grouped).length ? Object.entries(grouped).map(([date, matches]) => <div key={date} className="border-b border-slate-200 last:border-b-0">
                <div className="sticky top-20 z-10 flex items-center gap-3 bg-slate-950 px-4 py-3 text-white sm:px-6"><CalendarDays className="size-4 text-emerald-400"/><h3 className="text-sm font-black">{date === 'tbd' ? t('Date to be announced') : new Intl.DateTimeFormat(locale === 'ms' ? 'ms-MY' : 'en-MY', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(`${date}T00:00:00`))}</h3><span className="ml-auto text-xs text-slate-400">{matches.length} {t('matches')}</span></div>
                <div className="divide-y divide-slate-100">{matches.map(match => <ScheduleRow key={match.id} match={match} t={t} locale={locale}/>)}</div>
            </div>) : <Empty text={items.length ? t('No matches match the selected filters.') : t('The competition schedule has not been published yet.')}/>} 
        </div>
    </section>;
}

function ScheduleRow({ match, t, locale }: { match: Match; t: (key: string) => string; locale: string }) {
    const stage = match.stage === 'semi_final' ? t('Semi Final') : match.stage === 'bronze' ? t('Bronze Match') : match.stage === 'final' ? t('Final') : match.group || t('Group Stage');
    const time = match.scheduled_at ? new Intl.DateTimeFormat(locale === 'ms' ? 'ms-MY' : 'en-MY', { hour: '2-digit', minute: '2-digit' }).format(new Date(match.scheduled_at)) : t('TBD');
    return <article className="p-4 transition hover:bg-slate-50 sm:p-6">
        <div className="mb-4 flex flex-wrap items-center justify-between gap-2 text-xs"><div className="flex flex-wrap items-center gap-2"><span className="rounded-full bg-emerald-50 px-2.5 py-1 font-black uppercase tracking-wider text-emerald-700">{match.sport || t('Sport')}</span><span className="font-semibold text-slate-500">{match.event}</span></div><span className="font-semibold text-slate-400">{stage}{match.round ? ` · ${t('Round')} ${match.round}` : ''} · #${match.match_number}</span></div>
        <div className="grid grid-cols-[1fr_72px_1fr] items-center gap-2 sm:grid-cols-[1fr_112px_1fr] sm:gap-5"><Team team={match.home} missing={t('To be determined')} align="right"/><div className="text-center"><div className="rounded-xl bg-slate-950 px-2 py-2.5 font-mono text-sm font-black text-white sm:text-base">{time}</div><span className="mt-1.5 block text-[10px] font-bold uppercase tracking-wider text-slate-400">{t('Kick-off')}</span></div><Team team={match.away} missing={t('To be determined')}/></div>
        <div className="mt-4 flex items-center justify-center gap-1.5 border-t border-slate-100 pt-3 text-xs text-slate-500"><MapPin className="size-3.5 text-emerald-600"/>{match.venue || t('Venue to be announced')}</div>
    </article>;
}

function Cards({ items, result = false, empty, t, locale }: { items: Match[]; result?: boolean; empty: string; t: (key: string) => string; locale: string }) { return <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6">{items.length ? <div className="grid gap-4 lg:grid-cols-2">{items.map(item=><MatchCard key={item.id} match={item} result={result} t={t} locale={locale}/>)}</div> : <Empty text={empty}/>}</section>; }
function Empty({ text }: { text: string }) { return <div className="p-12 text-center text-sm text-slate-500">{text}</div>; }
