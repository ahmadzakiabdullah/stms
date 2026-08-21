import ParticipantLogo from '@/components/ParticipantLogo';
import PublicEmptyState from '@/components/PublicEmptyState';
import PublicLayout from '@/Layouts/PublicLayout';
import PublicPageHero from '@/components/PublicPageHero';
import { useI18n } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/react';
import { Search, Users, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

type EventEntry = { name: string | null; sport: string | null; category: string | null };
type Member = { name: string; role: 'athlete_male' | 'athlete_female' | 'assistant_manager' | 'manager' | 'coach' | 'physio' };
type Roster = { id: string | null; name: string | null; logo_url: string | null; inverse_logo_url: string | null; events: EventEntry[]; members: Member[] };
type Athlete = { id: string; name: string; faculty: string | null; faculty_logo_url: string | null; faculty_inverse_logo_url: string | null; events: EventEntry[] };
type Props = {
    app_name: string;
    competition: { name: string; organization: string | null } | null;
    rosters: Roster[];
    athletes: Athlete[];
    sports: string[];
    categories: string[];
    stats: { teams: number; athletes: number; officials: number };
    updated_at?: string;
};

export default function PublicAthletes({ app_name, competition, rosters = [], athletes = [], sports = [], categories = [], stats, updated_at }: Props) {
    const { t, locale } = useI18n();
    const queryParams = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : null;
    const [query, setQuery] = useState(queryParams?.get('q') || '');
    const [sport, setSport] = useState(queryParams?.get('sport') || '');
    const [category, setCategory] = useState(queryParams?.get('category') || '');
    const [view, setView] = useState<'teams' | 'athletes'>(queryParams?.get('view') === 'athletes' ? 'athletes' : 'teams');
    const normalized = query.trim().toLowerCase();

    const filteredRosters = useMemo(() => rosters.filter(roster => {
        const matchesText = !normalized || [roster.name, ...roster.members.map(member => member.name), ...roster.events.map(event => event.name)].filter(Boolean).some(value => value!.toLowerCase().includes(normalized));
        const matchesSport = !sport || roster.events.some(event => event.sport === sport);
        const matchesCategory = !category || roster.events.some(event => event.category === category);
        return matchesText && matchesSport && matchesCategory;
    }), [rosters, normalized, sport, category]);
    const filteredAthletes = useMemo(() => athletes.filter(athlete => {
        const matchesText = !normalized || [athlete.name, athlete.faculty, ...athlete.events.map(event => event.name)].filter(Boolean).some(value => value!.toLowerCase().includes(normalized));
        const matchesSport = !sport || athlete.events.some(event => event.sport === sport);
        const matchesCategory = !category || athlete.events.some(event => event.category === category);
        return matchesText && matchesSport && matchesCategory;
    }), [athletes, normalized, sport, category]);

    const clearFilters = () => { setQuery(''); setSport(''); setCategory(''); };
    const hasFilters = Boolean(normalized || sport || category);

    useEffect(() => {
        const params = new URLSearchParams();
        if (view !== 'teams') params.set('view', view);
        if (query.trim()) params.set('q', query.trim());
        if (sport) params.set('sport', sport);
        if (category) params.set('category', category);
        const next = params.toString();
        window.history.replaceState({}, '', `${window.location.pathname}${next ? `?${next}` : ''}`);
    }, [view, query, sport, category]);

    return (
        <PublicLayout title={`${t('Athletes & Teams')} | ${competition?.name || app_name}`} appName={app_name} current="athletes">
            <Head><link rel="canonical" href={route('public.athletes')} /></Head>
            <main>
                <PublicPageHero eyebrow={competition?.organization || t('Official competition')} title={t('Athletes & Teams')} intro={t('Meet the confirmed athletes and teams taking part in the competition.')} icon={<Users className="size-4" />} />
                <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14">
                    <section className="rounded-3xl border border-[var(--public-dark-border)] bg-white p-5 shadow-[0_24px_70px_-48px_rgba(7,27,51,.9)] sm:p-7">
                        <div className="flex flex-wrap items-end gap-6">
                            <Stat value={stats?.teams ?? 0} label={t('teams')} />
                            <Stat value={stats?.athletes ?? 0} label={t('athletes')} />
                            <Stat value={stats?.officials ?? 0} label={t('officials')} />
                        </div>
                        <div className="mt-7 flex flex-wrap gap-2 border-t border-[var(--public-dark-border)] pt-5">
                            {(['teams', 'athletes'] as const).map(option => <button key={option} type="button" onClick={() => setView(option)} className={`rounded-xl px-4 py-2.5 text-sm font-black transition ${view === option ? 'bg-[var(--public-primary)] text-white' : 'border border-[var(--public-dark-border)] bg-white text-[var(--public-dark-faint)] hover:text-[var(--public-primary)]'}`}>{option === 'teams' ? t('Teams & Rosters') : t('Athlete Directory')}</button>)}
                        </div>
                        <div className="mt-7 grid gap-3 lg:grid-cols-[minmax(0,1fr)_12rem_12rem_auto]">
                            <div className="relative">
                                <Search className="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-[var(--public-dark-faint)]" />
                                <input type="search" value={query} onChange={event => setQuery(event.target.value)} placeholder={t('Search athlete or faculty')} aria-label={t('Search athlete or faculty')} className="w-full rounded-xl border border-[var(--public-dark-border)] py-2.5 pl-10 pr-9 text-sm font-semibold outline-none focus:border-[var(--public-primary-border)] focus:ring-2 focus:ring-[var(--public-primary)]/15" />
                                {query && <button type="button" onClick={() => setQuery('')} aria-label={t('Clear search')} className="absolute right-2.5 top-1/2 flex size-6 -translate-y-1/2 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100"><X className="size-3.5" /></button>}
                            </div>
                            <select value={sport} onChange={event => setSport(event.target.value)} aria-label={t('Filter by sport')} className="rounded-xl border border-[var(--public-dark-border)] bg-white px-3.5 py-2.5 text-sm font-semibold outline-none focus:border-[var(--public-primary-border)]"><option value="">{t('All Sports')}</option>{sports.map(value => <option key={value} value={value}>{value}</option>)}</select>
                            <select value={category} onChange={event => setCategory(event.target.value)} aria-label={t('Filter by category')} className="rounded-xl border border-[var(--public-dark-border)] bg-white px-3.5 py-2.5 text-sm font-semibold outline-none focus:border-[var(--public-primary-border)]"><option value="">{t('All Categories')}</option>{categories.map(value => <option key={value} value={value}>{value}</option>)}</select>
                            {hasFilters && <button type="button" onClick={clearFilters} className="inline-flex items-center justify-center gap-2 rounded-xl border border-[var(--public-dark-border)] px-4 py-2.5 text-sm font-black text-slate-600 hover:border-red-200 hover:text-red-600"><X className="size-4" />{t('Clear')}</button>}
                        </div>
                    </section>

                    <div className="mt-6 flex flex-wrap items-center justify-between gap-3 text-xs font-bold text-[var(--public-dark-faint)]"><p aria-live="polite">{t('Showing')} <span className="text-[var(--public-text)]">{view === 'teams' ? filteredRosters.length : filteredAthletes.length}</span> {t('of')} <span className="text-[var(--public-text)]">{view === 'teams' ? rosters.length : athletes.length}</span> {view === 'teams' ? t('teams') : t('athletes')}</p>{updated_at && <p>{t('Updated')} {formatUpdatedAt(updated_at, locale)}</p>}</div>

                    {(view === 'teams' ? filteredRosters.length : filteredAthletes.length) === 0 ? <PublicEmptyState text={t('No athletes or teams found.')} /> : view === 'teams' ? (
                        <div className="mt-4 grid gap-5 lg:grid-cols-2">
                            {filteredRosters.map(roster => <RosterCard key={roster.id || roster.name} roster={roster} t={t} />)}
                        </div>
                    ) : (
                        <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {filteredAthletes.map((athlete, index) => <AthleteCard key={`${athlete.name}-${athlete.faculty}-${index}`} athlete={athlete} t={t} />)}
                        </div>
                    )}
                </div>
            </main>
        </PublicLayout>
    );
}

function Stat({ value, label }: { value: number; label: string }) {
    return <div><b className="block text-3xl font-black tracking-[-.04em] tabular-nums">{value}</b><span className="mt-1 block text-[10px] font-black uppercase tracking-[.16em] text-[var(--public-dark-faint)]">{label}</span></div>;
}

function AthleteCard({ athlete, t }: { athlete: Athlete; t: (key: string) => string }) {
    return <article className="rounded-2xl border border-[var(--public-dark-border)] bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-[var(--public-primary-border)] hover:shadow-md">
        <div className="flex items-center gap-3">
            <ParticipantLogo participant={{ name: athlete.faculty, logo_url: athlete.faculty_logo_url, inverse_logo_url: athlete.faculty_inverse_logo_url }} size="md" />
            <div className="min-w-0"><h2 className="truncate text-sm font-black">{athlete.name}</h2><p className="mt-1 truncate text-xs font-semibold text-[var(--public-dark-faint)]">{athlete.faculty || t('Faculty')}</p></div>
        </div>
        <div className="mt-4 flex flex-wrap gap-1.5">{athlete.events.map(event => <span key={`${event.name}-${event.category}`} className="rounded-md bg-[var(--public-primary-soft)] px-2 py-1 text-[11px] font-bold text-[var(--public-primary)]">{event.sport}{event.category ? ` · ${event.category}` : ''}</span>)}</div>
        <Link href={route('public.athletes.show', athlete.id)} className="mt-4 inline-flex min-h-10 items-center text-xs font-black text-[var(--public-primary)] hover:underline">{t('View athlete profile')} →</Link>
    </article>;
}

function formatUpdatedAt(value: string, locale: string) {
    return new Intl.DateTimeFormat(locale === 'ms' ? 'ms-MY' : 'en-MY', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }).format(new Date(value));
}

function RosterCard({ roster, t }: { roster: Roster; t: (key: string) => string }) {
    const athletes = roster.members.filter(member => member.role === 'athlete_male' || member.role === 'athlete_female');
    const officials = roster.members.filter(member => !athletes.includes(member));

    return (
        <article className="rounded-2xl border border-[var(--public-dark-border)] bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-[var(--public-primary-border)] hover:shadow-md">
            <div className="flex items-start gap-4">
                <ParticipantLogo participant={roster} size="lg" />
                <div className="min-w-0 flex-1">
                    <h2 className="truncate text-lg font-black">{roster.name}</h2>
                    <div className="mt-2 flex flex-wrap gap-1.5">{roster.events.map(event => <span key={`${event.name}-${event.category}`} className="rounded-md bg-[var(--public-primary-soft)] px-2 py-1 text-[11px] font-bold text-[var(--public-primary)]">{event.sport}{event.category ? ` · ${event.category}` : ''}</span>)}</div>
                </div>
            </div>
            <div className="mt-5 grid grid-cols-2 gap-3 border-y border-[var(--public-dark-border)] py-3 text-xs font-bold text-[var(--public-dark-faint)]"><span><strong className="text-base text-[var(--public-text)]">{athletes.length}</strong> {t('athletes')}</span><span><strong className="text-base text-[var(--public-text)]">{officials.length}</strong> {t('officials')}</span></div>
            <details className="mt-4 group"><summary className="cursor-pointer list-none text-sm font-black text-[var(--public-primary)] group-open:mb-3 [&::-webkit-details-marker]:hidden">{t('View roster')}</summary><div className="space-y-2">{roster.members.map(member => <div key={`${member.name}-${member.role}`} className="flex items-center justify-between gap-3 rounded-lg bg-[var(--public-dark-soft)] px-3 py-2 text-sm"><span className="font-bold">{member.name}</span><span className="text-[11px] font-semibold text-[var(--public-dark-faint)]">{roleLabel(member.role, t)}</span></div>)}</div></details>
        </article>
    );
}

function roleLabel(role: Member['role'], t: (key: string) => string) {
    if (role === 'athlete_male' || role === 'athlete_female') return t('Athlete');
    return t(role === 'assistant_manager' ? 'Assistant Manager' : role.charAt(0).toUpperCase() + role.slice(1));
}
