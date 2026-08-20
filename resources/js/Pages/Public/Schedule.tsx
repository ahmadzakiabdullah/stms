import PublicEmptyState from '@/components/PublicEmptyState';
import PublicLayout from '@/Layouts/PublicLayout';
import PublicPageHero from '@/components/PublicPageHero';
import PublicScheduleMatchCard, { type ScheduleMatch } from '@/components/PublicScheduleMatchCard';
import { useI18n } from '@/lib/i18n';
import { Head } from '@inertiajs/react';
import { CalendarDays, Clock3, Radio, Trophy, Search, X } from 'lucide-react';
import { useMemo, useState } from 'react';

type Props = {
    app_name: string;
    competition: { name: string; description: string | null; start_date: string | null; end_date: string | null; organization: string | null } | null;
    upcoming: ScheduleMatch[];
    completed: ScheduleMatch[];
    sports_catalog: { name: string; categories: string[]; events: { name: string }[] }[];
    venues: string[];
    updated_at: string;
};

type TabType = 'all' | 'live' | 'upcoming' | 'completed';

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

export default function SchedulePage({ app_name, competition, upcoming = [], completed = [], sports_catalog = [], venues = [], updated_at }: Props) {
    const { t, locale } = useI18n();
    const [activeTab, setActiveTab] = useState<TabType>('all');
    const [sportFilter, setSportFilter] = useState('');
    const [categoryFilter, setCategoryFilter] = useState('');
    const [venueFilter, setVenueFilter] = useState('');
    const [searchQuery, setSearchQuery] = useState('');

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
        <PublicLayout title={`${t('Competition Schedule')} | ${competition?.name || app_name}`} appName={app_name} current="schedule">
            <Head><link rel="canonical" href={route('public.schedule')} /></Head>
            <main>
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
                                        <button
                                            key={tab.key}
                                            onClick={() => setActiveTab(tab.key)}
                                            className={`inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-black transition-all ${
                                                activeTab === tab.key
                                                    ? 'border-[var(--public-primary)] bg-[var(--public-primary)] text-white shadow-sm'
                                                    : 'border-slate-200 bg-white text-slate-600 hover:border-[var(--public-primary-border)] hover:text-[var(--public-primary)]'
                                            }`}
                                        >
                                            <Icon className="size-4" />
                                            <span>{t(tab.label)}</span>
                                            <span className={`inline-flex size-5 items-center justify-center rounded-full text-[10px] font-black ${
                                                activeTab === tab.key
                                                    ? 'bg-white/20 text-white'
                                                    : 'bg-slate-100 text-slate-500'
                                            }`}>
                                                {count}
                                            </span>
                                        </button>
                                    );
                                })}
                            </div>
                        </div>

                        <div className="flex flex-col gap-4 lg:flex-row">
                            <div className="relative flex-1">
                                <Search className="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                                <input
                                    type="search"
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    placeholder={t('Search team, venue, match #...')}
                                    aria-label={t('Search team, venue, match #...')}
                                    className="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-9 text-sm font-semibold outline-none transition placeholder:text-slate-400 focus:border-[var(--public-primary)] focus:ring-2 focus:ring-[var(--public-primary)]/15"
                                />
                                {searchQuery && (
                                    <button
                                        type="button"
                                        onClick={() => setSearchQuery('')}
                                        aria-label={t('Clear search')}
                                        className="absolute right-2.5 top-1/2 -translate-y-1/2 flex size-6 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                                    >
                                        <X className="size-3.5" />
                                    </button>
                                )}
                            </div>

                            <div className="flex flex-col gap-3 sm:flex-row">
                                <select
                                    value={sportFilter}
                                    onChange={(e) => {
                                        setSportFilter(e.target.value);
                                        setCategoryFilter('');
                                    }}
                                    aria-label={t('Filter by sport')}
                                    className="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold outline-none transition focus:border-[var(--public-primary)] focus:ring-2 focus:ring-[var(--public-primary)]/15"
                                >
                                    <option value="">{t('All Sports')}</option>
                                    {sports_catalog.map(sport => (
                                        <option key={sport.name} value={sport.name}>
                                            {sport.name} ({sportCounts.get(sport.name) ?? 0})
                                        </option>
                                    ))}
                                </select>

                                <select
                                    value={categoryFilter}
                                    onChange={(e) => setCategoryFilter(e.target.value)}
                                    aria-label={t('Filter by category')}
                                    className="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold outline-none transition focus:border-[var(--public-primary)] focus:ring-2 focus:ring-[var(--public-primary)]/15"
                                >
                                    <option value="">{t('All Categories')}</option>
                                    {categoryOptions.map(category => (
                                        <option key={category} value={category}>
                                            {category} ({categoryCounts.get(category) ?? 0})
                                        </option>
                                    ))}
                                </select>

                                <select
                                    value={venueFilter}
                                    onChange={(e) => setVenueFilter(e.target.value)}
                                    aria-label={t('Filter by venue')}
                                    className="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold outline-none transition focus:border-[var(--public-primary)] focus:ring-2 focus:ring-[var(--public-primary)]/15"
                                >
                                    <option value="">{t('All Venues')}</option>
                                    {venues.map(venue => (
                                        <option key={venue} value={venue}>
                                            {venue}
                                        </option>
                                    ))}
                                </select>

                                {hasActiveFilters && (
                                    <button
                                        type="button"
                                        onClick={clearFilters}
                                        className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 transition hover:border-red-200 hover:text-red-600"
                                    >
                                        <X className="size-4" />
                                        {t('Clear')}
                                    </button>
                                )}
                            </div>
                        </div>

                        {hasActiveFilters && (
                            <p className="text-xs font-semibold text-slate-500">
                                {t('Showing')} <span className="font-black text-slate-700">{filteredMatches.length}</span> {t('of')} <span className="font-black text-slate-700">{allMatches.all.length}</span> {t('matches')}
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
                                            <p className="text-xs font-semibold text-slate-500">
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

                    <p className="mt-10 text-right text-xs text-slate-400">
                        {t('Updated')} {formatDateTime(updated_at, locale)}
                    </p>
                </div>
            </main>
        </PublicLayout>
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
