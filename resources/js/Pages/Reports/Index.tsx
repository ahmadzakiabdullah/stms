import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { formatDateTime, formatNumber, useI18n } from '@/lib/i18n';
import { AlertTriangle, Archive, CheckCircle2, DatabaseZap, Download, Scale, ServerCog, ShieldCheck } from 'lucide-react';
import axios from 'axios';
import { useEffect, useState } from 'react';

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

interface OperationsSummary {
    status: string;
    queue: { pending: number; failed: number; status: string };
    data_freshness: { last_match_update: string | null; last_result_update: string | null };
    active_sessions: { domain: number; application: number | null };
    incident_signal: string;
}

interface DataQualityCheck {
    key: string;
    label: string;
    count: number;
    severity: 'ok' | 'warning' | 'error';
    description: string;
}

interface DataQualitySummary {
    status: string;
    total_issues: number;
    checks: DataQualityCheck[];
}

interface GovernanceSummary {
    comparison: {
        window_days: number;
        fixtures_completed_current: number;
        fixtures_completed_previous: number;
        results_recorded_current: number;
        results_recorded_previous: number;
        completion_delta: number;
        results_delta: number;
    };
    exports: {
        window_days: number;
        total: number;
        completed: number;
        failed: number;
        running: number;
        last_export_at: string | null;
        policy: string;
    };
    retention: {
        backup_retention_days: number;
        transfer_retention_days: number;
        archive_owner: string;
        policy: string;
    };
    ownership: { area: string; owner: string; access: string }[];
}

interface ReportsProps {
    stats: Stats;
    fixturesByStatus: FixturesByStatus[];
    recentResults: RecentResult[];
    fixturesByTournament: Record<string, { status: string; count: number }[]>;
    operations: OperationsSummary;
    dataQuality: DataQualitySummary;
    governance: GovernanceSummary;
}

interface TransferStatus {
    id: string;
    type: string;
    status: 'pending' | 'running' | 'completed' | 'completed_with_errors' | 'failed';
    progress: number;
    processed: number;
    total: number | null;
    failure_report: string[];
    download_url: string | null;
}

export default function ReportsIndex({ stats, fixturesByStatus, recentResults, operations, dataQuality, governance }: ReportsProps) {
    const { locale, t } = useI18n();
    const [queuedExport, setQueuedExport] = useState<TransferStatus | null>(null);
    const [queueError, setQueueError] = useState<string | null>(null);
    const [queueingType, setQueueingType] = useState<string | null>(null);
    const completionRate = stats.total_fixtures > 0
        ? Math.round((stats.completed_fixtures / stats.total_fixtures) * 100)
        : 0;

    useEffect(() => {
        if (!queuedExport || ['completed', 'completed_with_errors', 'failed'].includes(queuedExport.status)) {
            return;
        }

        const timer = window.setInterval(async () => {
            try {
                const response = await axios.get<{ data: TransferStatus }>(route('data-transfers.show', queuedExport.id));
                setQueuedExport(response.data.data);
            } catch {
                setQueueError(t('Unable to refresh export status.'));
                window.clearInterval(timer);
            }
        }, 2000);

        return () => window.clearInterval(timer);
    }, [queuedExport?.id, queuedExport?.status]);

    const queueExport = async (type: 'fixtures' | 'results') => {
        setQueueError(null);
        setQueueingType(type);

        try {
            const key = `reports:${type}:${Date.now()}:${crypto.randomUUID?.() ?? Math.random().toString(36).slice(2)}`;
            const response = await axios.post<{ data: TransferStatus }>(route('exports.queue'), {
                type,
                idempotency_key: key,
            });
            setQueuedExport(response.data.data);
        } catch (error) {
            const message = axios.isAxiosError(error)
                ? (error.response?.data?.message ?? t('Unable to queue export.'))
                : t('Unable to queue export.');
            setQueueError(message);
        } finally {
            setQueueingType(null);
        }
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-foreground">{t('Reports & Analytics')}</h2>}
        >
            <Head title={t('Reports')} />

            <div className="space-y-6">
                {/* Stats Grid */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard title={t('Total Fixtures')} value={stats.total_fixtures} />
                    <StatCard title={t('Completed')} value={stats.completed_fixtures} color="text-green-600" />
                    <StatCard title={t('Pending')} value={stats.pending_fixtures} color="text-yellow-600" />
                    <StatCard title={t('In Progress')} value={stats.in_progress_fixtures} color="text-blue-600" />
                    <StatCard title={t('Results Recorded')} value={stats.total_results} />
                    <StatCard title={t('Participants')} value={stats.total_participants} />
                    <StatCard title={t('Registrations')} value={stats.total_registrations} />
                    <StatCard title={t('Tournaments')} value={stats.total_tournaments} />
                </div>

                <div className="grid grid-cols-1 gap-4 xl:grid-cols-2">
                    <div className="rounded-lg border bg-card p-6 shadow-sm">
                        <div className="mb-4 flex items-start justify-between gap-4">
                            <div>
                                <h3 className="text-lg font-semibold">{t('Operations Monitor')}</h3>
                                <p className="mt-1 text-sm text-muted-foreground">{operations.incident_signal}</p>
                            </div>
                            <StatusBadge status={operations.status === 'ok' ? 'ok' : 'warning'} label={operations.status === 'ok' ? t('Healthy') : t('Attention')} />
                        </div>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <MetricTile icon={ServerCog} label={t('Queue Pending')} value={operations.queue.pending} hint={t(operations.queue.status)} />
                            <MetricTile icon={AlertTriangle} label={t('Failed Jobs')} value={operations.queue.failed} hint={operations.queue.failed === 0 ? t('Clear') : t('Needs review')} tone={operations.queue.failed > 0 ? 'error' : 'default'} />
                            <MetricTile icon={DatabaseZap} label={t('Active Sessions')} value={operations.active_sessions.domain} hint={operations.active_sessions.application === null ? t('App session table unavailable') : `${formatNumber(operations.active_sessions.application, locale)} ${t('app sessions')}`} />
                            <MetricTile icon={CheckCircle2} label={t('Data Freshness')} value={operations.data_freshness.last_result_update ? t('Updated') : t('No results')} hint={operations.data_freshness.last_result_update ? formatDateTime(operations.data_freshness.last_result_update, locale) : (operations.data_freshness.last_match_update ? formatDateTime(operations.data_freshness.last_match_update, locale) : t('No match updates'))} />
                        </div>
                    </div>

                    <div className="rounded-lg border bg-card p-6 shadow-sm">
                        <div className="mb-4 flex items-start justify-between gap-4">
                            <div>
                                <h3 className="text-lg font-semibold">{t('Data Quality')}</h3>
                                <p className="mt-1 text-sm text-muted-foreground">{t('Duplicate, parent relation, result and timeline checks for the current organization.')}</p>
                            </div>
                            <StatusBadge status={dataQuality.status === 'ok' ? 'ok' : 'warning'} label={dataQuality.total_issues === 0 ? t('Clean') : `${formatNumber(dataQuality.total_issues, locale)} ${t('issues')}`} />
                        </div>
                        <div className="divide-y rounded-md border">
                            {dataQuality.checks.map((check) => (
                                <div key={check.key} className="grid gap-2 p-3 sm:grid-cols-[1fr_auto] sm:items-center">
                                    <div>
                                        <p className="font-medium">{t(check.label)}</p>
                                        <p className="text-sm text-muted-foreground">{t(check.description)}</p>
                                    </div>
                                    <StatusBadge status={check.severity} label={formatNumber(check.count, locale)} />
                                </div>
                            ))}
                            {dataQuality.checks.length === 0 && <p className="p-3 text-sm text-muted-foreground">{t('No data quality checks available.')}</p>}
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-4 xl:grid-cols-2">
                    <div className="rounded-lg border bg-card p-6 shadow-sm">
                        <div className="mb-4 flex items-start justify-between gap-4">
                            <div>
                                <h3 className="text-lg font-semibold">{t('Report Comparison')}</h3>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {t('Current 7-day activity compared with the previous 7 days.')}
                                </p>
                            </div>
                            <Scale className="size-5 text-muted-foreground" />
                        </div>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <ComparisonTile
                                label={t('Completed fixtures')}
                                current={governance.comparison.fixtures_completed_current}
                                previous={governance.comparison.fixtures_completed_previous}
                                delta={governance.comparison.completion_delta}
                                locale={locale}
                            />
                            <ComparisonTile
                                label={t('Recorded results')}
                                current={governance.comparison.results_recorded_current}
                                previous={governance.comparison.results_recorded_previous}
                                delta={governance.comparison.results_delta}
                                locale={locale}
                            />
                        </div>
                    </div>

                    <div className="rounded-lg border bg-card p-6 shadow-sm">
                        <div className="mb-4 flex items-start justify-between gap-4">
                            <div>
                                <h3 className="text-lg font-semibold">{t('Export Governance')}</h3>
                                <p className="mt-1 text-sm text-muted-foreground">{t(governance.exports.policy)}</p>
                            </div>
                            <ShieldCheck className="size-5 text-muted-foreground" />
                        </div>
                        <div className="grid gap-3 sm:grid-cols-4">
                            <MetricTile icon={Download} label={t('30-day exports')} value={governance.exports.total} hint={governance.exports.last_export_at ? formatDateTime(governance.exports.last_export_at, locale) : t('No exports')} />
                            <MetricTile icon={CheckCircle2} label={t('Completed')} value={governance.exports.completed} hint={t('Ready or completed with errors')} />
                            <MetricTile icon={AlertTriangle} label={t('Failed')} value={governance.exports.failed} hint={governance.exports.failed === 0 ? t('Clear') : t('Needs review')} tone={governance.exports.failed > 0 ? 'error' : 'default'} />
                            <MetricTile icon={ServerCog} label={t('Running')} value={governance.exports.running} hint={t('Pending or processing')} />
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-4 xl:grid-cols-2">
                    <div className="rounded-lg border bg-card p-6 shadow-sm">
                        <div className="mb-4 flex items-start justify-between gap-4">
                            <div>
                                <h3 className="text-lg font-semibold">{t('Retention & Archive Policy')}</h3>
                                <p className="mt-1 text-sm text-muted-foreground">{t(governance.retention.policy)}</p>
                            </div>
                            <Archive className="size-5 text-muted-foreground" />
                        </div>
                        <div className="grid gap-3 sm:grid-cols-3">
                            <MetricTile icon={Archive} label={t('Backup retention')} value={`${governance.retention.backup_retention_days}d`} hint={t('Configured backup window')} />
                            <MetricTile icon={Download} label={t('Transfer files')} value={`${governance.retention.transfer_retention_days}d`} hint={t('Operational export/import files')} />
                            <MetricTile icon={ShieldCheck} label={t('Archive owner')} value={governance.retention.archive_owner} hint={t('Accountable role')} />
                        </div>
                    </div>

                    <div className="rounded-lg border bg-card p-6 shadow-sm">
                        <h3 className="mb-4 text-lg font-semibold">{t('Data Ownership')}</h3>
                        <div className="divide-y rounded-md border">
                            {governance.ownership.map((item) => (
                                <div key={item.area} className="grid gap-2 p-3 sm:grid-cols-[minmax(0,0.7fr)_minmax(0,1fr)]">
                                    <div>
                                        <p className="font-medium">{t(item.area)}</p>
                                        <p className="text-xs text-muted-foreground">{t(item.owner)}</p>
                                    </div>
                                    <p className="text-sm text-muted-foreground">{t(item.access)}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Completion Rate */}
                <div className="rounded-lg border bg-card p-6 shadow-sm">
                    <h3 className="text-lg font-semibold mb-4">{t('Fixture Completion Rate')}</h3>
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
                    <h3 className="text-lg font-semibold mb-4">{t('Fixtures by Status')}</h3>
                    <div className="space-y-3">
                        {fixturesByStatus.map((item) => (
                            <div key={item.status} className="flex items-center gap-3">
                                <span className="w-28 text-sm">{t(item.status)}</span>
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
                    <h3 className="text-lg font-semibold mb-4">{t('Recent Results')}</h3>
                    {recentResults.length === 0 ? (
                        <p className="text-muted-foreground">{t('No results recorded yet.')}</p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b">
                                        <th className="py-2 text-left font-medium">{t('Tournament')}</th>
                                        <th className="py-2 text-left font-medium">{t('Home')}</th>
                                        <th className="py-2 text-center font-medium">{t('Score')}</th>
                                        <th className="py-2 text-left font-medium">{t('Away')}</th>
                                        <th className="py-2 text-right font-medium">{t('Date')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {recentResults.map((r) => (
                                        <tr key={r.id} className="border-b last:border-0">
                                            <td className="py-2">{r.tournament}</td>
                                            <td className="py-2">{r.home}</td>
                                            <td className="py-2 text-center font-mono font-bold">{r.score}</td>
                                            <td className="py-2">{r.away}</td>
                                            <td className="py-2 text-right text-muted-foreground">{formatDateTime(r.created_at, locale)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

                {/* Quick Export Links */}
                <div className="rounded-lg border bg-card p-6 shadow-sm">
                    <h3 className="text-lg font-semibold mb-4">{t('Quick Exports')}</h3>
                    {queuedExport && (
                        <div className="mb-4 rounded-md border bg-muted/30 p-3">
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p className="text-sm font-medium">{t('Queued Excel export')}</p>
                                    <p className="text-xs text-muted-foreground">
                                        {t(queuedExport.status)} · {formatNumber(queuedExport.progress, locale)}%
                                        {queuedExport.total !== null && ` · ${formatNumber(queuedExport.processed, locale)}/${formatNumber(queuedExport.total, locale)}`}
                                    </p>
                                </div>
                                {queuedExport.download_url && (
                                    <ExportButton label={t('Download ready file')} onClick={() => { window.location.href = queuedExport.download_url!; }} />
                                )}
                            </div>
                            <div className="mt-3 h-2 overflow-hidden rounded-full bg-background">
                                <div className="h-full rounded-full bg-primary transition-all" style={{ width: `${queuedExport.progress}%` }} />
                            </div>
                            {queuedExport.failure_report.length > 0 && (
                                <p className="mt-2 text-xs text-yellow-700">{queuedExport.failure_report.join(' ')}</p>
                            )}
                        </div>
                    )}
                    {queueError && <p className="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">{queueError}</p>}
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <ExportButton
                            label={t('Fixtures (PDF)')}
                            onClick={() => router.visit(route('exports.fixtures.pdf'), { preserveState: true })}
                        />
                        <ExportButton
                            label={queueingType === 'fixtures' ? t('Queueing...') : t('Fixtures (Excel)')}
                            onClick={() => queueExport('fixtures')}
                            disabled={queueingType !== null}
                        />
                        <ExportButton
                            label={t('Results (PDF)')}
                            onClick={() => router.visit(route('exports.results.pdf'), { preserveState: true })}
                        />
                        <ExportButton
                            label={queueingType === 'results' ? t('Queueing...') : t('Results (Excel)')}
                            onClick={() => queueExport('results')}
                            disabled={queueingType !== null}
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

function MetricTile({ icon: Icon, label, value, hint, tone = 'default' }: { icon: typeof ServerCog; label: string; value: number | string; hint: string; tone?: 'default' | 'error' }) {
    return (
        <div className="rounded-md border bg-background p-4">
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                <Icon className={tone === 'error' ? 'size-4 text-destructive' : 'size-4'} />
                <span>{label}</span>
            </div>
            <p className={tone === 'error' ? 'mt-2 text-2xl font-bold text-destructive' : 'mt-2 text-2xl font-bold'}>{value}</p>
            <p className="mt-1 truncate text-xs text-muted-foreground">{hint}</p>
        </div>
    );
}

function ComparisonTile({ label, current, previous, delta, locale }: { label: string; current: number; previous: number; delta: number; locale: string }) {
    const tone = delta > 0 ? 'text-green-600' : delta < 0 ? 'text-yellow-700' : 'text-muted-foreground';
    const deltaLabel = delta > 0 ? `+${formatNumber(delta, locale)}` : formatNumber(delta, locale);

    return (
        <div className="rounded-md border bg-background p-4">
            <p className="text-sm text-muted-foreground">{label}</p>
            <div className="mt-2 flex items-end justify-between gap-3">
                <p className="text-2xl font-bold">{formatNumber(current, locale)}</p>
                <p className={`text-sm font-semibold ${tone}`}>{deltaLabel}</p>
            </div>
            <p className="mt-1 text-xs text-muted-foreground">
                {formatNumber(previous, locale)} previous
            </p>
        </div>
    );
}

function StatusBadge({ status, label }: { status: 'ok' | 'warning' | 'error'; label: string }) {
    const className = status === 'ok'
        ? 'border-green-200 bg-green-50 text-green-700'
        : status === 'error'
            ? 'border-red-200 bg-red-50 text-red-700'
            : 'border-yellow-200 bg-yellow-50 text-yellow-700';

    return <span className={`inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold ${className}`}>{label}</span>;
}

function ExportButton({ label, onClick, disabled = false }: { label: string; onClick?: () => void; disabled?: boolean }) {
    return (
        <button
            onClick={onClick}
            disabled={disabled}
            className="inline-flex items-center justify-center gap-2 rounded-md border border-input bg-background px-4 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground disabled:cursor-not-allowed disabled:opacity-60"
        >
            <Download className="size-4" />
            {label}
        </button>
    );
}
