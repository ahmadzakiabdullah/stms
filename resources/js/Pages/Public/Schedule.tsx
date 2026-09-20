import PublicEmptyState from '@/components/PublicEmptyState';
import PublicErrorState from '@/components/PublicErrorState';
import PublicStaleDataNotice from '@/components/PublicStaleDataNotice';
import PublicLayout from '@/Layouts/PublicLayout';
import PublicPageHero from '@/components/PublicPageHero';
import PublicScheduleMatchCard, { type ScheduleMatch } from '@/components/PublicScheduleMatchCard';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { useI18n } from '@/lib/i18n';
import { router } from '@inertiajs/react';
import { CalendarDays, Clock3, Radio, SlidersHorizontal, Trophy, Search, X } from 'lucide-react';
import { useMemo, useState } from 'react';

type Props = {
    app_name: string;
    competition: { name: string; description: string | null; start_date: string | null; end_date: string | null; organization: string | null } | null;
    upcoming: ScheduleMatch[];
    completed: ScheduleMatch[];
    sports_catalog: { name: string; categories: string[]; events: { name: string }[] }[];
    venues: string[];
    updated_at: string;
    error?: string | null;
};

type TabType = 'all' | 'live' | 'upcoming' | 'completed';

const initialQueryParam = (key: string): string => {
    if (typeof window === 'undefined') return '';
    return new URLSearchParams(window.location.search).get(key)?.trim() ?? '';
};

const formatDateTime = (value: string | null, locale: string) => {
    if (!value) return '';
    return new Intl.DateTimeFormat(locale === 'ms' ? 'ms-MY' : 'en-MY', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const tabs: { key: TabType; label: string; icon: typeof CalendarDays }[] = [
    { key: 'all', label: 'All', icon: CalendarDays },
    { key: 'live', label: 'Live', icon: Radio },
    { key: 'upcoming', label: 'Upcoming', icon: Clock3 },
    { key: 'completed', label: 'Completed', icon: Trophy },
];

export default function SchedulePage({ app_name, competition, upcoming = [], completed = [], sports_catalog = [], venues = [], updated_at, error = null }: Props) {
    const { t, locale } = useI18n();
    const [activeTab, setActiveTab] = useState<TabType>('all');
    const [sportFilter, setSportFilter] = useState(() => initialQueryParam('sport'));
    const [categoryFilter, setCategoryFilter] = useState(() => initialQueryParam('category'));
    const [venueFilter, setVenueFilter] = useState(() => initialQueryParam('venue'));
    const [searchQuery, setSearchQuery] = useState('');
    const [filtersOpen, setFiltersOpen] = useState(false);

    const allMatches = useMemo(() => {
        const live = upcoming.filter(m => m.status === 'in_progress');
        return { all: [...upcoming, ...completed], live, upcoming, completed };
    }, [upcoming, completed]);

    const sportCounts = useMemo(() => {
        const counts = new Map<string, number>();

        allMatches.all.forEach(match => {
            if (!match.sport) return;
            counts.set(match.sport, (counts.get(match.sport) ?? 0) + 1);
        });

        return counts;
    }, [allMatches]);

    const categoryOptions = useMemo(() => {
        const source = sportFilter ? sports_catalog.filter(s => s.name === sportFilter) : sports_catalog;

        return Array.from(new Set(source.flatMap(s => s.categories))).sort();
    }, [sports_catalog, sportFilter]);

    const categoryCounts = useMemo(() => {
        const counts = new Map<string, number>();
        const matches = sportFilter ? allMatches.all.filter(m => m.sport === sportFilter) : allMatches.all;

        matches.forEach(match => {
            if (!match.category) return;
            counts.set(match.category, (counts.get(match.category) ?? 0) + 1);
        });

        return counts;
    }, [allMatches, sportFilter]);

    const filteredMatches = useMemo(() => {
        let matches = allMatches[activeTab];

        if (sportFilter) {
            matches = matches.filter(m => m.sport === sportFilter);
        }

        if (categoryFilter) {
            matches = matches.filter(m => m.category === categoryFilter);
        }

        if (venueFilter) {
            matches = matches.filter(m => m.venue === venueFilter);
        }

        if (searchQuery.trim()) {
            const query = searchQuery.toLowerCase();
            matches = matches.filter(m =>
                (m.home?.name || '').toLowerCase().includes(query) ||
                (m.away?.name || '').toLowerCase().includes(query) ||
                (m.event || '').toLowerCase().includes(query) ||
                (m.venue || '').toLowerCase().includes(query) ||
                String(m.match_number).includes(query)
            );
        }

        return matches;
    }, [allMatches, activeTab, sportFilter, categoryFilter, venueFilter, searchQuery]);

    const groupedMatches = useMemo(() => {
        const groups: Record<string, ScheduleMatch[]> = {};

        filteredMatches.forEach(match => {
            const key = match.scheduled_at
                ? new Date(match.scheduled_at).toLocaleDateString(locale === 'ms' ? 'ms-MY' : 'en-MY', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                })
                : t('Date to be confirmed');

            if (!groups[key]) groups[key] = [];
            groups[key].push(match);
        });

        return groups;
    }, [filteredMatches, locale, t]);

    const hasActiveFilters = sportFilter || categoryFilter || venueFilter || searchQuery.trim();

    const clearFilters = () => {
        setSportFilter('');
        setCategoryFilter('');
        setVenueFilter('');
        setSearchQuery('');
    };

    const liveCount = allMatches.live.length;
    const upcomingCount = allMatches.upcoming.length;
    const completedCount = allMatches.completed.length;

    return (
        <PublicLayout title={`${t('Competition Schedule')} | ${competition?.name || app_name}`} appName={app_name} current="schedule" description={t('Find official upcoming fixtures, live matches and completed results by sport, venue and time.')} canonical={route('public.schedule')}>
            <main>
                {error && <div className="mx-auto max-w-7xl px-4 pt-8 sm:px-6"><PublicErrorState title={t('Schedule unavailable')} description={error} onRetry={() => router.reload()} /></div>}
                <PublicPageHero
                    eyebrow={competition?.organization || t('Official competition')}
                    title={t('Competition Schedule')}
                    intro={t('Find upcoming fixtures by sport, event, venue and time.')}
                    icon={<CalendarDays className="size-4" />}
                >
                    {competition?.start_date && (
                        <p className="mt-8 inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white/75">
                            <CalendarDays className="size-4 text-[var(--public-highlight)]" />
                            {formatDateRange(competition.start_date, competition.end_date, locale)}
                        </p>
                    )}
                </PublicPageHero>

                <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-12">
                    <div className="lg:grid lg:grid-cols-[17rem_minmax(0,1fr)] lg:items-start lg:gap-8">
                        <aside className="mb-8 hidden lg:sticky lg:top-24 lg:block">
                            <FilterPanel
                                t={t}
                                searchQuery={searchQuery}
                                setSearchQuery={setSearchQuery}
                                sportFilter={sportFilter}
                                setSportFilter={setSportFilter}
                                setCategoryFilter={setCategoryFilter}
                                categoryFilter={categoryFilter}
                                setVenueFilter={setVenueFilter}
                                venueFilter={venueFilter}
                                sportsCatalog={sports_catalog}
                                sportCounts={sportCounts}
                                categoryOptions={categoryOptions}
                                categoryCounts={categoryCounts}
                                venues={venues}
                                hasActiveFilters={Boolean(hasActiveFilters)}
                                clearFilters={clearFilters}
                            />
                        </aside>
                        <div className="min-w-0">
                    <div className="mb-8 space-y-6">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div className="flex flex-wrap items-center gap-2">
                                {tabs.map(tab => {
                                    const Icon = tab.icon;
                                    let count = 0;
                                    if (tab.key === 'all') count = allMatches.all.length;
                                    else if (tab.key === 'live') count = liveCount;
                                    else if (tab.key === 'upcoming') count = upcomingCount;
                                    else if (tab.key === 'completed') count = completedCount;

                                    return (
                                        <Button
                                            type="button"
                                            variant={activeTab === tab.key ? 'default' : 'outline'}
                                            size="lg"
                                            key={tab.key}
                                            onClick={() => setActiveTab(tab.key)}
                                        >
                                            <Icon className="size-4" />
                                            <span>{t(tab.label)}</span>
                                            <span className={`inline-flex size-5 items-center justify-center rounded-full text-xs font-black ${
                                                activeTab === tab.key
                                                    ? 'bg-white/20 text-white'
                                                    : 'bg-[var(--public-dark-soft)] text-[var(--public-dark-faint)]'
                                            }`}>
                                                {count}
                                            </span>
                                        </Button>
                                    );
                                })}
                            </div>
                        </div>

                        <Sheet open={filtersOpen} onOpenChange={setFiltersOpen}>
                            <div className="flex items-center justify-between gap-3 lg:hidden">
                                <p className="text-sm font-bold text-[var(--public-dark-faint)]">{hasActiveFilters ? t('Filters applied') : t('Refine schedule')}</p>
                                <SheetTrigger asChild>
                                    <Button type="button" variant="outline" size="lg" className="shrink-0">
                                        <SlidersHorizontal className="size-4" />
                                        {t('Filters')}
                                    </Button>
                                </SheetTrigger>
                            </div>
                            <SheetContent side="bottom" className="max-h-[85vh] overflow-y-auto rounded-t-2xl">
                                <SheetHeader><SheetTitle>{t('Refine schedule')}</SheetTitle></SheetHeader>
                                <div className="mt-6 flex flex-col gap-4">
                            <div className="relative flex-1">
                                <Search className="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-[var(--public-dark-faint)]" />
                                <Input
                                    type="search"
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    placeholder={t('Search team, venue, match #...')}
                                    aria-label={t('Search team, venue, match #...')}
                                    className="h-11 w-full rounded-xl bg-white py-2.5 pl-10 pr-9 text-sm font-semibold"
                                />
                                {searchQuery && (
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        type="button"
                                        onClick={() => setSearchQuery('')}
                                        aria-label={t('Clear search')}
                                        className="absolute right-2.5 top-1/2 size-8 -translate-y-1/2 text-[var(--public-dark-faint)]"
                                    >
                                        <X className="size-3.5" />
                                    </Button>
                                )}
                            </div>

                            <div className="flex flex-col gap-3 sm:flex-row">
                                <Select value={sportFilter || 'all'} onValueChange={(value) => {
                                        setSportFilter(value === 'all' ? '' : value);
                                        setCategoryFilter('');
                                    }}><SelectTrigger aria-label={t('Filter by sport')} className="h-11 rounded-xl bg-white text-sm font-semibold"><SelectValue placeholder={t('All Sports')} /></SelectTrigger><SelectContent><SelectItem value="all">{t('All Sports')}</SelectItem>{sports_catalog.map(sport => <SelectItem key={sport.name} value={sport.name}>{sport.name} ({sportCounts.get(sport.name) ?? 0})</SelectItem>)}</SelectContent></Select>

                                <Select value={categoryFilter || 'all'} onValueChange={(value) => setCategoryFilter(value === 'all' ? '' : value)}><SelectTrigger aria-label={t('Filter by category')} className="h-11 rounded-xl bg-white text-sm font-semibold"><SelectValue placeholder={t('All Categories')} /></SelectTrigger><SelectContent><SelectItem value="all">{t('All Categories')}</SelectItem>{categoryOptions.map(category => <SelectItem key={category} value={category}>{category} ({categoryCounts.get(category) ?? 0})</SelectItem>)}</SelectContent></Select>

                                <Select value={venueFilter || 'all'} onValueChange={(value) => setVenueFilter(value === 'all' ? '' : value)}><SelectTrigger aria-label={t('Filter by venue')} className="h-11 rounded-xl bg-white text-sm font-semibold"><SelectValue placeholder={t('All Venues')} /></SelectTrigger><SelectContent><SelectItem value="all">{t('All Venues')}</SelectItem>{venues.map(venue => <SelectItem key={venue} value={venue}>{venue}</SelectItem>)}</SelectContent></Select>

                                {hasActiveFilters && (
                                    <Button
                                        variant="outline"
                                        size="lg"
                                        type="button"
                                        onClick={clearFilters}
                                    >
                                        <X className="size-4" />
                                        {t('Clear')}
                                    </Button>
                                )}
                            </div>
                                </div>
                            </SheetContent>
                        </Sheet>

                        {hasActiveFilters && (
                            <p className="text-xs font-semibold text-[var(--public-dark-faint)]">
                                {t('Showing')} <span className="font-black text-[var(--public-text)]">{filteredMatches.length}</span> {t('of')} <span className="font-black text-[var(--public-text)]">{allMatches.all.length}</span> {t('matches')}
                            </p>
                        )}
                    </div>

                    {Object.keys(groupedMatches).length === 0 ? (
                        <PublicEmptyState text={t('No matches match your current filters.')} />
                    ) : (
                        <div className="space-y-8">
                            {Object.entries(groupedMatches).map(([dateLabel, matches]) => (
                                <section key={dateLabel}>
                                    <div className="mb-4 flex items-center gap-3">
                                        <div className="flex size-10 items-center justify-center rounded-xl bg-[var(--public-primary-soft)] text-[var(--public-primary)]">
                                            <CalendarDays className="size-5" />
                                        </div>
                                        <div>
                                            <h2 className="text-lg font-black tracking-[-.02em] text-[var(--public-text)]">
                                                {dateLabel}
                                            </h2>
                                            <p className="text-xs font-semibold text-[var(--public-dark-faint)]">
                                                {matches.length} {matches.length === 1 ? t('match') : t('matches')}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="grid gap-4">
                                        {matches.map(match => (
                                            <PublicScheduleMatchCard key={match.id} match={match} />
                                        ))}
                                    </div>
                                </section>
                            ))}
                        </div>
                    )}

                    <div className="mt-10 flex justify-end"><PublicStaleDataNotice updatedAt={updated_at} /></div>
                        </div>
                </div>
                </div>
            </main>
        </PublicLayout>
    );
}

type FilterPanelProps = {
    t: (key: string) => string;
    searchQuery: string;
    setSearchQuery: (value: string) => void;
    sportFilter: string;
    setSportFilter: (value: string) => void;
    categoryFilter: string;
    setCategoryFilter: (value: string) => void;
    venueFilter: string;
    setVenueFilter: (value: string) => void;
    sportsCatalog: Props['sports_catalog'];
    sportCounts: Map<string, number>;
    categoryOptions: string[];
    categoryCounts: Map<string, number>;
    venues: string[];
    hasActiveFilters: boolean;
    clearFilters: () => void;
};

function FilterPanel({
    t, searchQuery, setSearchQuery, sportFilter, setSportFilter, categoryFilter, setCategoryFilter,
    venueFilter, setVenueFilter, sportsCatalog, sportCounts, categoryOptions, categoryCounts,
    venues, hasActiveFilters, clearFilters,
}: FilterPanelProps) {
    const selectClass = 'w-full rounded-xl border border-[var(--public-dark-border)] bg-white px-3.5 py-2.5 text-sm font-semibold outline-none transition focus:border-[var(--public-primary)] focus:ring-2 focus:ring-[var(--public-primary)]/15';

    return (
        <div className="rounded-2xl border border-[var(--public-dark-border)] bg-white p-4 shadow-sm">
            <div className="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h2 className="text-sm font-black text-[var(--public-text)]">{t('Filters')}</h2>
                    <p className="mt-1 text-xs font-medium text-[var(--public-dark-faint)]">{t('Refine the schedule')}</p>
                </div>
                <SlidersHorizontal className="size-4 text-[var(--public-primary)]" />
            </div>
            <div className="space-y-3">
                <div className="relative">
                    <Search className="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-[var(--public-dark-faint)]" />
                    <input type="search" value={searchQuery} onChange={(e) => setSearchQuery(e.target.value)} placeholder={t('Search team, venue, match #...')} aria-label={t('Search team, venue, match #...')} className={`${selectClass} pl-10 pr-9`} />
                    {searchQuery && <button type="button" onClick={() => setSearchQuery('')} aria-label={t('Clear search')} className="absolute right-2.5 top-1/2 flex size-6 -translate-y-1/2 items-center justify-center rounded-full text-[var(--public-dark-faint)] hover:bg-[var(--public-dark-soft)] hover:text-[var(--public-text)]"><X className="size-3.5" /></button>}
                </div>
                <Select value={sportFilter || 'all'} onValueChange={(value) => { setSportFilter(value === 'all' ? '' : value); setCategoryFilter(''); }}><SelectTrigger aria-label={t('Filter by sport')} className={selectClass}><SelectValue placeholder={t('All Sports')} /></SelectTrigger><SelectContent><SelectItem value="all">{t('All Sports')}</SelectItem>{sportsCatalog.map(sport => <SelectItem key={sport.name} value={sport.name}>{sport.name} ({sportCounts.get(sport.name) ?? 0})</SelectItem>)}</SelectContent></Select>
                <Select value={categoryFilter || 'all'} onValueChange={(value) => setCategoryFilter(value === 'all' ? '' : value)}><SelectTrigger aria-label={t('Filter by category')} className={selectClass}><SelectValue placeholder={t('All Categories')} /></SelectTrigger><SelectContent><SelectItem value="all">{t('All Categories')}</SelectItem>{categoryOptions.map(category => <SelectItem key={category} value={category}>{category} ({categoryCounts.get(category) ?? 0})</SelectItem>)}</SelectContent></Select>
                <Select value={venueFilter || 'all'} onValueChange={(value) => setVenueFilter(value === 'all' ? '' : value)}><SelectTrigger aria-label={t('Filter by venue')} className={selectClass}><SelectValue placeholder={t('All Venues')} /></SelectTrigger><SelectContent><SelectItem value="all">{t('All Venues')}</SelectItem>{venues.map(venue => <SelectItem key={venue} value={venue}>{venue}</SelectItem>)}</SelectContent></Select>
                {hasActiveFilters && <button type="button" onClick={clearFilters} className="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-[var(--public-dark-border)] bg-white px-4 py-2.5 text-sm font-bold text-[var(--public-dark-faint)] transition hover:border-red-200 hover:text-red-600"><X className="size-4" />{t('Clear')}</button>}
            </div>
        </div>
    );
}

function formatDateRange(start: string, end: string | null, locale: string) {
    const fmt = (d: string) => new Intl.DateTimeFormat(locale === 'ms' ? 'ms-MY' : 'en-MY', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(d));

    return end ? `${fmt(start)} — ${fmt(end)}` : fmt(start);
}
