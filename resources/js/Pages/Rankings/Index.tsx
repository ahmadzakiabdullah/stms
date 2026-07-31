import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/components/ui/button';
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
import { Medal, Save, Trophy } from 'lucide-react';
import { type PageProps, type RankingEntry, type Session, type Tournament } from '@/types';

interface RankingsIndexProps {
    sessions: Session[];
    selectedSession: string | null;
    tournaments: Tournament[];
    selectedTournament: string | null;
    rankings: RankingEntry[];
    events: Record<string, unknown>;
    strategies: Record<string, string>;
}

const strategyLabels: Record<string, string> = {
    points: 'Points (W=3, D=1, L=0)',
    win_rate: 'Win Rate',
    medal_tally: 'Medal Tally',
};

const rankColors: Record<number, string> = {
    1: 'text-yellow-600 font-bold',
    2: 'text-gray-500 font-bold',
    3: 'text-amber-600 font-bold',
};

export default function RankingsIndex({ sessions, selectedSession, tournaments, selectedTournament, rankings, events, strategies }: RankingsIndexProps) {
    const { flash } = usePage<PageProps>().props;

    const selectedSessionData = sessions.find(s => s.slug === selectedSession);
    const selectedTournamentData = tournaments.find(t => t.slug === selectedTournament);
    const isSessionLevel = !!selectedSession && !selectedTournament;

    const { data, setData, put, processing } = useForm({
        ranking_strategy: selectedSessionData?.ranking_strategy || 'points',
    });

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

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Rankings</h1>
                        <p className="text-sm text-muted-foreground">
                            View calculated rankings from match results
                        </p>
                    </div>
                </div>
            }
        >
            <Head title="Rankings" />

            {flash?.success && (
                <div className="mb-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-700">
                    {flash.success}
                </div>
            )}

            <Card className="mb-6">
                <CardHeader>
                    <CardTitle>Select Session</CardTitle>
                    <CardDescription>
                        A session is one competition (e.g. SAF 2026); its tournaments (e.g. Fasa 1, Fasa 2) can be viewed separately.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="flex flex-wrap items-end gap-4">
                        <div className="flex-1">
                            <label className="text-sm font-medium mb-1 block">Session</label>
                            <select
                                className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                                value={selectedSession || ''}
                                onChange={(e) => handleSessionChange(e.target.value)}
                            >
                                <option value="">-- Select Session --</option>
                                {sessions.map((s) => (
                                    <option key={s.id} value={s.slug}>
                                        {s.name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {tournaments.length > 0 && (
                            <div className="flex-1">
                                <label className="text-sm font-medium mb-1 block">Tournament (optional)</label>
                                <select
                                    className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                                    value={selectedTournament || ''}
                                    onChange={(e) => handleTournamentChange(e.target.value)}
                                >
                                    <option value="">All Phases (Session Total)</option>
                                    {tournaments.map((t) => (
                                        <option key={t.id} value={t.slug}>
                                            {t.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        )}

                        {selectedSessionData && (
                            <form onSubmit={updateStrategy} className="flex items-end gap-2">
                                <div>
                                    <label className="text-sm font-medium mb-1 block">Strategy</label>
                                    <select
                                        className="flex h-9 rounded-md border border-input bg-background px-3 py-1 text-sm"
                                        value={data.ranking_strategy}
                                        onChange={(e) => setData('ranking_strategy', e.target.value)}
                                    >
                                        {Object.entries(strategies).map(([key, label]) => (
                                            <option key={key} value={key}>{label}</option>
                                        ))}
                                    </select>
                                </div>
                                <Button type="submit" size="sm" disabled={processing}>
                                    <Save className="mr-1 size-3" /> Apply
                                </Button>
                            </form>
                        )}
                        {selectedSessionData && (
                            <div className="flex gap-2">
                                {selectedTournamentData && (
                                    <>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => window.location.href = route('exports.rankings.pdf', selectedTournamentData.slug)}
                                        >
                                            PDF
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => window.location.href = route('exports.rankings.excel', selectedTournamentData.slug)}
                                        >
                                            Excel
                                        </Button>
                                    </>
                                )}
                            </div>
                        )}
                    </div>
                </CardContent>
            </Card>

            {selectedSession && (
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Trophy className="size-5" />
                            Rankings: {selectedSessionData?.name}
                            {selectedTournamentData ? ` — ${selectedTournamentData.name}` : ' (All Phases)'}
                        </CardTitle>
                        <CardDescription>
                            Strategy: {strategyLabels[selectedTournamentData?.ranking_strategy ?? selectedSessionData?.ranking_strategy ?? 'points'] || 'Points'} |
                            Based on {rankings.length} participant(s) with match results
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
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
                                                No rankings available. Record match results first.
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
                                            <TableCell className="font-medium">
                                                {r.participant_name}
                                                {r.team_name && <span className="text-muted-foreground ml-1">({r.team_name})</span>}
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
                                                No rankings available. Record match results first.
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
                                            <TableCell className="font-medium">
                                                {r.participant_name}
                                                {r.team_name && <span className="text-muted-foreground ml-1">({r.team_name})</span>}
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
                M5: Basic Ranking Engine. Rankings are computed from match results using the selected strategy.
            </div>
        </AuthenticatedLayout>
    );
}
