import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { router } from '@inertiajs/react';

interface Stats {
    total_fixtures: number;
    completed_fixtures: number;
    pending_fixtures: number;
    in_progress_fixtures: number;
    total_results: number;
    total_participants: number;
    total_registrations: number;
    total_tournaments: number;
}

interface FixturesByStatus {
    status: string;
    count: number;
}

interface RecentResult {
    id: string;
    home: string;
    away: string;
    score: string;
    tournament: string;
    created_at: string;
}

interface ReportsProps {
    stats: Stats;
    fixturesByStatus: FixturesByStatus[];
    recentResults: RecentResult[];
    fixturesByTournament: Record<string, { status: string; count: number }[]>;
}

export default function ReportsIndex({ stats, fixturesByStatus, recentResults }: ReportsProps) {
    const completionRate = stats.total_fixtures > 0
        ? Math.round((stats.completed_fixtures / stats.total_fixtures) * 100)
        : 0;

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-foreground">Reports & Analytics</h2>}
        >
            <Head title="Reports" />

            <div className="space-y-6">
                {/* Stats Grid */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard title="Total Fixtures" value={stats.total_fixtures} />
                    <StatCard title="Completed" value={stats.completed_fixtures} color="text-green-600" />
                    <StatCard title="Pending" value={stats.pending_fixtures} color="text-yellow-600" />
                    <StatCard title="In Progress" value={stats.in_progress_fixtures} color="text-blue-600" />
                    <StatCard title="Results Recorded" value={stats.total_results} />
                    <StatCard title="Participants" value={stats.total_participants} />
                    <StatCard title="Registrations" value={stats.total_registrations} />
                    <StatCard title="Tournaments" value={stats.total_tournaments} />
                </div>

                {/* Completion Rate */}
                <div className="rounded-lg border bg-card p-6 shadow-sm">
                    <h3 className="text-lg font-semibold mb-4">Fixture Completion Rate</h3>
                    <div className="flex items-center gap-4">
                        <div className="flex-1 bg-muted rounded-full h-6 overflow-hidden">
                            <div
                                className="bg-green-500 h-full transition-all"
                                style={{ width: `${completionRate}%` }}
                            />
                        </div>
                        <span className="text-2xl font-bold">{completionRate}%</span>
                    </div>
                    <p className="text-sm text-muted-foreground mt-2">
                        {stats.completed_fixtures} of {stats.total_fixtures} fixtures completed
                    </p>
                </div>

                {/* Fixtures by Status */}
                <div className="rounded-lg border bg-card p-6 shadow-sm">
                    <h3 className="text-lg font-semibold mb-4">Fixtures by Status</h3>
                    <div className="space-y-3">
                        {fixturesByStatus.map((item) => (
                            <div key={item.status} className="flex items-center gap-3">
                                <span className="w-28 text-sm">{item.status}</span>
                                <div className="flex-1 bg-muted rounded-full h-4 overflow-hidden">
                                    <div
                                        className={`h-full rounded-full ${
                                            item.status === 'Completed' ? 'bg-green-500' :
                                            item.status === 'In Progress' ? 'bg-blue-500' :
                                            'bg-yellow-500'
                                        }`}
                                        style={{
                                            width: stats.total_fixtures > 0
                                                ? `${(item.count / stats.total_fixtures) * 100}%`
                                                : '0%',
                                        }}
                                    />
                                </div>
                                <span className="w-10 text-right text-sm font-medium">{item.count}</span>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Recent Results */}
                <div className="rounded-lg border bg-card p-6 shadow-sm">
                    <h3 className="text-lg font-semibold mb-4">Recent Results</h3>
                    {recentResults.length === 0 ? (
                        <p className="text-muted-foreground">No results recorded yet.</p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b">
                                        <th className="py-2 text-left font-medium">Tournament</th>
                                        <th className="py-2 text-left font-medium">Home</th>
                                        <th className="py-2 text-center font-medium">Score</th>
                                        <th className="py-2 text-left font-medium">Away</th>
                                        <th className="py-2 text-right font-medium">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {recentResults.map((r) => (
                                        <tr key={r.id} className="border-b last:border-0">
                                            <td className="py-2">{r.tournament}</td>
                                            <td className="py-2">{r.home}</td>
                                            <td className="py-2 text-center font-mono font-bold">{r.score}</td>
                                            <td className="py-2">{r.away}</td>
                                            <td className="py-2 text-right text-muted-foreground">{r.created_at}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

                {/* Quick Export Links */}
                <div className="rounded-lg border bg-card p-6 shadow-sm">
                    <h3 className="text-lg font-semibold mb-4">Quick Exports</h3>
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <ExportButton
                            label="Fixtures (PDF)"
                            onClick={() => router.visit(route('exports.fixtures.pdf'), { preserveState: true })}
                        />
                        <ExportButton
                            label="Fixtures (Excel)"
                            onClick={() => router.visit(route('exports.fixtures.excel'), { preserveState: true })}
                        />
                        <ExportButton
                            label="Results (PDF)"
                            onClick={() => router.visit(route('exports.results.pdf'), { preserveState: true })}
                        />
                        <ExportButton
                            label="Results (Excel)"
                            onClick={() => router.visit(route('exports.results.excel'), { preserveState: true })}
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function StatCard({ title, value, color }: { title: string; value: number; color?: string }) {
    return (
        <div className="rounded-lg border bg-card p-4 shadow-sm">
            <p className="text-sm text-muted-foreground">{title}</p>
            <p className={`text-2xl font-bold ${color ?? ''}`}>{value}</p>
        </div>
    );
}

function ExportButton({ label, onClick }: { label: string; onClick: () => void }) {
    return (
        <button
            onClick={onClick}
            className="inline-flex items-center justify-center gap-2 rounded-md border border-input bg-background px-4 py-2 text-sm font-medium hover:bg-accent hover:text-accent-foreground transition-colors"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                <polyline points="7 10 12 15 17 10" />
                <line x1="12" x2="12" y1="15" y2="3" />
            </svg>
            {label}
        </button>
    );
}
