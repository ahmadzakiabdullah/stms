import ParticipantLogo from '@/components/ParticipantLogo';
import PublicEmptyState from '@/components/PublicEmptyState';
import PublicLayout from '@/Layouts/PublicLayout';
import PublicPageHero from '@/components/PublicPageHero';
import PublicSectionHeading from '@/components/PublicSectionHeading';
import { useI18n } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight, FileText, MapPin, Search, Trophy, Users, X } from 'lucide-react';
import { SportIcon } from '@/lib/sportIcons';
import { type ComponentType, useMemo, useState } from 'react';

type Team = { name: string; logo_url: string | null; inverse_logo_url: string | null } | null;
type SportDocument = { title: string; url: string; file_name: string; mime_type: string; file_size: number };
type SportCatalogEntry = { name: string; categories: string[]; events: { name: string; category: string | null }[]; documents?: SportDocument[] };
type Props = { section: 'sports' | 'faculties' | 'venues'; app_name: string; competition: { name: string; description: string | null; organization: string | null } | null; sports_catalog: SportCatalogEntry[]; faculties: Team[]; venues: string[] };

const labels: Record<Props['section'], { title: string; intro: string; icon: ComponentType<{ className?: string }> }> = {
    sports: { title: 'Sports Programme', intro: 'Explore the official sports and events in this competition.', icon: Trophy },
    faculties: { title: 'Faculties & Contingents', intro: 'Meet the faculties taking part in the competition.', icon: Users },
    venues: { title: 'Venues', intro: 'Competition locations and venues used for official fixtures.', icon: MapPin },
};

export default function PublicDirectory({ section, app_name, competition, sports_catalog, faculties, venues }: Props) {
    const { t } = useI18n();
    const meta = labels[section];
    const Icon = meta.icon;

    return (
        <PublicLayout title={`${t(meta.title)} | ${competition?.name || app_name}`} appName={app_name} current={section === 'sports' ? section : undefined}>
            <Head><link rel="canonical" href={route(`public.${section}`)} /></Head>
            <main>
                <PublicPageHero eyebrow={competition?.organization || t('Official competition')} title={t(meta.title)} intro={t(meta.intro)} icon={<Icon className="size-4" />} />
                <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16">
                    <DirectoryContent section={section} sports_catalog={sports_catalog} faculties={faculties} venues={venues} t={t} />
                </div>
            </main>
        </PublicLayout>
    );
}

function DirectoryContent({ section, sports_catalog, faculties, venues, t }: { section: Props['section']; sports_catalog: Props['sports_catalog']; faculties: Team[]; venues: string[]; t: (key: string) => string }) {
    if (section === 'sports') return <SportsDirectory sports_catalog={sports_catalog} t={t} />;
    if (section === 'faculties') return <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">{faculties.map((faculty, index) => <article key={`${faculty?.name}-${index}`} className="rounded-2xl border border-[var(--public-dark-border)] bg-white p-5 text-center shadow-sm"><div className="flex justify-center"><ParticipantLogo participant={faculty} size="xl" /></div><h2 className="mt-4 text-sm font-black">{faculty?.name || 'TBC'}</h2></article>)}</div>;
    return <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">{venues.map(venue => <article key={venue} className="rounded-2xl border border-[var(--public-dark-border)] bg-white p-6 shadow-sm"><MapPin className="size-6 text-[var(--public-primary)]" /><h2 className="mt-4 text-lg font-black">{venue}</h2><p className="mt-2 text-sm text-[var(--public-dark-faint)]">{t('Official competition venue')}</p></article>)}</div>;
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

    const chipClass = (active: boolean) => `inline-flex min-h-9 items-center gap-1.5 rounded-full border px-3 text-xs font-black uppercase tracking-wider transition ${
        active
            ? 'border-[var(--public-primary)] bg-[var(--public-primary)] text-white'
            : 'border-[var(--public-dark-border)] bg-white text-[var(--public-dark-faint)] hover:border-[var(--public-primary-border)] hover:text-[var(--public-primary)]'
    }`;

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
                    <input
                        type="search"
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                        placeholder={t('Search sports and events')}
                        aria-label={t('Search sports and events')}
                        className="w-full rounded-xl border border-[var(--public-dark-border)] bg-white py-2.5 pl-10 pr-9 text-sm font-semibold outline-none transition placeholder:text-[var(--public-dark-faint)] focus:border-[var(--public-primary-border)] focus:ring-2 focus:ring-[var(--public-primary)]/15"
                    />
                    {query ? (
                        <button type="button" onClick={() => setQuery('')} aria-label={t('Clear search')} className="absolute right-2.5 top-1/2 -translate-y-1/2 flex size-6 items-center justify-center rounded-full text-[var(--public-dark-faint)] transition hover:bg-[var(--public-dark-soft)] hover:text-[var(--public-text)]">
                            <X className="size-3.5" />
                        </button>
                    ) : null}
                </div>
            </div>

            {categories.length > 1 ? (
                <div role="group" aria-label={t('Filter by category')} className="mt-5 flex flex-wrap items-center gap-2">
                    <button type="button" onClick={() => setCategory('')} aria-pressed={category === ''} className={chipClass(category === '')}>{t('All')}</button>
                    {categories.map(([name, count]) => (
                        <button
                            key={name}
                            type="button"
                            onClick={() => setCategory(previous => (previous === name ? '' : name))}
                            aria-pressed={category === name}
                            className={chipClass(category === name)}
                        >
                            {name}
                            <span className={`tabular-nums ${category === name ? 'text-white/70' : 'text-[var(--public-dark-faint)]'}`}>{count}</span>
                        </button>
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
        <article className="group relative flex h-full flex-col rounded-2xl border border-[var(--public-dark-border)] bg-white p-5 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-[var(--public-primary-border)] hover:shadow-[0_24px_70px_-48px_rgba(7,27,51,.9)]">
            <Link
                href={route('public.schedule', { sport: sport.name })}
                aria-label={`${sport.name} — ${t('View fixtures')}`}
                className="absolute inset-0 z-0 rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--public-primary)]/40"
            />
            <div className="flex items-start justify-between gap-3">
                <span className="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-[var(--public-primary-soft)] text-[var(--public-primary)]"><SportIcon name={sport.name} className="text-2xl leading-none" /></span>
                <span className="rounded-full bg-[var(--public-dark-soft)] px-2.5 py-1 text-xs font-black uppercase tracking-wider text-[var(--public-dark-faint)] tabular-nums">{sport.events.length} {t('events')}</span>
            </div>
            <h2 className="mt-4 text-lg font-black leading-tight tracking-[-.02em]">{sport.name}</h2>
            <div className="mt-3 flex flex-wrap gap-1.5">
                {labels.map(label => <span key={label} className="rounded-md border border-[var(--public-primary-border)] bg-[var(--public-primary-soft)] px-2 py-0.5 text-xs font-bold text-[var(--public-primary)]">{label}</span>)}
            </div>
            {documents.length > 0 ? (
                <ul className="relative z-10 mt-3 space-y-1.5">
                    {documents.map(document => (
                        <li key={document.url}>
                            <a
                                href={document.url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex items-center gap-1.5 text-xs font-bold text-[var(--public-primary)] underline-offset-2 hover:underline"
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