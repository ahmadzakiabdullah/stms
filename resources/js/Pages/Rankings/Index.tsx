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
import { type PageProps, type Tournament, type RankingEntry } from '@/types';

interface RankingsIndexProps {
    tournaments: Tournament[];
    rankings: RankingEntry[];
    selectedTournament: string | null;
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

export default function RankingsIndex({ tournaments, rankings, selectedTournament, events, strategies }: RankingsIndexProps) {
    const { flash } = usePage<PageProps>().props;

    const selectedTournamentData = tournaments.find(t => t.slug === selectedTournament);

    const { data, setData, put, processing } = useForm({
        ranking_strategy: selectedTournamentData?.ranking_strategy || 'points',
    });

    const handleTournamentChange = (slug: string) => {
        if (slug) {
            router.get(route('rankings.index', { tournament: slug }));
        } else {
            router.get(route('rankings.index'));
        }
    };

    const updateStrategy = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedTournamentData) return;
        put(route('rankings.updateStrategy', selectedTournamentData.slug));
    };

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
                    <CardTitle>Select Tournament</CardTitle>
                    <CardDescription>
                        Choose a tournament to view its rankings
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="flex items-end gap-4">
                        <div className="flex-1">
                            <label className="text-sm font-medium mb-1 block">Tournament</label>
                            <select
                                className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                                value={selectedTournament || ''}
                                onChange={(e) => handleTournamentChange(e.target.value)}
                            >
                                <option value="">-- Select Tournament --</option>
                                {tournaments.map((t) => (
                                    <option key={t.id} value={t.slug}>
                                        {t.name} {t.session ? `(${t.session.name})` : ''}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {selectedTournamentData && (
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
                        {selectedTournamentData && (
                            <div className="flex gap-2">
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
                            </div>
                        )}
                    </div>
                </CardContent>
            </Card>

            {selectedTournament && (
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Trophy className="size-5" />
                            Rankings: {selectedTournamentData?.name}
                        </CardTitle>
                        <CardDescription>
                            Strategy: {strategyLabels[selectedTournamentData?.ranking_strategy || ''] || 'Points'} |
                            Based on {rankings.length} participant(s) with match results
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
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
                                        {selectedTournamentData?.ranking_strategy === 'win_rate' ? 'Win %' : 'Pts'}
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
                                            {selectedTournamentData?.ranking_strategy === 'win_rate'
                                                ? `${r.win_rate}%`
                                                : r.points}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            )}

            {!selectedTournament && (
                <Card>
                    <CardContent className="py-12 text-center text-muted-foreground">
                        <Trophy className="mx-auto mb-4 size-12 opacity-30" />
                        <p>Select a tournament above to view rankings</p>
                    </CardContent>
                </Card>
            )}

            <div className="mt-6 text-xs text-muted-foreground">
                M5: Basic Ranking Engine. Rankings are computed from match results using the selected strategy.
            </div>
        </AuthenticatedLayout>
    );
}
