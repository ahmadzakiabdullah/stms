import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { EmptyState } from '@/components/EmptyState';
import { PageHeader } from '@/components/PageHeader';
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
import { Head, router, useForm } from '@inertiajs/react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ArrowDown, ArrowUp, BarChart3, Download, Medal, Plus, Save, Settings2, Trophy, Users, X } from 'lucide-react';
import { type RankingEntry, type RankingRules, type Session, type Tournament } from '@/types';
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

type RankingStrategyKey = 'points' | 'win_rate' | 'medal_tally';
type TieBreakerOption = { value: string; label: string };

const tieBreakerOptions: Record<RankingStrategyKey, TieBreakerOption[]> = {
    points: [
        { value: 'points', label: 'Points' },
        { value: 'goal_difference', label: 'Goal difference' },
        { value: 'score_for', label: 'Score for' },
        { value: 'wins', label: 'Wins' },
    ],
    win_rate: [
        { value: 'win_rate', label: 'Win rate' },
        { value: 'wins', label: 'Wins' },
        { value: 'goal_difference', label: 'Goal difference' },
        { value: 'score_for', label: 'Score for' },
    ],
    medal_tally: [
        { value: 'gold', label: 'Gold' },
        { value: 'silver', label: 'Silver' },
        { value: 'bronze', label: 'Bronze' },
    ],
};

const strategyKeys = Object.keys(tieBreakerOptions) as RankingStrategyKey[];
const isRankingStrategyKey = (value: string): value is RankingStrategyKey => strategyKeys.includes(value as RankingStrategyKey);
const optionLabel = (strategy: RankingStrategyKey, value: string) => tieBreakerOptions[strategy].find((option) => option.value === value)?.label ?? value;

export default function RankingsIndex({ sessions, selectedSession, tournaments, selectedTournament, rankings, events, strategies }: RankingsIndexProps) {
    const { t } = useI18n();

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

    const selectedStrategy = isRankingStrategyKey(data.ranking_strategy) ? data.ranking_strategy : 'points';
    const selectedTiebreakers = data.ranking_rules[selectedStrategy].tiebreakers;
    const availableTiebreakers = tieBreakerOptions[selectedStrategy].filter((option) => !selectedTiebreakers.includes(option.value));
    const maxTiebreakers = selectedStrategy === 'medal_tally' ? 3 : 4;

    const updateTiebreakers = (strategy: RankingStrategyKey, tiebreakers: string[]) => {
        setData('ranking_rules', {
            ...data.ranking_rules,
            [strategy]: {
                ...data.ranking_rules[strategy],
                tiebreakers,
            },
        });
    };

    const moveTiebreaker = (strategy: RankingStrategyKey, index: number, direction: -1 | 1) => {
        const nextIndex = index + direction;
        const tiebreakers = [...data.ranking_rules[strategy].tiebreakers];

        if (nextIndex < 0 || nextIndex >= tiebreakers.length) {
            return;
        }

        [tiebreakers[index], tiebreakers[nextIndex]] = [tiebreakers[nextIndex], tiebreakers[index]];
        updateTiebreakers(strategy, tiebreakers);
    };

    const removeTiebreaker = (strategy: RankingStrategyKey, value: string) => {
        updateTiebreakers(strategy, data.ranking_rules[strategy].tiebreakers.filter((field) => field !== value));
    };

    const addTiebreaker = (strategy: RankingStrategyKey, value: string) => {
        if (!value || data.ranking_rules[strategy].tiebreakers.includes(value)) {
            return;
        }

        updateTiebreakers(strategy, [...data.ranking_rules[strategy].tiebreakers, value].slice(0, maxTiebreakers));
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
                <PageHeader
                    title={t('Rankings')}
                    description={t('Analytics')}
                    actions={rankingScope && <Badge variant="secondary" className="w-fit">{rankingScope}</Badge>}
                />
            }
        >
            <Head title={t('Rankings')} />

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
                            <Select
                                value={selectedSession || 'all'}
                                onValueChange={(value) => handleSessionChange(value === 'all' ? '' : value)}
                            >
                                <SelectTrigger className="h-10 w-full">
                                    <SelectValue placeholder={t('-- Select Session --')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">{t('-- Select Session --')}</SelectItem>
                                    {sessions.map((s) => (
                                        <SelectItem key={s.id} value={s.slug}>
                                            {s.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        {tournaments.length > 0 && (
                            <div className="space-y-2">
                                <label className="text-sm font-medium">{t('Tournament (optional)')}</label>
                                <Select
                                    value={selectedTournament || 'all'}
                                    onValueChange={(value) => handleTournamentChange(value === 'all' ? '' : value)}
                                >
                                    <SelectTrigger className="h-10 w-full">
                                        <SelectValue placeholder={t('All Phases (Session Total)')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">{t('All Phases (Session Total)')}</SelectItem>
                                        {tournaments.map((t) => (
                                            <SelectItem key={t.id} value={t.slug}>
                                                {t.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        )}

                        {selectedSessionData && (
                            <form onSubmit={updateStrategy} className="flex flex-wrap items-end gap-3 border-t pt-4 md:col-span-2">
                                <div className="min-w-44">
                                    <label className="mb-1 block text-xs font-semibold uppercase tracking-wide text-muted-foreground">Strategy</label>
                                    <Select
                                        value={data.ranking_strategy}
                                        onValueChange={(value) => setData('ranking_strategy', value)}
                                    >
                                        <SelectTrigger className="h-10 w-full">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {Object.entries(strategies).map(([key, label]) => (
                                                <SelectItem key={key} value={key}>{label}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                {selectedStrategy === 'points' && (
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
                                    </>
                                )}
                                <div className="min-w-72 flex-1 space-y-2">
                                    <label className="mb-1 block text-xs font-medium text-muted-foreground">
                                        {selectedStrategy === 'medal_tally' ? 'Medal order' : 'Tiebreakers'}
                                    </label>
                                    <div className="flex flex-wrap gap-2">
                                        {selectedTiebreakers.map((field, index) => (
                                            <Badge key={field} variant="secondary" className="gap-1.5 rounded-md px-2 py-1">
                                                <span className="text-[11px] text-muted-foreground">{index + 1}</span>
                                                <span>{optionLabel(selectedStrategy, field)}</span>
                                                <button
                                                    type="button"
                                                    className="rounded-sm p-0.5 text-muted-foreground hover:bg-background hover:text-foreground disabled:pointer-events-none disabled:opacity-40"
                                                    onClick={() => moveTiebreaker(selectedStrategy, index, -1)}
                                                    disabled={index === 0}
                                                    aria-label={`Move ${optionLabel(selectedStrategy, field)} up`}
                                                >
                                                    <ArrowUp className="size-3" />
                                                </button>
                                                <button
                                                    type="button"
                                                    className="rounded-sm p-0.5 text-muted-foreground hover:bg-background hover:text-foreground disabled:pointer-events-none disabled:opacity-40"
                                                    onClick={() => moveTiebreaker(selectedStrategy, index, 1)}
                                                    disabled={index === selectedTiebreakers.length - 1}
                                                    aria-label={`Move ${optionLabel(selectedStrategy, field)} down`}
                                                >
                                                    <ArrowDown className="size-3" />
                                                </button>
                                                <button
                                                    type="button"
                                                    className="rounded-sm p-0.5 text-muted-foreground hover:bg-background hover:text-foreground disabled:pointer-events-none disabled:opacity-40"
                                                    onClick={() => removeTiebreaker(selectedStrategy, field)}
                                                    disabled={selectedTiebreakers.length <= 1}
                                                    aria-label={`Remove ${optionLabel(selectedStrategy, field)}`}
                                                >
                                                    <X className="size-3" />
                                                </button>
                                            </Badge>
                                        ))}
                                    </div>
                                    <div className="flex max-w-sm gap-2">
                                        <Select
                                            value=""
                                            onValueChange={(value) => addTiebreaker(selectedStrategy, value)}
                                            disabled={availableTiebreakers.length === 0 || selectedTiebreakers.length >= maxTiebreakers}
                                        >
                                            <SelectTrigger className="h-9">
                                                <SelectValue placeholder="Add field" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {availableTiebreakers.map((option) => (
                                                    <SelectItem key={option.value} value={option.value}>
                                                        {option.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="icon"
                                            className="h-9 w-9 shrink-0"
                                            disabled={availableTiebreakers.length === 0 || selectedTiebreakers.length >= maxTiebreakers}
                                            aria-label="Add next available tiebreaker"
                                            onClick={() => availableTiebreakers[0] && addTiebreaker(selectedStrategy, availableTiebreakers[0].value)}
                                        >
                                            <Plus className="size-4" />
                                        </Button>
                                    </div>
                                </div>
                                <Button type="submit" size="sm" className="h-10" disabled={processing}>
                                    <Save className="mr-1 size-3.5" /> {processing ? t('Saving...') : t('Apply changes')}
                                </Button>
                            </form>
                        )}
                        {selectedSessionData && (
                            <div className="w-full text-xs text-muted-foreground" id="ranking-rules-help">
                                Arrange tie-breakers in priority order. Each field can only be used once.
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
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => window.location.href = route('exports.medals.pdf', selectedSessionData.slug)}
                                >
                                    <Download className="mr-1.5 size-3.5" /> <Medal className="size-3.5" /> Medals PDF
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => window.location.href = route('exports.medals.excel', selectedSessionData.slug)}
                                >
                                    <Download className="mr-1.5 size-3.5" /> <Medal className="size-3.5" /> Medals Excel
                                </Button>
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
                <EmptyState icon={Trophy} title="Select a session above to view rankings" />
            )}

            <div className="mt-6 text-xs text-muted-foreground">
                {t('Rankings are computed from match results using the selected strategy.')}
            </div>
        </AuthenticatedLayout>
    );
}
