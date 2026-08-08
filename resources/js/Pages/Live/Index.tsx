import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { CircleCheck, Clock, LogOut, Radio, Trophy } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useT } from '@/lib/i18n';
import LanguageSwitcher from '@/components/LanguageSwitcher';
import type { PageProps, Participant } from '@/types';

interface LiveMatch {
    id: string;
    event_id: string;
    pool_id: string | null;
    stage: string | null;
    round: number | null;
    match_number: number;
    home_participant_id: string | null;
    away_participant_id: string | null;
    venue: string | null;
    scheduled_at: string | null;
    status: string;
    event?: { id: string; name: string; slug: string; sport?: SportSummary } | null;
    pool?: { id: string; name: string } | null;
    home_participant?: Participant | null;
    away_participant?: Participant | null;
    result?: {
        id: string;
        score_home: number | null;
        score_away: number | null;
        winner_participant_id: string | null;
    } | null;
}

interface Sport {
    id: string;
    name: string;
    slug: string;
}

interface SportSummary {
    id: string;
    name: string;
    slug: string;
}

interface StandingRow {
    participant_id: string;
    played: number;
    won: number;
    drawn: number;
    lost: number;
    goals_for: number;
    goals_against: number;
    goal_difference: number;
    points: number;
    participant?: { id: string; name: string; team_name?: string; logo_url?: string } | null;
}

interface StandingsGroup {
    event: { id: string; name: string; slug: string; sport?: SportSummary | null };
    pools: Array<{ id: string; name: string; rows: StandingRow[] }>;
}

interface LiveIndexProps {
    organization?: { id: string; name: string } | null;
    sports: Sport[];
    events: Array<{ id: string; name: string; slug: string; sport_slug?: string | null }>;
    matches: LiveMatch[];
    standings: StandingsGroup[];
    filters: { sport: string; event: string };
}

type ParticipantSummary = Pick<Participant, 'id' | 'name'> & Partial<Pick<Participant, 'team_name' | 'logo_url'>>;

const participantName = (participant?: ParticipantSummary | null, fallback = 'TBD') => {
    if (!participant) return fallback;

    const code = participant.name?.trim();

    if (code && code.length <= 12) return code;

    return participant.team_name || code || fallback;
};

const participantFullName = (participant?: ParticipantSummary | null, fallback = '') => {
    if (!participant) return fallback;
    return participant.team_name || participant.name || fallback;
};

const participantInitials = (name: string) =>
    name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() || '')
        .join('');

const TeamMark = ({ participant, fallback = 'TBD', size = 'size-9' }: { participant?: ParticipantSummary | null; fallback?: string; size?: string }) => {
    const name = participantName(participant, fallback);

    if (participant?.logo_url) {
        return <img src={participant.logo_url} alt={name} className={`${size} shrink-0 object-contain`} />;
    }

    return (
        <span className={`flex ${size} shrink-0 items-center justify-center rounded-md border bg-muted text-[10px] font-semibold text-muted-foreground`}>
            {participantInitials(name)}
        </span>
    );
};

const formatDateTime = (value: string | null | undefined) =>
    value ? new Date(value).toLocaleString() : 'Time TBD';

const winnerId = (match: LiveMatch) => {
    const result = match.result;
    if (!result) return null;
    if ((result.score_home ?? 0) === (result.score_away ?? 0)) return null;
    return (result.score_home ?? 0) > (result.score_away ?? 0) ? match.home_participant_id : match.away_participant_id;
};

function ResultCard({ match }: { match: LiveMatch }) {
    const t = useT();
    const scored = match.result?.score_home !== null && match.result?.score_home !== undefined;
    const winner = winnerId(match);
    const isDraw = scored && match.result && match.result.score_home === match.result.score_away;

    return (
        <Card className="gap-3 py-4">
            <CardContent className="flex flex-col gap-3 px-4">
                <div className="flex items-start justify-between gap-2">
                    <span className="text-xs font-semibold text-muted-foreground">
                        {match.event?.name || t('Event')}
                    </span>
                    {isDraw && <Badge variant="secondary">{t('Draw')}</Badge>}
                </div>

                <div className="flex items-center justify-center gap-3">
                    <div className="flex min-w-0 flex-1 flex-col items-center gap-1 text-center">
                        <TeamMark participant={match.home_participant} />
                        <span className="max-w-full truncate text-sm font-medium" title={participantFullName(match.home_participant)}>
                            {participantName(match.home_participant)}
                        </span>
                        {winner === match.home_participant_id && (
                            <span className="flex items-center gap-1 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400">
                                <Trophy className="size-3" /> {t('Winner')}
                            </span>
                        )}
                    </div>
                    <span className={`shrink-0 rounded-md bg-muted px-2 py-1 text-lg font-bold tabular-nums ${isDraw ? '' : 'text-primary'}`}>
                        {scored ? `${match.result!.score_home} : ${match.result!.score_away}` : '—'}
                    </span>
                    <div className="flex min-w-0 flex-1 flex-col items-center gap-1 text-center">
                        <TeamMark participant={match.away_participant} />
                        <span className="max-w-full truncate text-sm font-medium" title={participantFullName(match.away_participant)}>
                            {participantName(match.away_participant)}
                        </span>
                        {winner === match.away_participant_id && (
                            <span className="flex items-center gap-1 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400">
                                <Trophy className="size-3" /> {t('Winner')}
                            </span>
                        )}
                    </div>
                </div>

                <div className="flex items-center justify-between border-t pt-2 text-xs text-muted-foreground">
                    <span className="flex items-center gap-1">
                        {match.pool?.name
                            ? t('Pool {{name}}', { name: match.pool.name.replace(/^Pool\s+/i, '') })
                            : match.stage
                              ? t('Knockout')
                              : t('Round {{number}}', { number: match.round || 1 })}
                        <span>·</span>
                        <span className="flex items-center gap-1"><Clock className="size-3" /> {formatDateTime(match.scheduled_at)}</span>
                    </span>
                    {match.venue && <span>{match.venue}</span>}
                </div>
            </CardContent>
        </Card>
    );
}

const standingsLabel = (row: StandingRow) => row.participant?.name ?? 'Unknown';

function StandingsTable({ pool }: { pool: StandingsGroup['pools'][number] }) {
    const t = useT();

    return (
        <div className="overflow-hidden rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow className="bg-muted/50">
                        <TableHead className="w-8">#</TableHead>
                        <TableHead>{t('Team')}</TableHead>
                        <TableHead className="text-center">P</TableHead>
                        <TableHead className="text-center">W</TableHead>
                        <TableHead className="text-center">D</TableHead>
                        <TableHead className="text-center">L</TableHead>
                        <TableHead className="text-center">GF</TableHead>
                        <TableHead className="text-center">GA</TableHead>
                        <TableHead className="text-center">GD</TableHead>
                        <TableHead className="w-12 text-center font-semibold">Pts</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {pool.rows.map((row, index) => {
                        const name = standingsLabel(row);
                        const isLeader = index === 0 && row.points > 0;

                        return (
                            <TableRow key={row.participant_id} className={isLeader ? 'bg-emerald-50/60 dark:bg-emerald-950/20' : ''}>
                                <TableCell className="font-medium">{index + 1}</TableCell>
                                <TableCell>
                                    <div className="flex items-center gap-2">
                                        {row.participant?.logo_url && (
                                            <img src={row.participant.logo_url} alt={name} className="size-5 shrink-0 object-contain" />
                                        )}
                                        <span className="truncate font-medium">{name}</span>
                                        {isLeader && <Badge variant="secondary">{t('Leader')}</Badge>}
                                    </div>
                                </TableCell>
                                <TableCell className="text-center">{row.played}</TableCell>
                                <TableCell className="text-center">{row.won}</TableCell>
                                <TableCell className="text-center">{row.drawn}</TableCell>
                                <TableCell className="text-center">{row.lost}</TableCell>
                                <TableCell className="text-center">{row.goals_for}</TableCell>
                                <TableCell className="text-center">{row.goals_against}</TableCell>
                                <TableCell className={`text-center ${row.goal_difference > 0 ? 'font-medium text-emerald-600 dark:text-emerald-400' : row.goal_difference < 0 ? 'text-destructive' : ''}`}>
                                    {row.goal_difference > 0 ? `+${row.goal_difference}` : row.goal_difference}
                                </TableCell>
                                <TableCell className="text-center font-semibold">{row.points}</TableCell>
                            </TableRow>
                        );
                    })}
                </TableBody>
            </Table>
        </div>
    );
}

export default function LiveIndex({ organization, sports, events, matches, standings, filters }: LiveIndexProps) {
    const { auth, app, settings = {} } = usePage<PageProps>().props;
    const t = useT();
    const user = auth?.user;
    const logoUrl = (settings as Record<string, string>)?.logo_url;
    const [lastUpdated, setLastUpdated] = useState<Date | null>(null);

    const applyFilter = (params: { sport?: string; event?: string }) => {
        const query: Record<string, string> = {};
        if (params.sport !== undefined) query.sport = params.sport;
        if (params.event !== undefined && params.event !== '') query.event = params.event;
        router.get(route('live.index'), query, { preserveScroll: true });
    };

    useEffect(() => {
        const interval = setInterval(() => {
            if (document.hidden) return;
            router.reload({
                only: ['matches', 'standings'],
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => setLastUpdated(new Date()),
            });
        }, 20000);

        return () => clearInterval(interval);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const matchCount = matches.length;
    const standingsCount = standings.reduce((total, group) => total + group.pools.length, 0);

    return (
        <>
            <Head title={t('Live Scores')} />

            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="sticky top-0 z-40 border-b border-border/60 bg-background/80 backdrop-blur-md">
                    <div className="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-4 sm:px-6">
                        <Link href="/" className="flex items-center gap-2.5">
                            {logoUrl ? (
                                <img src={logoUrl} alt={`${app?.name || 'STMS'} logo`} className="size-10 rounded-lg object-contain" />
                            ) : (
                                <div className="flex size-9 items-center justify-center rounded-xl bg-primary shadow-sm">
                                    <Trophy className="size-5 text-primary-foreground" />
                                </div>
                            )}
                            <div className="leading-tight">
                                <span className="block text-sm font-semibold tracking-tight">{app?.name || 'SAF'}</span>
                                <span className="block text-[11px] text-muted-foreground">{t('Tournament Portal')}</span>
                            </div>
                        </Link>

                        <div className="flex items-center gap-2">
                            <LanguageSwitcher />
                            {user ? (
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={route('dashboard')}>{t('Dashboard')}</Link>
                                </Button>
                            ) : (
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={route('login')}>{t('Log in')}</Link>
                                </Button>
                            )}
                        </div>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-6">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <div className="flex items-center gap-2">
                                <span className="flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-400 dark:ring-emerald-900">
                                    <span className="relative flex size-2">
                                        <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75" />
                                        <span className="relative inline-flex size-2 rounded-full bg-emerald-500" />
                                    </span>
                                    LIVE
                                </span>
                                <h1 className="text-2xl font-bold tracking-tight">{t('Live Scores & Results')}</h1>
                            </div>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {organization?.name ? `${organization.name} · ` : ''}
                                {t('{{count}} completed match', { count: matchCount })}
                                {standingsCount > 0 && ` · ${t('{{count}} league table', { count: standingsCount })}`}
                                <span className="ml-1 inline-flex items-center gap-1">
                                    {lastUpdated ? (
                                        <>{`· ${t('Updated {{time}}', { time: lastUpdated.toLocaleTimeString() })}`}</>
                                    ) : (
                                        <>{t('Auto-refreshes every 20s')}</>
                                    )}
                                </span>
                            </p>
                        </div>
                        <Button variant="outline" size="sm" className="gap-1.5" asChild>
                            <Link href={route('results.index')}>
                                <Radio className="size-3.5" />
                                {t('Official Results')}
                            </Link>
                        </Button>
                    </div>

                    {sports.length > 0 && (
                        <div className="mt-6 flex flex-wrap items-center gap-2">
                            <Button
                                variant={!filters.sport ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => applyFilter({ sport: '', event: '' })}
                            >
{t('All Sports')}
                                </Button>
                            {sports.map((sport) => (
                                <Button
                                    key={sport.id}
                                    variant={filters.sport === sport.slug ? 'default' : 'outline'}
                                    size="sm"
                                    onClick={() => applyFilter({ sport: sport.slug, event: '' })}
                                >
                                    {sport.name}
                                </Button>
                            ))}
                        </div>
                    )}

                    {events.length > 0 && (
                        <div className="mt-3 flex flex-wrap items-center gap-2">
                            <span className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{t('Event')}</span>
                            <div className="flex flex-wrap gap-2">
                                <Button
                                    variant={!filters.event ? 'secondary' : 'ghost'}
                                    size="sm"
                                    onClick={() => applyFilter({ event: '' })}
                                >
                                    {t('All Events')}
                                </Button>
                                {events.map((event) => (
                                    <Button
                                        key={event.id}
                                        variant={filters.event === event.slug ? 'default' : 'outline'}
                                        size="sm"
                                        onClick={() => applyFilter({ event: event.slug })}
                                    >
                                        {event.name}
                                    </Button>
                                ))}
                            </div>
                        </div>
                    )}

                    <section className="mt-8">
                        <div className="flex items-center gap-2">
                            <CircleCheck className="size-4 text-primary" />
                            <h2 className="text-lg font-semibold tracking-tight">{t('Latest Results')}</h2>
                        </div>
                        {matchCount === 0 ? (
                            <Card className="mt-3">
                                <CardHeader>
                                    <CardTitle>{t('No results yet')}</CardTitle>
                                    <CardDescription>
                                        {t('Completed matches with recorded scores will appear here as soon as they are available.')}
                                    </CardDescription>
                                </CardHeader>
                            </Card>
                        ) : (
                            <div className="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                {matches.map((match) => (
                                    <ResultCard key={match.id} match={match} />
                                ))}
                            </div>
                        )}
                    </section>

                    {standings.length > 0 && (
                        <section className="mt-10">
                            <div className="flex items-center gap-2">
                                <Trophy className="size-4 text-primary" />
                                <h2 className="text-lg font-semibold tracking-tight">{t('League Standings')}</h2>
                            </div>
                            <div className="mt-3 space-y-6">
                                {standings.map((group) => (
                                    <Card key={group.event.id}>
                                        <CardHeader className="pb-3">
                                            <CardTitle className="flex items-center gap-2 text-base">
                                                {group.event.name}
                                                {group.event.sport?.name && <Badge variant="outline">{group.event.sport.name}</Badge>}
                                            </CardTitle>
                                            <CardDescription>{t('{{count}} pool', { count: group.pools.length })}</CardDescription>
                                        </CardHeader>
                                        <CardContent className="space-y-4">
                                            {group.pools.map((pool) => (
                                                <div key={pool.id} className="space-y-2">
                                                    <div className="text-sm font-semibold">{pool.name}</div>
                                                    <StandingsTable pool={pool} />
                                                </div>
                                            ))}
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        </section>
                    )}
                </main>

                <footer className="border-t border-border/60 bg-muted/30">
                    <div className="mx-auto flex w-full max-w-6xl flex-col items-center justify-between gap-3 px-4 py-6 sm:flex-row sm:px-6">
                        <div className="flex items-center gap-2">
                            <Radio className="size-4 text-muted-foreground" />
                            <span className="text-sm font-medium">{app?.name || 'SAF'} · {t('Live Scores')}</span>
                        </div>
                        <div className="flex items-center gap-4 text-xs text-muted-foreground">
                            <Link href="/" className="transition hover:text-foreground">{t('Home')}</Link>
                            {user ? (
                                <Link href={route('dashboard')} className="transition hover:text-foreground">{t('Dashboard')}</Link>
                            ) : (
                                <Link href={route('login')} className="transition hover:text-foreground">{t('Log in')}</Link>
                            )}
                            {user && (
                                <Link href={route('logout')} method="post" as="button" className="flex items-center gap-1 transition hover:text-foreground">
                                    <LogOut className="size-3" /> {t('Logout')}
                                </Link>
                            )}
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}