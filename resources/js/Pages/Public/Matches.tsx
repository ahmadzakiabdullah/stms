import PublicEmptyState from '@/components/PublicEmptyState';
import PublicLayout from '@/Layouts/PublicLayout';
import PublicMatchCard, { type PublicMatch } from '@/components/PublicMatchCard';
import PublicPageHero from '@/components/PublicPageHero';
import { useI18n } from '@/lib/i18n';
import { Head } from '@inertiajs/react';
import { CalendarDays, CheckCircle2, Clock3, Swords } from 'lucide-react';
import { type ReactNode } from 'react';

type Props = { app_name: string; competition: { name: string; description: string | null; start_date: string | null; end_date: string | null; organization: string | null } | null; upcoming: PublicMatch[]; results: PublicMatch[]; updated_at: string };

const formatDate = (value: string | null, locale: string, withTime = false) => value ? new Intl.DateTimeFormat(locale === 'ms' ? 'ms-MY' : 'en-MY', { day: 'numeric', month: 'short', year: 'numeric', ...(withTime ? { hour: '2-digit', minute: '2-digit' } : {}) }).format(new Date(value)) : null;

export default function PublicMatches({ app_name, competition, upcoming, results, updated_at }: Props) {
    const { t, locale } = useI18n();
    const dateRange = competition?.start_date && `${formatDate(competition.start_date, locale)}${competition.end_date ? ` — ${formatDate(competition.end_date, locale)}` : ''}`;

    return (
        <PublicLayout title={`${t('Matches')} | ${competition?.name || app_name}`} appName={app_name} current="matches">
            <Head><link rel="canonical" href={route('public.matches')} /></Head>
            <main>
                <PublicPageHero eyebrow={competition?.organization || t('Official competition')} title={t('Matches')} intro={`${competition?.name || app_name} — ${t('Follow the official match schedule and latest results.')}`} icon={<Swords className="size-4" />}>
                    {dateRange ? <p className="mt-8 inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white/75"><CalendarDays className="size-4 text-[var(--public-highlight)]" />{dateRange}</p> : null}
                </PublicPageHero>
                <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16">
                    <div className="grid gap-10 lg:grid-cols-2">
                        <MatchSection title={t('Upcoming matches')} icon={<Clock3 className="size-5" />} matches={upcoming} variant="upcoming" t={t} />
                        <MatchSection title={t('Latest results')} icon={<CheckCircle2 className="size-5" />} matches={results} variant="result" t={t} />
                    </div>
                    <p className="mt-10 text-right text-xs text-[var(--public-dark-faint)]">{t('Updated')} {formatDate(updated_at, locale, true)}</p>
                </div>
            </main>
        </PublicLayout>
    );
}

function MatchSection({ title, icon, matches, variant, t }: { title: string; icon: ReactNode; matches: PublicMatch[]; variant: 'upcoming' | 'result'; t: (key: string) => string }) {
    return <section aria-labelledby={title.replace(/\s+/g, '-').toLowerCase()}><div className="mb-5 flex items-center gap-3"><span className="flex size-10 items-center justify-center rounded-xl bg-[var(--public-primary-soft)] text-[var(--public-primary)]">{icon}</span><h2 id={title.replace(/\s+/g, '-').toLowerCase()} className="text-xl font-black">{title}</h2></div><div className="grid gap-4">{matches.map(match => <PublicMatchCard key={match.id} match={match} variant={variant} />)}{matches.length === 0 && <PublicEmptyState text={variant === 'result' ? t('No official results recorded yet.') : t('Schedule will be shown after publication.')} />}</div></section>;
}