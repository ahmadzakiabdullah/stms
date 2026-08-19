import ParticipantLogo from '@/components/ParticipantLogo';
import PublicEmptyState from '@/components/PublicEmptyState';
import PublicLayout from '@/Layouts/PublicLayout';
import PublicMatchCard, { type PublicMatch } from '@/components/PublicMatchCard';
import PublicPageHero from '@/components/PublicPageHero';
import { useI18n } from '@/lib/i18n';
import { Head } from '@inertiajs/react';
import { CalendarDays, MapPin, Medal, Radio, Trophy, Users } from 'lucide-react';
import { type ComponentType } from 'react';

type Team = { name: string; logo_url: string | null; inverse_logo_url: string | null } | null;
type Props = { section: 'sports' | 'schedule' | 'results' | 'faculties' | 'venues' | 'live'; app_name: string; competition: { name: string; description: string | null; organization: string | null } | null; sports_catalog: { name: string; events: string[] }[]; upcoming: PublicMatch[]; results: PublicMatch[]; faculties: Team[]; venues: string[] };

const labels: Record<Props['section'], { title: string; intro: string; icon: ComponentType<{ className?: string }> }> = {
    sports: { title: 'Sports Programme', intro: 'Explore the official sports and events in this competition.', icon: Trophy },
    schedule: { title: 'Competition Schedule', intro: 'Find upcoming fixtures by sport, event, venue and time.', icon: CalendarDays },
    results: { title: 'Results', intro: 'View the latest official results from the competition.', icon: Medal },
    faculties: { title: 'Faculties & Contingents', intro: 'Meet the faculties taking part in the competition.', icon: Users },
    venues: { title: 'Venues', intro: 'Competition locations and venues used for official fixtures.', icon: MapPin },
    live: { title: 'Live Updates', intro: 'Follow fixtures that are currently in progress.', icon: Radio },
};

export default function PublicDirectory({ section, app_name, competition, sports_catalog, upcoming, results, faculties, venues }: Props) {
    const { t } = useI18n();
    const meta = labels[section];
    const Icon = meta.icon;
    const live = upcoming.filter(match => match.status === 'in_progress');
    const matches = section === 'results' ? results : section === 'live' ? live : upcoming;
    const variant = section === 'results' ? 'result' : section === 'live' ? 'live' : 'upcoming';

    return (
        <PublicLayout title={`${t(meta.title)} | ${competition?.name || app_name}`} appName={app_name} current={section === 'sports' || section === 'schedule' || section === 'results' ? section : undefined}>
            <Head><link rel="canonical" href={route(`public.${section}`)} /></Head>
            <main>
                <PublicPageHero eyebrow={competition?.organization || t('Official competition')} title={t(meta.title)} intro={t(meta.intro)} icon={<Icon className="size-4" />} />
                <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16">
                    <DirectoryContent section={section} sports_catalog={sports_catalog} matches={matches} faculties={faculties} venues={venues} variant={variant} t={t} />
                </div>
            </main>
        </PublicLayout>
    );
}

function DirectoryContent({ section, sports_catalog, matches, faculties, venues, variant, t }: { section: Props['section']; sports_catalog: Props['sports_catalog']; matches: PublicMatch[]; faculties: Team[]; venues: string[]; variant: 'upcoming' | 'result' | 'live'; t: (key: string) => string }) {
    if (section === 'sports') return <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">{sports_catalog.map(sport => <article key={sport.name} className="rounded-2xl border border-[var(--public-dark-border)] bg-white p-6 shadow-sm"><Trophy className="size-6 text-[var(--public-primary)]" /><h2 className="mt-4 text-xl font-black">{sport.name}</h2><p className="mt-2 text-sm text-[var(--public-dark-faint)]">{sport.events.length} {t('events')}</p><ul className="mt-4 space-y-2 border-t border-[var(--public-dark-border)] pt-4 text-sm text-[var(--public-dark-faint)]">{sport.events.map(event => <li key={event}>• {event}</li>)}</ul></article>)}</div>;
    if (section === 'faculties') return <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">{faculties.map((faculty, index) => <article key={`${faculty?.name}-${index}`} className="rounded-2xl border border-[var(--public-dark-border)] bg-white p-5 text-center shadow-sm"><div className="flex justify-center"><ParticipantLogo participant={faculty} size="xl" /></div><h2 className="mt-4 text-sm font-black">{faculty?.name || 'TBC'}</h2></article>)}</div>;
    if (section === 'venues') return <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">{venues.map(venue => <article key={venue} className="rounded-2xl border border-[var(--public-dark-border)] bg-white p-6 shadow-sm"><MapPin className="size-6 text-[var(--public-primary)]" /><h2 className="mt-4 text-lg font-black">{venue}</h2><p className="mt-2 text-sm text-[var(--public-dark-faint)]">{t('Official competition venue')}</p></article>)}</div>;
    return <div className="grid gap-4">{matches.map(match => <PublicMatchCard key={match.id} match={match} variant={variant} />)}{matches.length === 0 && <PublicEmptyState text={section === 'live' ? t('No matches are live right now.') : section === 'results' ? t('No official results recorded yet.') : t('Schedule will be shown after publication.')} />}</div>;
}