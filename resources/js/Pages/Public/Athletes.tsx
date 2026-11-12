import ParticipantLogo from '@/components/ParticipantLogo';
import PublicEmptyState from '@/components/PublicEmptyState';
import PublicLayout from '@/Layouts/PublicLayout';
import PublicErrorState from '@/components/PublicErrorState';
import PublicStaleDataNotice from '@/components/PublicStaleDataNotice';
import PublicLoadingState from '@/components/PublicLoadingState';
import SafeImage from '@/components/SafeImage';
import { Pagination, PaginationContent, PaginationItem, PaginationLink, PaginationNext, PaginationPrevious } from '@/components/ui/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Dialog, DialogContent, DialogDescription, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import PublicPageHero from '@/components/PublicPageHero';
import { useI18n } from '@/lib/i18n';
import { SportIcon } from '@/lib/sportIcons';
import { Link, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Search, Users, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

type EventEntry = { name: string | null; sport: string | null; category: string | null };
type Member = { name: string; role: 'athlete_male' | 'athlete_female' | 'assistant_manager' | 'manager' | 'coach' | 'physio' };
type Roster = { id: string | null; name: string | null; name_ms: string | null; logo_url: string | null; inverse_logo_url: string | null; events: EventEntry[]; members: Member[] };
type Athlete = { id: string; name: string; faculty: string | null; faculty_ms: string | null; faculty_logo_url: string | null; faculty_inverse_logo_url: string | null; events: EventEntry[] };
type PaginatorLink = { url: string | null; label: string; active: boolean };
type Paginator<T> = { data: T[]; current_page: number; last_page: number; per_page: number; total: number; from: number | null; to: number | null; links: PaginatorLink[] };
type View = 'teams' | 'athletes';
type Filters = { q: string; sport: string; category: string; faculty: string; letter: string; sort: string };
type Props = {
    app_name: string;
    competition: { name: string; organization: string | null } | null;
    view: View;
    filters: Filters;
    rosters: Paginator<Roster> | null;
    athletes: Paginator<Athlete> | null;
    counts: { teams: number; athletes: number };
    letters: string[];
    faculties: Array<{ name: string; name_ms: string | null }>;
    sports: string[];
    categories: string[];
    stats: { teams: number; athletes: number; officials: number };
    updated_at?: string;
    error?: string | null;
};

export default function PublicAthletes({ app_name, competition, view, filters, rosters, athletes, counts, letters = [], faculties = [], sports = [], categories = [], stats, updated_at, error = null }: Props) {
    const { t, locale } = useI18n();
    const [query, setQuery] = useState(filters.q);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (query.trim() === filters.q) return;
        const id = window.setTimeout(() => applyFilters({ q: query }), 350);
        return () => window.clearTimeout(id);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [query]);

    function applyFilters(overrides: Partial<Filters & { view: View }> = {}) {
        const next = { view, q: query, sport: filters.sport, category: filters.category, faculty: filters.faculty, letter: filters.letter, sort: filters.sort, ...overrides };
        const params: Record<string, string> = {};
        if (next.view === 'athletes') params.view = 'athletes';
        if (next.q.trim()) params.q = next.q.trim();
        if (next.sport) params.sport = next.sport;
        if (next.category) params.category = next.category;
        if (next.faculty) params.faculty = next.faculty;
        if (next.view === 'athletes' && next.letter) params.letter = next.letter;
        if (next.sort && next.sort !== 'name') params.sort = next.sort;
        router.get(route('public.athletes'), params, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            onStart: () => setLoading(true),
            onFinish: () => setLoading(false),
        });
    }

    const clearFilters = () => { setQuery(''); applyFilters({ q: '', sport: '', category: '', faculty: '', letter: '', sort: 'name' }); };
    const hasFilters = Boolean(query.trim() || filters.sport || filters.category || filters.faculty || filters.letter || (filters.sort && filters.sort !== 'name'));
    const searchLabel = view === 'teams' ? t('Search team, athlete or faculty') : t('Search athlete or faculty');

    const active = view === 'athletes' ? athletes : rosters;
    const items = active?.data ?? [];
    const total = active?.total ?? (view === 'athletes' ? counts.athletes : counts.teams);
    const prevLink = active?.links.find(link => link.label.includes('Previous')) ?? null;
    const nextLink = active?.links.find(link => link.label.includes('Next')) ?? null;
    const pageLinks = (active?.links ?? []).filter(link => /^\d+$/.test(link.label));

    const sortOptions = [
        { value: 'name', label: t('Name A–Z') },
        { value: 'name_desc', label: t('Name Z–A') },
        { value: 'faculty', label: t('By faculty') },
    ];

    return (
        <PublicLayout title={`${t('Athletes & Teams')} | ${competition?.name || app_name}`} appName={app_name} current="athletes" description={t('Browse confirmed athletes, teams and official competition participation.')} canonical={route('public.athletes')}>
            <main>
                {error && <div className="mx-auto max-w-7xl px-4 pt-8 sm:px-6"><PublicErrorState title={t('Athlete directory unavailable')} description={t(error)} onRetry={() => router.reload()} /></div>}
                <PublicPageHero eyebrow={competition?.organization || t('Official competition')} title={t('Athletes & Teams')} intro={t('Meet the confirmed athletes and teams taking part in the competition.')} icon={<Users className="size-4" />} />
                <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14">
                    <section className="public-card p-5 sm:p-7">
                        <div className="flex items-center justify-between gap-4">
                            <div className="flex flex-wrap items-end gap-6">
                                <Stat value={stats?.teams ?? 0} label={t('teams')} />
                                <Stat value={stats?.athletes ?? 0} label={t('athletes')} />
                                <Stat value={stats?.officials ?? 0} label={t('officials')} />
                            </div>
                            <SafeImage src="/images/mascots/pose-semangat.webp" alt={locale === 'ms' ? 'Maskot SAF 20 memberi semangat kepada atlet' : 'SAF 20 mascot cheering on athletes'} loading="lazy" decoding="async" className="h-32 w-auto max-w-[30%] object-contain drop-shadow-xl sm:h-40" />
                        </div>

                        <Tabs value={view} onValueChange={value => applyFilters({ view: value as View, letter: '' })} className="mt-7">
                            <TabsList aria-label={t('Athletes & Teams')}>
                            {(['teams', 'athletes'] as const).map(option => (
                                <TabsTrigger key={option} value={option} disabled={loading}>
                                    {option === 'teams' ? t('Teams & Rosters') : t('Athlete Directory')}
                                    <span className="rounded-md bg-white/20 px-1.5 py-0.5 text-xs tabular-nums">{option === 'teams' ? counts.teams : counts.athletes}</span>
                                </TabsTrigger>
                            ))}
                            </TabsList>
                            <TabsContent value="teams" className="sr-only" />
                            <TabsContent value="athletes" className="sr-only" />
                        </Tabs>

                        <div className="mt-6 flex flex-wrap gap-2" role="group" aria-label={t('Filter by sport')}>
                            <Chip active={!filters.sport} onClick={() => applyFilters({ sport: '' })} disabled={loading}>{t('All Sports')}</Chip>
                            {sports.map(sport => (
                                <Chip key={sport} active={filters.sport === sport} onClick={() => applyFilters({ sport: filters.sport === sport ? '' : sport })} disabled={loading}>
                                    <SportIcon name={sport} className="size-4" />{sport}
                                </Chip>
                            ))}
                        </div>

                        <div className="mt-4 grid gap-3 lg:grid-cols-[minmax(0,1fr)_11rem_11rem_11rem_auto]">
                            <div className="relative">
                                <Search className="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-[var(--public-dark-faint)]" />
                                <Input type="search" value={query} onChange={event => setQuery(event.target.value)} placeholder={searchLabel} aria-label={searchLabel} className="h-11 w-full rounded-xl py-2.5 pl-10 pr-9 text-sm font-semibold" />
                                {query && <Button type="button" variant="ghost" size="icon" onClick={() => setQuery('')} aria-label={t('Clear search')} className="absolute right-2.5 top-1/2 size-8 -translate-y-1/2 text-[var(--public-dark-faint)]"><X className="size-3.5" /></Button>}
                            </div>
                            <Select value={filters.faculty || 'all'} onValueChange={value => applyFilters({ faculty: value === 'all' ? '' : value })} disabled={loading}><SelectTrigger aria-label={t('Filter by faculty')} className="h-11 rounded-xl bg-white text-sm font-semibold"><SelectValue placeholder={t('All Faculties')} /></SelectTrigger><SelectContent><SelectItem value="all">{t('All Faculties')}</SelectItem>{faculties.map(value => <SelectItem key={value.name} value={value.name}>{locale === 'ms' ? (value.name_ms || value.name) : value.name}</SelectItem>)}</SelectContent></Select>
                            <Select value={filters.category || 'all'} onValueChange={value => applyFilters({ category: value === 'all' ? '' : value })} disabled={loading}><SelectTrigger aria-label={t('Filter by category')} className="h-11 rounded-xl bg-white text-sm font-semibold"><SelectValue placeholder={t('All Categories')} /></SelectTrigger><SelectContent><SelectItem value="all">{t('All Categories')}</SelectItem>{categories.map(value => <SelectItem key={value} value={value}>{value}</SelectItem>)}</SelectContent></Select>
                            <Select value={filters.sort || 'name'} onValueChange={value => applyFilters({ sort: value })} disabled={loading}><SelectTrigger aria-label={t('Sort')} className="h-11 rounded-xl bg-white text-sm font-semibold"><SelectValue /></SelectTrigger><SelectContent>{sortOptions.map(option => <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>)}</SelectContent></Select>
                            {hasFilters && <Button type="button" variant="outline" onClick={clearFilters} disabled={loading} className="gap-2 rounded-xl text-sm font-black text-[var(--public-dark-faint)] hover:border-red-200 hover:text-red-600"><X className="size-4" />{t('Clear')}</Button>}
                        </div>

                        {view === 'athletes' && letters.length > 0 && (
                            <div className="mt-6 flex flex-wrap items-center gap-1.5 border-t border-[var(--public-dark-border)] pt-5" role="group" aria-label={t('Jump to letter')}>
                                <Button type="button" variant={!filters.letter ? 'default' : 'outline'} size="sm" onClick={() => applyFilters({ letter: '' })} disabled={loading} aria-pressed={!filters.letter} className="min-h-10 min-w-10 rounded-lg px-2.5 text-xs font-black">{t('All')}</Button>
                                {letters.map(letter => <Button key={letter} type="button" variant={filters.letter === letter ? 'default' : 'outline'} size="sm" onClick={() => applyFilters({ letter: filters.letter === letter ? '' : letter })} disabled={loading} aria-pressed={filters.letter === letter} className="min-h-10 min-w-10 rounded-lg px-2.5 text-xs font-black">{letter}</Button>)}
                            </div>
                        )}
                    </section>

                    <div className="mt-6 flex flex-wrap items-center justify-between gap-3 text-xs font-bold text-[var(--public-dark-faint)]" aria-busy={loading}>
                        <p aria-live="polite">{t('Showing')} <span className="text-[var(--public-text)]">{active?.from ?? 0}</span>–<span className="text-[var(--public-text)]">{active?.to ?? 0}</span> {t('of')} <span className="text-[var(--public-text)]">{total}</span> {view === 'teams' ? t('teams') : t('athletes')}{loading && <span className="ml-2 text-[var(--public-primary)]">{t('Loading…')}</span>}</p>
                        {updated_at && <PublicStaleDataNotice updatedAt={updated_at} />}
                    </div>

                    {loading ? (
                        <PublicLoadingState label={t('Loading')} />
                    ) : items.length === 0 ? (
                        <PublicEmptyState text={t('No athletes or teams found.')}>
                            {hasFilters && <Button type="button" onClick={clearFilters} className="gap-2 rounded-xl"><X className="size-4" />{t('Clear filters')}</Button>}
                        </PublicEmptyState>
                    ) : view === 'teams' ? (
                        <div className="mt-4 grid gap-5 lg:grid-cols-2">
                            {(items as Roster[]).map(roster => <RosterCard key={roster.id || roster.name} roster={roster} t={t} />)}
                        </div>
                    ) : (
                        <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {(items as Athlete[]).map((athlete, index) => <AthleteCard key={`${athlete.name}-${athlete.faculty}-${index}`} athlete={athlete} t={t} locale={locale} />)}
                        </div>
                    )}

                    {active && active.last_page > 1 && (
                        <Pagination className="mt-8" aria-label={t('Navigate pages')}>
                            <PaginationContent>
                                <PaginationItem>
                                    <PaginationPrevious asChild aria-disabled={!prevLink?.url}>
                                        {prevLink?.url ? <Link href={prevLink.url}><ChevronLeft className="size-4" />{t('Previous')}</Link> : <span><ChevronLeft className="size-4" />{t('Previous')}</span>}
                                    </PaginationPrevious>
                                </PaginationItem>
                                {pageLinks.map(link => <PaginationItem key={link.label}><PaginationLink asChild isActive={link.active}>{link.url ? <Link href={link.url}>{link.label}</Link> : <span>{link.label}</span>}</PaginationLink></PaginationItem>)}
                                <PaginationItem>
                                    <PaginationNext asChild aria-disabled={!nextLink?.url}>
                                        {nextLink?.url ? <Link href={nextLink.url}>{t('Next')}<ChevronRight className="size-4" /></Link> : <span>{t('Next')}<ChevronRight className="size-4" /></span>}
                                    </PaginationNext>
                                </PaginationItem>
                            </PaginationContent>
                        </Pagination>
                    )}
                </div>
            </main>
        </PublicLayout>
    );
}

function Stat({ value, label }: { value: number; label: string }) {
    return <div><b className="block text-3xl font-black tracking-[-.04em] tabular-nums">{value}</b><span className="mt-1 block text-xs font-black uppercase tracking-[.16em] text-[var(--public-dark-faint)]">{label}</span></div>;
}

function Chip({ active, onClick, disabled, children }: { active: boolean; onClick: () => void; disabled?: boolean; children: React.ReactNode }) {
    return <Button type="button" variant={active ? 'default' : 'outline'} size="sm" onClick={onClick} disabled={disabled} aria-pressed={active} className="min-h-10 rounded-full px-3.5 text-xs font-black">{children}</Button>;
}

function AthleteCard({ athlete, t, locale }: { athlete: Athlete; t: (key: string) => string; locale: string }) {
    const facultyName = locale === 'ms' ? (athlete.faculty_ms || athlete.faculty) : athlete.faculty;
    return <article className="public-card p-4">
        <div className="flex items-center gap-3">
            <ParticipantLogo participant={{ name: facultyName, logo_url: athlete.faculty_logo_url, inverse_logo_url: athlete.faculty_inverse_logo_url }} size="md" />
            <div className="min-w-0"><h2 className="truncate text-sm font-black">{athlete.name}</h2><p className="mt-1 truncate text-xs font-semibold text-[var(--public-dark-faint)]">{facultyName || t('Faculty')}</p></div>
        </div>
        <div className="mt-4 flex flex-wrap gap-1.5">{athlete.events.map(event => <span key={`${event.name}-${event.category}`} className="rounded-md bg-[var(--public-primary-soft)] px-2 py-1 text-xs font-bold text-[var(--public-primary)]">{event.sport}{event.category ? ` · ${event.category}` : ''}</span>)}</div>
        <Link href={route('public.athletes.show', athlete.id)} className="mt-4 inline-flex min-h-10 items-center text-xs font-black text-[var(--public-primary)] hover:underline">{t('View athlete profile')} →</Link>
    </article>;
}

function formatUpdatedAt(value: string, locale: string) {
    return new Intl.DateTimeFormat(locale === 'ms' ? 'ms-MY' : 'en-MY', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }).format(new Date(value));
}

function RosterCard({ roster, t }: { roster: Roster; t: (key: string) => string }) {
    const { locale } = useI18n();
    const [open, setOpen] = useState(false);
    const [memberQuery, setMemberQuery] = useState('');
    const [memberType, setMemberType] = useState<'all' | 'athletes' | 'officials'>('all');
    const rosterName = locale === 'ms' ? (roster.name_ms || roster.name) : roster.name;
    const athletes = roster.members.filter(member => member.role === 'athlete_male' || member.role === 'athlete_female');
    const officials = roster.members.filter(member => member.role !== 'athlete_male' && member.role !== 'athlete_female');
    const visibleMembers = useMemo(() => roster.members.filter(member => {
        const isAthlete = member.role === 'athlete_male' || member.role === 'athlete_female';
        const matchesType = memberType === 'all' || (memberType === 'athletes' ? isAthlete : !isAthlete);
        return matchesType && member.name.toLocaleLowerCase().includes(memberQuery.trim().toLocaleLowerCase());
    }), [roster.members, memberQuery, memberType]);

    return (
        <article className="public-card p-5">
            <div className="flex items-start gap-4">
                <ParticipantLogo participant={roster} size="lg" />
                <div className="min-w-0 flex-1">
                    <h2 className="truncate text-lg font-black">{rosterName}</h2>
                    <div className="mt-2 flex flex-wrap gap-1.5">{roster.events.map(event => <span key={`${event.name}-${event.category}`} className="rounded-md bg-[var(--public-primary-soft)] px-2 py-1 text-xs font-bold text-[var(--public-primary)]">{event.sport}{event.category ? ` · ${event.category}` : ''}</span>)}</div>
                </div>
            </div>
            <div className="mt-5 grid grid-cols-2 gap-3 border-y border-[var(--public-dark-border)] py-3 text-xs font-bold text-[var(--public-dark-faint)]"><span><strong className="text-base text-[var(--public-text)]">{athletes.length}</strong> {t('athletes')}</span><span><strong className="text-base text-[var(--public-text)]">{officials.length}</strong> {t('officials')}</span></div>
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogTrigger asChild><Button type="button" variant="outline" className="mt-4 min-h-11 w-full justify-between rounded-xl font-black">{t('View roster')}<span className="rounded-full bg-[var(--public-primary-soft)] px-2.5 py-1 text-xs text-[var(--public-primary)]">{roster.members.length}</span></Button></DialogTrigger>
                <DialogContent className="flex max-h-[min(85dvh,44rem)] w-full max-w-2xl flex-col gap-0 overflow-hidden rounded-2xl p-0 sm:max-w-2xl">
                    <div className="border-b border-[var(--public-dark-border)] p-5 pr-14 sm:p-6 sm:pr-14">
                        <DialogTitle className="text-lg font-black">{rosterName}</DialogTitle>
                        <DialogDescription className="mt-1">{roster.members.length} {t('roster members')} · {athletes.length} {t('athletes')} · {officials.length} {t('officials')}</DialogDescription>
                        <div className="relative mt-4">
                            <Search className="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-[var(--public-dark-faint)]" />
                            <Input autoFocus value={memberQuery} onChange={event => setMemberQuery(event.target.value)} placeholder={t('Search roster members')} aria-label={t('Search roster members')} className="h-11 rounded-xl pl-10" />
                            {memberQuery && <Button type="button" variant="ghost" size="icon" onClick={() => setMemberQuery('')} aria-label={t('Clear search')} className="absolute right-1.5 top-1/2 size-8 -translate-y-1/2"><X className="size-4" /></Button>}
                        </div>
                        <div className="mt-3 flex flex-wrap gap-2" role="group" aria-label={t('Filter roster')}>
                            {([
                                ['all', t('All'), roster.members.length],
                                ['athletes', t('Athletes'), athletes.length],
                                ['officials', t('Officials'), officials.length],
                            ] as const).map(([value, label, count]) => <Button key={value} type="button" variant={memberType === value ? 'default' : 'outline'} size="sm" aria-pressed={memberType === value} onClick={() => setMemberType(value)} className="min-h-10 rounded-full">{label}<span className="ml-1 tabular-nums">{count}</span></Button>)}
                        </div>
                    </div>
                    <div className="min-h-0 flex-1 overflow-y-auto overscroll-contain p-4 sm:p-6" aria-live="polite">
                        {visibleMembers.length ? <div className="space-y-2">{visibleMembers.map((member, index) => <div key={`${member.name}-${member.role}-${index}`} className="flex min-h-12 items-center justify-between gap-3 rounded-xl bg-[var(--public-dark-soft)] px-3.5 py-2.5 sm:px-4"><span className="min-w-0 text-sm font-bold">{member.name}</span><span className="shrink-0 text-xs font-semibold text-[var(--public-dark-faint)]">{roleLabel(member.role, t)}</span></div>)}</div> : <p className="py-10 text-center text-sm font-semibold text-[var(--public-dark-faint)]">{t('No results match your search.')}</p>}
                    </div>
                </DialogContent>
            </Dialog>
        </article>
    );
}

function roleLabel(role: Member['role'], t: (key: string) => string) {
    if (role === 'athlete_male' || role === 'athlete_female') return t('Athlete');
    return t(role === 'assistant_manager' ? 'Assistant Manager' : role.charAt(0).toUpperCase() + role.slice(1));
}
