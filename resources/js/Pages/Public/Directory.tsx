import ParticipantLogo from '@/components/ParticipantLogo';
import PublicEmptyState from '@/components/PublicEmptyState';
import PublicLayout from '@/Layouts/PublicLayout';
import PublicErrorState from '@/components/PublicErrorState';
import PublicPageHero from '@/components/PublicPageHero';
import PublicStaleDataNotice from '@/components/PublicStaleDataNotice';
import PublicSectionHeading from '@/components/PublicSectionHeading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useI18n } from '@/lib/i18n';
import { router } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { ArrowRight, FileText, MapPin, Search, Trophy, Users, X } from 'lucide-react';
import { SportIcon } from '@/lib/sportIcons';
import { type ComponentType, useMemo, useState } from 'react';

type Team = { name: string; logo_url: string | null; inverse_logo_url: string | null } | null;
type SportDocument = { title: string; url: string; file_name: string; mime_type: string; file_size: number };
type SportQuota = { mode: string | null; total: number | null; male: number | null; female: number | null; officials: number | null; min_male: number | null; min_female: number | null };
type SportEvent = { name: string; category: string | null; venues: string[]; quota: SportQuota };
type SportCatalogEntry = { name: string; categories: string[]; events: SportEvent[]; documents?: SportDocument[] };
type Props = { section: 'sports' | 'faculties' | 'venues'; app_name: string; competition: { name: string; description: string | null; organization: string | null } | null; sports_catalog: SportCatalogEntry[]; faculties: Team[]; venues: string[]; updated_at?: string; error?: string | null };

const labels: Record<Props['section'], { title: string; intro: string; icon: ComponentType<{ className?: string }> }> = {
    sports: { title: 'Sports Programme', intro: 'Explore the official sports and events in this competition.', icon: Trophy },
    faculties: { title: 'Faculties & Contingents', intro: 'Meet the faculties taking part in the competition.', icon: Users },
    venues: { title: 'Venues', intro: 'Competition locations and venues used for official fixtures.', icon: MapPin },
};

export default function PublicDirectory({ section, app_name, competition, sports_catalog, faculties, venues, updated_at, error = null }: Props) {
    const { t } = useI18n();
    const meta = labels[section];
    const Icon = meta.icon;

    return (
        <PublicLayout title={`${t(meta.title)} | ${competition?.name || app_name}`} appName={app_name} current={section === 'sports' ? section : undefined} description={t(meta.intro)} canonical={route(`public.${section}`)}>
            <main>
                {error && <div className="mx-auto max-w-7xl px-4 pt-8 sm:px-6"><PublicErrorState title={t('Directory unavailable')} description={error} onRetry={() => router.reload()} /></div>}
                <PublicPageHero eyebrow={competition?.organization || t('Official competition')} title={t(meta.title)} intro={t(meta.intro)} icon={<Icon className="size-4" />} />
                <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16">
                    <div className="mb-6 flex justify-end"><PublicStaleDataNotice updatedAt={updated_at} /></div>
                    <DirectoryContent section={section} sports_catalog={sports_catalog} faculties={faculties} venues={venues} t={t} />
                </div>
            </main>
        </PublicLayout>
    );
}

function DirectoryContent({ section, sports_catalog, faculties, venues, t }: { section: Props['section']; sports_catalog: Props['sports_catalog']; faculties: Team[]; venues: string[]; t: (key: string) => string }) {
    if (section === 'sports') return <SportsDirectory sports_catalog={sports_catalog} t={t} />;
    if (section === 'faculties') return faculties.length === 0
        ? <PublicEmptyState text={t('No faculties published yet.')} />
        : <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">{faculties.map((faculty, index) => <article key={`${faculty?.name}-${index}`} className="public-card p-5 text-center"><div className="relative z-10 flex justify-center"><ParticipantLogo participant={faculty} size="xl" /></div><h2 className="relative z-10 mt-4 text-sm font-black">{faculty?.name || 'TBC'}</h2></article>)}</div>;
    return <VenuesDirectory venues={venues} t={t} />;
}

function VenuesDirectory({ venues, t }: { venues: string[]; t: (key: string) => string }) {
    if (venues.length === 0) {
        return <PublicEmptyState text={t('The venue directory is not available yet.')} />;
    }

    return (
        <section aria-label={t('Venues')}>
            <div className="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
                <PublicSectionHeading
                    eyebrow={t('Competition locations')}
                    title={t('Find your competition venue')}
                    description={t('Browse the official locations used for SAF fixtures, then open the schedule for each venue.')}
                />
                <Link href={route('public.schedule')} className="inline-flex min-h-11 items-center gap-2 self-start rounded-xl border border-[var(--public-dark-border)] bg-white px-4 text-sm font-black transition hover:border-[var(--public-primary-border)] hover:text-[var(--public-primary)] sm:self-auto">
                    {t('View full schedule')}
                    <ArrowRight className="size-4" />
                </Link>
            </div>

            <div className="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                {venues.map((venue, index) => (
                    <article key={venue} className="public-card group flex min-h-64 flex-col p-6">
                        <div className="flex items-start justify-between gap-4">
                            <span className="flex size-12 items-center justify-center rounded-2xl bg-[var(--public-primary-soft)] text-[var(--public-primary)]">
                                <MapPin className="size-6" aria-hidden="true" />
                            </span>
                            <span className="text-xs font-black tracking-[.18em] text-[var(--public-primary)] tabular-nums">{String(index + 1).padStart(2, '0')}</span>
                        </div>
                        <div className="mt-auto pt-12">
                            <p className="text-xs font-black uppercase tracking-[.16em] text-[var(--public-dark-faint)]">{t('Official competition venue')}</p>
                            <h2 className="mt-2 text-xl font-black leading-tight tracking-[-.02em] text-[var(--public-text)]">{venue}</h2>
                            <Link
                                href={route('public.schedule', { venue })}
                                className="relative z-10 mt-5 inline-flex min-h-11 items-center gap-2 text-sm font-black text-[var(--public-primary)] transition hover:text-[var(--public-dark)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--public-primary)]/40"
                            >
                                {t('View fixtures')}
                                <ArrowRight className="size-4 transition group-hover:translate-x-1" aria-hidden="true" />
                            </Link>
                        </div>
                    </article>
                ))}
            </div>

            <div className="mt-10 flex flex-col gap-4 rounded-3xl bg-[var(--public-dark)] p-6 text-white sm:flex-row sm:items-center sm:justify-between sm:p-8">
                <div>
                    <p className="text-xs font-black uppercase tracking-[.18em] text-[var(--public-accent)]">{t('Plan your visit')}</p>
                    <p className="mt-2 max-w-2xl text-sm leading-6 text-white/70">{t('Find upcoming fixtures by sport, event, venue and time.')}</p>
                </div>
                <Link href={route('public.schedule')} className="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-[var(--public-highlight)] px-4 text-sm font-black text-[var(--public-dark)] transition hover:brightness-105">
                    {t('Explore schedule')}
                    <ArrowRight className="size-4" />
                </Link>
            </div>
        </section>
    );
}

function SportsDirectory({ sports_catalog, t }: { sports_catalog: SportCatalogEntry[]; t: (key: string) => string }) {
    const [query, setQuery] = useState('');
    const [category, setCategory] = useState('');
    const totalEvents = sports_catalog.reduce((sum, sport) => sum + sport.events.length, 0);
    const normalized = query.trim().toLowerCase();

    const categories = useMemo(() => {
        const counts = new Map<string, number>();

        sports_catalog.forEach(sport => {
            sport.categories.forEach(name => counts.set(name, (counts.get(name) ?? 0) + 1));
        });

        return Array.from(counts.entries()).sort((a, b) => a[0].localeCompare(b[0]));
    }, [sports_catalog]);

    const filtered = useMemo(() => {
        return sports_catalog.filter(sport => {
            if (category && !sport.categories.includes(category)) return false;
            if (!normalized) return true;

            return sport.name.toLowerCase().includes(normalized)
                || sport.events.some(event => event.name.toLowerCase().includes(normalized))
                || sport.categories.some(name => name.toLowerCase().includes(normalized));
        });
    }, [sports_catalog, normalized, category]);

    return (
        <section>
            <div className="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
                <PublicSectionHeading title={t('Explore the sports')} />
                <Link href={route('public.schedule')} className="inline-flex min-h-11 items-center gap-2 self-start rounded-xl border border-[var(--public-dark-border)] bg-white px-4 text-sm font-black transition hover:border-[var(--public-primary-border)] hover:text-[var(--public-primary)] sm:self-auto">{t('View full schedule')}<ArrowRight className="size-4" /></Link>
            </div>

            <div className="mt-8 flex flex-col gap-5 rounded-2xl border border-[var(--public-dark-border)] bg-white p-5 shadow-[0_24px_70px_-48px_rgba(7,27,51,.9)] lg:flex-row lg:items-center lg:justify-between">
                <div className="flex items-center gap-7">
                    <div><b className="block text-3xl font-black tracking-[-.04em] tabular-nums">{sports_catalog.length}</b><span className="mt-1 block text-xs font-black uppercase tracking-[.16em] text-[var(--public-dark-faint)]">{t('sports')}</span></div>
                    <div aria-hidden="true" className="h-10 w-px bg-[var(--public-dark-border)]" />
                    <div><b className="block text-3xl font-black tracking-[-.04em] tabular-nums">{totalEvents}</b><span className="mt-1 block text-xs font-black uppercase tracking-[.16em] text-[var(--public-dark-faint)]">{t('events')}</span></div>
                </div>
                <div className="relative w-full lg:max-w-sm">
                    <Search className="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-[var(--public-dark-faint)]" />
                    <Input
                        type="search"
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                        placeholder={t('Search sports and events')}
                        aria-label={t('Search sports and events')}
                        className="h-11 w-full rounded-xl py-2.5 pl-10 pr-9 text-sm font-semibold"
                    />
                    {query ? (
                        <Button type="button" variant="ghost" size="icon-xs" onClick={() => setQuery('')} aria-label={t('Clear search')} className="absolute right-2.5 top-1/2 -translate-y-1/2 text-[var(--public-dark-faint)] hover:bg-[var(--public-dark-soft)] hover:text-[var(--public-text)]">
                            <X className="size-3.5" />
                        </Button>
                    ) : null}
                </div>
            </div>

            {categories.length > 1 ? (
                <div role="group" aria-label={t('Filter by category')} className="mt-5 flex flex-wrap items-center gap-2">
                    <Button type="button" variant={category === '' ? 'default' : 'outline'} size="sm" onClick={() => setCategory('')} aria-pressed={category === ''} className="min-h-9 rounded-full px-3 text-xs font-black uppercase tracking-wider">{t('All')}</Button>
                    {categories.map(([name, count]) => (
                        <Button
                            key={name}
                            type="button"
                            variant={category === name ? 'default' : 'outline'}
                            size="sm"
                            onClick={() => setCategory(previous => (previous === name ? '' : name))}
                            aria-pressed={category === name}
                            className="min-h-9 gap-1.5 rounded-full px-3 text-xs font-black uppercase tracking-wider"
                        >
                            {name}
                            <span className="tabular-nums opacity-70">{count}</span>
                        </Button>
                    ))}
                </div>
            ) : null}

            <p aria-live="polite" className="mt-6 text-xs font-bold text-[var(--public-dark-faint)]">{`${t('Showing')} ${filtered.length} ${t('of')} ${sports_catalog.length} ${t('sports')}`}</p>

            <div className="mt-3 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                {filtered.map(sport => <SportCard key={sport.name} sport={sport} t={t} />)}
            </div>
            {filtered.length === 0 ? <div className="mt-3"><PublicEmptyState text={t('No sports match your search.')} /></div> : null}
        </section>
    );
}

function SportCard({ sport, t }: { sport: SportCatalogEntry; t: (key: string) => string }) {
    const labels = sport.categories.length > 0
        ? sport.categories
        : Array.from(new Set(sport.events.map(event => event.category).filter((category): category is string => Boolean(category))));
    const documents = sport.documents ?? [];

    return (
        <article className="public-card group flex h-full flex-col p-5">
            <Link
                href={route('public.schedule', { sport: sport.name })}
                aria-label={`${sport.name} — ${t('View fixtures')}`}
                className="absolute inset-0 z-0 rounded-3xl focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--public-primary)]/40"
            />
            <div className="flex items-start justify-between gap-3">
                <span className="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-[var(--public-primary-soft)] text-[var(--public-primary)]"><SportIcon name={sport.name} className="text-2xl leading-none" /></span>
                <span className="rounded-full bg-[var(--public-dark-soft)] px-2.5 py-1 text-xs font-black uppercase tracking-wider text-[var(--public-dark-faint)] tabular-nums">{sport.events.length} {t('events')}</span>
            </div>
            <h2 className="mt-4 text-lg font-black leading-tight tracking-[-.02em]">{sport.name}</h2>
            <div className="mt-3 flex flex-wrap gap-1.5">
                {labels.map(label => <span key={label} className="rounded-md border border-[var(--public-primary-border)] bg-[var(--public-primary-soft)] px-2 py-0.5 text-xs font-bold text-[var(--public-primary)]">{label}</span>)}
            </div>
            <div className="relative z-10 mt-4 space-y-2 border-t border-[var(--public-dark-border)] pt-4">
                {sport.events.map(event => (
                    <div key={`${event.name}-${event.category ?? 'uncategorized'}`} className="rounded-xl border border-[var(--public-dark-border)] bg-[var(--public-background)] p-3">
                        <div className="flex items-start justify-between gap-3">
                            <p className="text-sm font-black leading-tight">{event.name}</p>
                            {event.category ? <span className="shrink-0 text-[10px] font-black uppercase tracking-wider text-[var(--public-dark-faint)]">{event.category}</span> : null}
                        </div>
                        <div className="mt-3 grid grid-cols-3 gap-2 text-center">
                            <QuotaValue label={t('Male')} value={event.quota.male} />
                            <QuotaValue label={t('Female')} value={event.quota.female} />
                            <QuotaValue label={t('Officials')} value={event.quota.officials} />
                        </div>
                        {event.quota.total !== null ? <p className="mt-2 text-xs font-bold text-[var(--public-dark-faint)]">{t('Max Total Athletes')}: <span className="font-black tabular-nums text-[var(--public-text)]">{event.quota.total}</span></p> : null}
                        <p className="mt-3 flex items-start gap-1.5 text-xs font-semibold leading-5 text-[var(--public-dark-faint)]">
                            <MapPin className="mt-0.5 size-3.5 shrink-0 text-[var(--public-primary)]" aria-hidden="true" />
                            <span>{event.venues.length > 0 ? event.venues.join(', ') : t('Venue TBD')}</span>
                        </p>
                    </div>
                ))}
            </div>
            {documents.length > 0 ? (
                <ul className="relative z-10 mt-3 space-y-1.5">
                    {documents.map(document => (
                        <li key={document.url}>
                            <a
                                href={document.url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex min-h-10 items-center gap-1.5 rounded-lg px-2 py-2 text-xs font-bold text-[var(--public-primary)] underline-offset-2 hover:bg-[var(--public-primary-soft)] hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--public-primary)]/40"
                            >
                                <FileText className="size-3.5 shrink-0" />
                                {document.title}
                            </a>
                        </li>
                    ))}
                </ul>
            ) : null}
            <div className="mt-auto flex items-center justify-between gap-3 pt-4">
                <p className="text-xs text-[var(--public-dark-faint)]">{sport.events.length > 1 ? `${sport.events.length} ${t('events')} ${t('scheduled')}` : `1 ${t('event')} ${t('scheduled')}`}</p>
                <span className="inline-flex items-center gap-1 text-xs font-black text-[var(--public-primary)]">{t('View fixtures')}<ArrowRight className="size-3.5 transition group-hover:translate-x-0.5" /></span>
            </div>
        </article>
    );
}

function QuotaValue({ label, value }: { label: string; value: number | null }) {
    return (
        <div className="rounded-lg bg-white px-2 py-1.5">
            <span className="block text-[10px] font-black uppercase tracking-wider text-[var(--public-dark-faint)]">{label}</span>
            <strong className="mt-0.5 block text-sm font-black tabular-nums">{value ?? '—'}</strong>
        </div>
    );
}
