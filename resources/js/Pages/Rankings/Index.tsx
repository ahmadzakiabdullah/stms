import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { BarChart3, Download, Medal, Save, Settings2, Trophy, Users } from 'lucide-react';
import { type PageProps, type RankingEntry, type RankingRules, type Session, type Tournament } from '@/types';
import { useI18n } from '@/lib/i18n';
import { useEffect } from 'react';
import ParticipantLogo from '@/components/ParticipantLogo';
import { Badge } from '@/components/ui/badge';

interface RankingsIndexProps {
    sessions: Session[];
    selectedSession: string | null;
    tournaments: Tournament[];
    selectedTournament: string | null;
    rankings: RankingEntry[];
    events: Record<string, unknown>;
    strategies: Record<string, string>;
}

const rankColors: Record<number, string> = {
    1: 'text-yellow-600 font-bold',
    2: 'text-gray-500 font-bold',
    3: 'text-amber-600 font-bold',
};

export default function RankingsIndex({ sessions, selectedSession, tournaments, selectedTournament, rankings, events, strategies }: RankingsIndexProps) {
    const { t } = useI18n();
    const { flash } = usePage<PageProps>().props;

    const selectedSessionData = sessions.find(s => s.slug === selectedSession);
    const selectedTournamentData = tournaments.find(t => t.slug === selectedTournament);
    const isSessionLevel = !!selectedSession && !selectedTournament;

    const activeRules = selectedTournamentData?.ranking_rules ?? selectedSessionData?.ranking_rules;
    const defaultRules: Required<RankingRules> = {
        points: { win_points: 3, draw_points: 1, loss_points: 0, tiebreakers: ['points', 'goal_difference', 'score_for'] },
        win_rate: { tiebreakers: ['win_rate', 'wins', 'goal_difference'] },
        medal_tally: { tiebreakers: ['gold', 'silver', 'bronze'] },
    };
    const formRules: Required<RankingRules> = {
        points: { ...defaultRules.points, ...activeRules?.points },
        win_rate: { ...defaultRules.win_rate, ...activeRules?.win_rate },
        medal_tally: { ...defaultRules.medal_tally, ...activeRules?.medal_tally },
    };

    const { data, setData, put, processing, errors } = useForm({
        ranking_strategy: selectedSessionData?.ranking_strategy || 'points',
        ranking_rules: formRules,
    });

    useEffect(() => {
        setData({
            ranking_strategy: selectedTournamentData?.ranking_strategy ?? selectedSessionData?.ranking_strategy ?? 'points',
            ranking_rules: formRules,
        });
    }, [selectedSession, selectedTournament]);

    const handleSessionChange = (slug: string) => {
        if (slug) {
            router.get(route('rankings.index', { session: slug }));
        } else {
            router.get(route('rankings.index'));
        }
    };

    const handleTournamentChange = (slug: string) => {
        const params: Record<string, string> = { session: selectedSession || '' };
        if (slug) params.tournament = slug;
        router.get(route('rankings.index', params));
    };

    const updateStrategy = (e: React.FormEvent) => {
        e.preventDefault();
        if (selectedTournamentData) {
            put(route('rankings.updateStrategy', selectedTournamentData.slug));
        } else if (selectedSessionData) {
            put(route('rankings.updateSessionStrategy', selectedSessionData.slug));
        }
    };

    const isMedal = (selectedTournamentData?.ranking_strategy ?? selectedSessionData?.ranking_strategy ?? 'points') === 'medal_tally';
    const isWinRate = (selectedTournamentData?.ranking_strategy ?? selectedSessionData?.ranking_strategy ?? 'points') === 'win_rate';
    const activeStrategy = selectedTournamentData?.ranking_strategy ?? selectedSessionData?.ranking_strategy ?? 'points';
    const leader = rankings[0];
    const totalMatches = rankings.reduce((total, row) => total + row.matches_played, 0) / 2;
    const rankingScope = selectedTournamentData?.name ?? selectedSessionData?.name;

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div className="flex items-center gap-2 text-xs font-medium uppercase tracking-[0.16em] text-muted-foreground">
                            <BarChart3 className="size-4" /> {t('Analytics')}
                        </div>
                        <h1 className="mt-1 text-2xl font-semibold tracking-tight">{t('Rankings')}</h1>
                    </div>
                    {rankingScope && <Badge variant="secondary" className="w-fit">{rankingScope}</Badge>}
                </div>
            }
        >
            <Head title={t('Rankings')} />

            {flash?.success && (
                <div className="mb-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-700">
                    {flash.success}
                </div>
            )}

            {selectedSessionData && (
                <div className="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <Card className="border-primary/20 bg-primary/[0.03]">
                        <CardContent className="flex items-center gap-3 p-4">
                            <span className="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary"><Users className="size-5" /></span>
                            <div><p className="text-xs font-medium text-muted-foreground">{t('Participants')}</p><p className="text-2xl font-semibold">{rankings.length}</p></div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex items-center gap-3 p-4">
                            <span className="flex size-10 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600"><Trophy className="size-5" /></span>
                            <div><p className="text-xs font-medium text-muted-foreground">{t('Current leader')}</p><p className="truncate text-sm font-semibold">{leader?.participant_name ?? '—'}</p></div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex items-center gap-3 p-4">
                            <span className="flex size-10 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600"><BarChart3 className="size-5" /></span>
                            <div><p className="text-xs font-medium text-muted-foreground">{t('Matches counted')}</p><p className="text-2xl font-semibold">{Number.isInteger(totalMatches) ? totalMatches : totalMatches.toFixed(1)}</p></div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex items-center gap-3 p-4">
                            <span className="flex size-10 items-center justify-center rounded-xl bg-violet-500/10 text-violet-600"><Settings2 className="size-5" /></span>
                            <div><p className="text-xs font-medium text-muted-foreground">{t('Strategy')}</p><p className="text-sm font-semibold">{strategies[activeStrategy] || activeStrategy}</p></div>
                        </CardContent>
                    </Card>
                </div>
            )}

            <Card className="mb-6 overflow-hidden">
                <CardHeader>
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <CardTitle>{t('Ranking view')}</CardTitle>
                            <CardDescription className="mt-1">
                                Choose a competition scope, then adjust how standings are calculated.
                            </CardDescription>
                        </div>
                        <Settings2 className="mt-1 hidden size-5 text-muted-foreground sm:block" />
                    </div>
                </CardHeader>
                <CardContent className="space-y-6">
                    <div className="grid gap-4 rounded-xl border bg-muted/20 p-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <label className="text-sm font-medium">{t('Session')}</label>
                            <select
                                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-1.5 text-sm shadow-sm outline-none transition focus:ring-2 focus:ring-ring"
                                value={selectedSession || ''}
                                onChange={(e) => handleSessionChange(e.target.value)}
                            >
                                <option value="">{t('-- Select Session --')}</option>
                                {sessions.map((s) => (
                                    <option key={s.id} value={s.slug}>
                                        {s.name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {tournaments.length > 0 && (
                            <div className="space-y-2">
                                <label className="text-sm font-medium">{t('Tournament (optional)')}</label>
                                <select
                                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-1.5 text-sm shadow-sm outline-none transition focus:ring-2 focus:ring-ring"
                                    value={selectedTournament || ''}
                                    onChange={(e) => handleTournamentChange(e.target.value)}
                                >
                                    <option value="">{t('All Phases (Session Total)')}</option>
                                    {tournaments.map((t) => (
                                        <option key={t.id} value={t.slug}>
                                            {t.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        )}

                        {selectedSessionData && (
                            <form onSubmit={updateStrategy} className="flex flex-wrap items-end gap-3 border-t pt-4 md:col-span-2">
                                <div className="min-w-44">
                                    <label className="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted-foreground">Strategy</label>
                                    <select
                                        className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-1.5 text-sm shadow-sm"
                                        value={data.ranking_strategy}
                                        onChange={(e) => setData('ranking_strategy', e.target.value)}
                                    >
                                        {Object.entries(strategies).map(([key, label]) => (
                                            <option key={key} value={key}>{label}</option>
                                        ))}
                                    </select>
                                </div>
                                {data.ranking_strategy === 'points' && (
                                    <>
                                        {(['win_points', 'draw_points', 'loss_points'] as const).map((field) => (
                                            <div key={field} className="w-24">
                                                <label className="mb-1 block text-xs font-medium text-muted-foreground">
                                                    {field === 'win_points' ? 'Win' : field === 'draw_points' ? 'Draw' : 'Loss'}
                                                </label>
                                                <Input
                                                    type="number"
                                                    min={-100}
                                                    max={100}
                                                    value={data.ranking_rules.points[field]}
                                                    onChange={(event) => setData('ranking_rules', {
                                                        ...data.ranking_rules,
                                                        points: { ...data.ranking_rules.points, [field]: Number(event.target.value) },
                                                    })}
                                                />
                                            </div>
                                        ))}
                                        <div className="min-w-64 flex-1">
                                            <label className="mb-1 block text-xs font-medium text-muted-foreground">Tiebreakers</label>
                                            <Input
                                                value={data.ranking_rules.points.tiebreakers.join(', ')}
                                                onChange={(event) => setData('ranking_rules', {
                                                    ...data.ranking_rules,
                                                    points: {
                                                        ...data.ranking_rules.points,
                                                        tiebreakers: event.target.value.split(',').map((value) => value.trim()).filter(Boolean),
                                                    },
                                                })}
                                                aria-describedby="ranking-rules-help"
                                            />
                                        </div>
                                    </>
                                )}
                                {data.ranking_strategy === 'win_rate' && (
                                    <div className="min-w-64 flex-1">
                                        <label className="mb-1 block text-xs font-medium text-muted-foreground">Tiebreakers</label>
                                        <Input
                                            value={data.ranking_rules.win_rate.tiebreakers.join(', ')}
                                            onChange={(event) => setData('ranking_rules', {
                                                ...data.ranking_rules,
                                                win_rate: { tiebreakers: event.target.value.split(',').map((value) => value.trim()).filter(Boolean) },
                                            })}
                                        />
                                    </div>
                                )}
                                {data.ranking_strategy === 'medal_tally' && (
                                    <div className="min-w-64 flex-1">
                                        <label className="mb-1 block text-xs font-medium text-muted-foreground">Medal order</label>
                                        <Input
                                            value={data.ranking_rules.medal_tally.tiebreakers.join(', ')}
                                            onChange={(event) => setData('ranking_rules', {
                                                ...data.ranking_rules,
                                                medal_tally: { tiebreakers: event.target.value.split(',').map((value) => value.trim()).filter(Boolean) },
                                            })}
                                        />
                                    </div>
                                )}
                                <Button type="submit" size="sm" className="h-10" disabled={processing}>
                                    <Save className="mr-1 size-3.5" /> {processing ? t('Saving...') : t('Apply changes')}
                                </Button>
                            </form>
                        )}
                        {selectedSessionData && (
                            <div className="w-full text-xs text-muted-foreground" id="ranking-rules-help">
                                Allowed fields depend on strategy. Separate tiebreakers with commas.
                                {Object.keys(errors).length > 0 && <span className="ml-2 text-destructive">Check the ranking rule values.</span>}
                            </div>
                        )}
                        {selectedSessionData && (
                            <div className="flex flex-wrap gap-2 border-t pt-4 md:col-span-2">
                                {selectedTournamentData && (
                                    <>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => window.location.href = route('exports.rankings.pdf', selectedTournamentData.slug)}
                                        >
                                            <Download className="mr-1.5 size-3.5" /> PDF
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => window.location.href = route('exports.rankings.excel', selectedTournamentData.slug)}
                                        >
                                            <Download className="mr-1.5 size-3.5" /> Excel
                                        </Button>
                                    </>
                                )}
                            </div>
                        )}
                    </div>
                </CardContent>
            </Card>

            {selectedSession && (
                <Card className="overflow-hidden">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Trophy className="size-5" />
                            Rankings: {selectedSessionData?.name}
                            {selectedTournamentData ? ` — ${selectedTournamentData.name}` : ' (All Phases)'}
                        </CardTitle>
                        <CardDescription>
                            Strategy: {strategies[selectedTournamentData?.ranking_strategy ?? selectedSessionData?.ranking_strategy ?? 'points'] || 'Points'} |
                            Based on {rankings.length} participant(s) with match results
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                        {isMedal ? (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-16">Rank</TableHead>
                                        <TableHead>Participant</TableHead>
                                        <TableHead className="w-20 text-center">
                                            <span className="inline-flex items-center gap-1 text-yellow-600"><Medal className="size-3.5" /> Gold</span>
                                        </TableHead>
                                        <TableHead className="w-20 text-center">
                                            <span className="inline-flex items-center gap-1 text-gray-500"><Medal className="size-3.5" /> Silver</span>
                                        </TableHead>
                                        <TableHead className="w-20 text-center">
                                            <span className="inline-flex items-center gap-1 text-amber-600"><Medal className="size-3.5" /> Bronze</span>
                                        </TableHead>
                                        <TableHead className="w-24 text-center">Total</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rankings.length === 0 && (
                                        <TableRow>
                                            <TableCell colSpan={6} className="text-center text-muted-foreground">
                                                {t('No rankings available. Record match results first.')}
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    {rankings.map((r) => (
                                        <TableRow key={r.participant_id}>
                                            <TableCell>
                                                <span className={`flex items-center gap-1 ${rankColors[r.rank] || ''}`}>
                                                    {r.rank <= 3 && <Medal className="size-4" />}
                                                    {r.rank}
                                                </span>
                                            </TableCell>
                                            <TableCell className="min-w-56 font-medium">
                                                <div className="flex items-center gap-3">
                                                    <ParticipantLogo participant={{ name: r.participant_name, logo_url: r.logo_url, inverse_logo_url: r.inverse_logo_url }} size="sm" />
                                                    <span>{r.participant_name}{r.team_name && <span className="block text-xs font-normal text-muted-foreground">{r.team_name}</span>}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-center font-semibold text-yellow-600">{r.gold ?? 0}</TableCell>
                                            <TableCell className="text-center font-semibold text-gray-500">{r.silver ?? 0}</TableCell>
                                            <TableCell className="text-center font-semibold text-amber-600">{r.bronze ?? 0}</TableCell>
                                            <TableCell className="text-center font-bold">{r.total_medals ?? 0}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-16">Rank</TableHead>
                                        <TableHead>Participant</TableHead>
                                        <TableHead className="text-center">Played</TableHead>
                                        <TableHead className="text-center">W</TableHead>
                                        <TableHead className="text-center">D</TableHead>
                                        <TableHead className="text-center">L</TableHead>
                                        <TableHead className="text-center">GF</TableHead>
                                        <TableHead className="text-center">GA</TableHead>
                                        <TableHead className="text-center">GD</TableHead>
                                        <TableHead className="text-center">
                                            {isWinRate ? 'Win %' : 'Pts'}
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rankings.length === 0 && (
                                        <TableRow>
                                            <TableCell colSpan={10} className="text-center text-muted-foreground">
                                                {t('No rankings available. Record match results first.')}
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    {rankings.map((r) => (
                                        <TableRow key={r.participant_id}>
                                            <TableCell>
                                                <span className={`flex items-center gap-1 ${rankColors[r.rank] || ''}`}>
                                                    {r.rank <= 3 && <Medal className="size-4" />}
                                                    {r.rank}
                                                </span>
                                            </TableCell>
                                            <TableCell className="min-w-56 font-medium">
                                                <div className="flex items-center gap-3">
                                                    <ParticipantLogo participant={{ name: r.participant_name, logo_url: r.logo_url, inverse_logo_url: r.inverse_logo_url }} size="sm" />
                                                    <span>{r.participant_name}{r.team_name && <span className="block text-xs font-normal text-muted-foreground">{r.team_name}</span>}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-center">{r.matches_played}</TableCell>
                                            <TableCell className="text-center text-emerald-600">{r.wins}</TableCell>
                                            <TableCell className="text-center text-yellow-600">{r.draws}</TableCell>
                                            <TableCell className="text-center text-red-600">{r.losses}</TableCell>
                                            <TableCell className="text-center">{r.score_for}</TableCell>
                                            <TableCell className="text-center">{r.score_against}</TableCell>
                                            <TableCell className="text-center font-medium">
                                                {r.goal_difference > 0 ? '+' : ''}{r.goal_difference}
                                            </TableCell>
                                            <TableCell className="text-center font-bold">
                                                {isWinRate
                                                    ? `${r.win_rate}%`
                                                    : r.points}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                        </div>
                    </CardContent>
                </Card>
            )}

            {!selectedSession && (
                <Card>
                    <CardContent className="py-12 text-center text-muted-foreground">
                        <Trophy className="mx-auto mb-4 size-12 opacity-30" />
                        <p>Select a session above to view rankings</p>
                    </CardContent>
                </Card>
            )}

            <div className="mt-6 text-xs text-muted-foreground">
                {t('Rankings are computed from match results using the selected strategy.')}
            </div>
        </AuthenticatedLayout>
    );
}
