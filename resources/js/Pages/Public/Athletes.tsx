import ParticipantLogo from '@/components/ParticipantLogo';
import PublicEmptyState from '@/components/PublicEmptyState';
import PublicLayout from '@/Layouts/PublicLayout';
import PublicErrorState from '@/components/PublicErrorState';
import PublicStaleDataNotice from '@/components/PublicStaleDataNotice';
import PublicLoadingState from '@/components/PublicLoadingState';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import { Pagination, PaginationContent, PaginationItem, PaginationLink, PaginationNext, PaginationPrevious } from '@/components/ui/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import PublicPageHero from '@/components/PublicPageHero';
import { useI18n } from '@/lib/i18n';
import { SportIcon } from '@/lib/sportIcons';
import { Link, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Search, Users, X } from 'lucide-react';
import { useEffect, useState } from 'react';

type EventEntry = { name: string | null; sport: string | null; category: string | null };
type Member = { name: string; role: 'athlete_male' | 'athlete_female' | 'assistant_manager' | 'manager' | 'coach' | 'physio' };
type Roster = { id: string | null; name: string | null; logo_url: string | null; inverse_logo_url: string | null; events: EventEntry[]; members: Member[] };
type Athlete = { id: string; name: string; faculty: string | null; faculty_logo_url: string | null; faculty_inverse_logo_url: string | null; events: EventEntry[] };
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
    faculties: string[];
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
                {error && <div className="mx-auto max-w-7xl px-4 pt-8 sm:px-6"><PublicErrorState title={t('Directory unavailable')} description={error} onRetry={() => router.reload()} /></div>}
                <PublicPageHero eyebrow={competition?.organization || t('Official competition')} title={t('Athletes & Teams')} intro={t('Meet the confirmed athletes and teams taking part in the competition.')} icon={<Users className="size-4" />} />
                <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14">
                    <section className="rounded-3xl border border-[var(--public-dark-border)] bg-white p-5 shadow-[0_24px_70px_-48px_rgba(7,27,51,.9)] sm:p-7">
                        <div className="flex flex-wrap items-end gap-6">
                            <Stat value={stats?.teams ?? 0} label={t('teams')} />
                            <Stat value={stats?.athletes ?? 0} label={t('athletes')} />
                            <Stat value={stats?.officials ?? 0} label={t('officials')} />
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
                            <TabsContent value="teams" className="hidden" aria-hidden="true" />
                            <TabsContent value="athletes" className="hidden" aria-hidden="true" />
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
                                <Input type="search" value={query} onChange={event => setQuery(event.target.value)} placeholder={t('Search athlete or faculty')} aria-label={t('Search athlete or faculty')} className="h-11 w-full rounded-xl py-2.5 pl-10 pr-9 text-sm font-semibold" />
                                {query && <Button type="button" variant="ghost" size="icon" onClick={() => setQuery('')} aria-label={t('Clear search')} className="absolute right-2.5 top-1/2 size-8 -translate-y-1/2 text-[var(--public-dark-faint)]"><X className="size-3.5" /></Button>}
                            </div>
                            <Select value={filters.faculty || 'all'} onValueChange={value => applyFilters({ faculty: value === 'all' ? '' : value })} disabled={loading}><SelectTrigger aria-label={t('Filter by faculty')} className="h-11 rounded-xl bg-white text-sm font-semibold"><SelectValue placeholder={t('All Faculties')} /></SelectTrigger><SelectContent><SelectItem value="all">{t('All Faculties')}</SelectItem>{faculties.map(value => <SelectItem key={value} value={value}>{value}</SelectItem>)}</SelectContent></Select>
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
                            {(items as Athlete[]).map((athlete, index) => <AthleteCard key={`${athlete.name}-${athlete.faculty}-${index}`} athlete={athlete} t={t} />)}
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

function AthleteCard({ athlete, t }: { athlete: Athlete; t: (key: string) => string }) {
    return <article className="rounded-2xl border border-[var(--public-dark-border)] bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-[var(--public-primary-border)] hover:shadow-md">
        <div className="flex items-center gap-3">
            <ParticipantLogo participant={{ name: athlete.faculty, logo_url: athlete.faculty_logo_url, inverse_logo_url: athlete.faculty_inverse_logo_url }} size="md" />
            <div className="min-w-0"><h2 className="truncate text-sm font-black">{athlete.name}</h2><p className="mt-1 truncate text-xs font-semibold text-[var(--public-dark-faint)]">{athlete.faculty || t('Faculty')}</p></div>
        </div>
        <div className="mt-4 flex flex-wrap gap-1.5">{athlete.events.map(event => <span key={`${event.name}-${event.category}`} className="rounded-md bg-[var(--public-primary-soft)] px-2 py-1 text-xs font-bold text-[var(--public-primary)]">{event.sport}{event.category ? ` · ${event.category}` : ''}</span>)}</div>
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
                    <div className="mt-2 flex flex-wrap gap-1.5">{roster.events.map(event => <span key={`${event.name}-${event.category}`} className="rounded-md bg-[var(--public-primary-soft)] px-2 py-1 text-xs font-bold text-[var(--public-primary)]">{event.sport}{event.category ? ` · ${event.category}` : ''}</span>)}</div>
                </div>
            </div>
            <div className="mt-5 grid grid-cols-2 gap-3 border-y border-[var(--public-dark-border)] py-3 text-xs font-bold text-[var(--public-dark-faint)]"><span><strong className="text-base text-[var(--public-text)]">{athletes.length}</strong> {t('athletes')}</span><span><strong className="text-base text-[var(--public-text)]">{officials.length}</strong> {t('officials')}</span></div>
            <Accordion type="single" collapsible className="mt-4"><AccordionItem value="roster"><AccordionTrigger>{t('View roster')}</AccordionTrigger><AccordionContent><div className="space-y-2">{roster.members.map(member => <div key={`${member.name}-${member.role}`} className="flex items-center justify-between gap-3 rounded-lg bg-[var(--public-dark-soft)] px-3 py-2 text-sm"><span className="font-bold">{member.name}</span><span className="text-xs font-semibold text-[var(--public-dark-faint)]">{roleLabel(member.role, t)}</span></div>)}</div></AccordionContent></AccordionItem></Accordion>
        </article>
    );
}

function roleLabel(role: Member['role'], t: (key: string) => string) {
    if (role === 'athlete_male' || role === 'athlete_female') return t('Athlete');
    return t(role === 'assistant_manager' ? 'Assistant Manager' : role.charAt(0).toUpperCase() + role.slice(1));
}
