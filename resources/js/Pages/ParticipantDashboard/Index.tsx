import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import Pagination from '@/components/Pagination';
import type { Event, Paginated } from '@/types';

interface FacultyStat {
    id: string;
    name: string;
    total: number;
    pending: number;
    confirmed: number;
    rejected: number;
}

interface EventRow extends Event {
    sport?: { name: string };
    sportCategory?: { name: string };
    tournament?: { name: string };
    total?: number;
}

interface DashboardProps {
    stats: {
        totalRegistrations: number;
        pending: number;
        confirmed: number;
        totalFaculties: number;
        totalEvents: number;
    };
    facultyStats: FacultyStat[];
    eventStats: Paginated<EventRow> | EventRow[];
    sports: { id: string; name: string }[];
    faculties: { id: string; name: string }[];
}

export default function ParticipantDashboardIndex({
    stats,
    facultyStats,
    eventStats: eventStatsProp,
    sports,
    faculties,
}: DashboardProps) {
    const { flash } = usePage().props;
    const eventStats = Array.isArray(eventStatsProp) ? eventStatsProp : (eventStatsProp?.data ?? []);
    const [sportFilter, setSportFilter] = useState('');
    const [facultyFilter, setFacultyFilter] = useState('');
    const [statusFilter, setStatusFilter] = useState('');

    const applyFilters = () => {
        const params: Record<string, string> = {};
        if (sportFilter) params.sport_id = sportFilter;
        if (facultyFilter) params.faculty_id = facultyFilter;
        if (statusFilter) params.status = statusFilter;
        router.get(route('participant-dashboard.index'), params, { preserveScroll: true });
    };

    const clearFilters = () => {
        setSportFilter('');
        setFacultyFilter('');
        setStatusFilter('');
        router.get(route('participant-dashboard.index'), {}, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Participant Dashboard</h1>
                        <p className="text-sm text-muted-foreground">Overview of all event registrations across faculties</p>
                    </div>
                </div>
            }
        >
            <Head title="Participant Dashboard" />

            {flash?.success && <div className="mb-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-700">{flash.success}</div>}

            {/* Stats Cards */}
            <div className="grid gap-3 grid-cols-2 md:grid-cols-5 mb-6">
                <Card><CardHeader className="py-3"><CardTitle className="text-2xl text-center">{stats.totalRegistrations}</CardTitle><p className="text-xs text-center text-muted-foreground">Total Registrations</p></CardHeader></Card>
                <Card><CardHeader className="py-3"><CardTitle className="text-2xl text-center text-amber-600">{stats.pending}</CardTitle><p className="text-xs text-center text-muted-foreground">Pending</p></CardHeader></Card>
                <Card><CardHeader className="py-3"><CardTitle className="text-2xl text-center text-emerald-600">{stats.confirmed}</CardTitle><p className="text-xs text-center text-muted-foreground">Confirmed</p></CardHeader></Card>
                <Card><CardHeader className="py-3"><CardTitle className="text-2xl text-center">{stats.totalFaculties}</CardTitle><p className="text-xs text-center text-muted-foreground">Faculties</p></CardHeader></Card>
                <Card><CardHeader className="py-3"><CardTitle className="text-2xl text-center">{stats.totalEvents}</CardTitle><p className="text-xs text-center text-muted-foreground">Events</p></CardHeader></Card>
            </div>

            {/* Filters */}
            <div className="flex flex-wrap items-center gap-2 mb-4">
                <select value={sportFilter} onChange={(e) => setSportFilter(e.target.value)} className="h-9 rounded-md border border-input bg-background px-3 text-sm">
                    <option value="">All Sports</option>
                    {sports.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                </select>
                <select value={facultyFilter} onChange={(e) => setFacultyFilter(e.target.value)} className="h-9 rounded-md border border-input bg-background px-3 text-sm">
                    <option value="">All Faculties</option>
                    {faculties.map((f) => <option key={f.id} value={f.id}>{f.name}</option>)}
                </select>
                <select value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)} className="h-9 rounded-md border border-input bg-background px-3 text-sm">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="rejected">Rejected</option>
                </select>
                <Button variant="outline" size="sm" onClick={applyFilters}>Apply Filters</Button>
                {(sportFilter || facultyFilter || statusFilter) && (
                    <Button variant="ghost" size="sm" onClick={clearFilters}>Clear</Button>
                )}
            </div>

            {/* Faculty Breakdown */}
            <Card className="mb-6">
                <CardHeader><CardTitle className="text-sm">Per-Faculty Breakdown</CardTitle></CardHeader>
                <CardContent className="p-0">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left text-xs text-muted-foreground">
                                <th className="px-4 py-2 font-medium">Faculty</th>
                                <th className="px-4 py-2 font-medium text-center">Total</th>
                                <th className="px-4 py-2 font-medium text-center">Pending</th>
                                <th className="px-4 py-2 font-medium text-center">Confirmed</th>
                                <th className="px-4 py-2 font-medium text-center">Rejected</th>
                            </tr>
                        </thead>
                        <tbody>
                            {facultyStats.length === 0 && (
                                <tr><td colSpan={5} className="px-4 py-6 text-center text-muted-foreground">No data.</td></tr>
                            )}
                            {facultyStats.map((f) => (
                                <tr key={f.id} className="border-b last:border-0 hover:bg-muted/50">
                                    <td className="px-4 py-2 font-medium">{f.name}</td>
                                    <td className="px-4 py-2 text-center">{f.total}</td>
                                    <td className="px-4 py-2 text-center"><span className="text-amber-600">{f.pending}</span></td>
                                    <td className="px-4 py-2 text-center"><span className="text-emerald-600">{f.confirmed}</span></td>
                                    <td className="px-4 py-2 text-center"><span className="text-red-600">{f.rejected}</span></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </CardContent>
            </Card>

            {/* Per-Event Breakdown */}
            <Card>
                <CardHeader><CardTitle className="text-sm">Per-Event Breakdown</CardTitle></CardHeader>
                <CardContent className="p-0">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left text-xs text-muted-foreground">
                                <th className="px-4 py-2 font-medium">Event</th>
                                <th className="px-4 py-2 font-medium">Sport / Category</th>
                                <th className="px-4 py-2 font-medium text-center">Registrations</th>
                            </tr>
                        </thead>
                        <tbody>
                            {eventStats.length === 0 && (
                                <tr><td colSpan={3} className="px-4 py-6 text-center text-muted-foreground">No data.</td></tr>
                            )}
                            {eventStats.map((e) => (
                                <tr key={e.id} className="border-b last:border-0 hover:bg-muted/50">
                                    <td className="px-4 py-2 font-medium">{e.name}</td>
                                    <td className="px-4 py-2 text-muted-foreground">{e.sport?.name} / {e.sportCategory?.name}</td>
                                    <td className="px-4 py-2 text-center">{(e as any).total ?? 0}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </CardContent>
                <Pagination paginator={eventStatsProp} />
            </Card>
        </AuthenticatedLayout>
    );
}
