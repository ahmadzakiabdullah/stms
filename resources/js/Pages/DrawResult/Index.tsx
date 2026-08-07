import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
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
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Check,
    CheckCircle2,
    ClipboardList,
    History,
    Info,
    LayoutGrid,
    Pencil,
    Swords,
    Users,
    X,
} from 'lucide-react';
import { useState } from 'react';
import type { Event, Pool, Fixture, Participant, EventParticipant, Flash } from '@/types';

interface PoolWithRelations extends Pool {
    event_participants: (EventParticipant & { participant: Participant })[];
    fixtures: (Fixture & { home_participant?: Participant; away_participant?: Participant })[];
}

interface DrawResultProps {
    event: Event & { tournament?: { name: string }; sport?: { name: string }; sport_category?: { name: string } };
    pools: PoolWithRelations[];
    canEdit: boolean;
    drawVersions: {
        id: string;
        version: number;
        action: string;
        seed?: string | null;
        created_at: string;
        actor?: { name: string } | null;
    }[];
}

const statusStyles: Record<string, { label: string; className: string }> = {
    scheduled: { label: 'Scheduled', className: 'border-slate-200 bg-slate-50 text-slate-600' },
    in_progress: { label: 'In Progress', className: 'border-blue-200 bg-blue-50 text-blue-700' },
    completed: { label: 'Completed', className: 'border-emerald-200 bg-emerald-50 text-emerald-700' },
    cancelled: { label: 'Cancelled', className: 'border-red-200 bg-red-50 text-red-700' },
};

const statusBadge = (status: string) => {
    const s = statusStyles[status] || { label: status, className: 'border-slate-200 bg-slate-50 text-slate-600' };
    return <Badge variant="outline" className={`border ${s.className}`}>{s.label}</Badge>;
};

const participantName = (participant?: Participant, fallback = 'TBD') => {
    if (!participant) return fallback;

    const code = participant.name?.trim();

    if (code && code.length <= 12) return code;

    return participant.team_name || code || fallback;
};

const participantFullName = (participant?: Participant, fallback = '') => {
    if (!participant) return fallback;
    return participant.team_name || participant.name || fallback;
};

const participantInitials = (participant?: Participant) => {
    const name = participantName(participant, '?');
    return name.slice(0, 2).toUpperCase();
};

const ParticipantAvatar = ({ participant, size = 'sm' }: { participant?: Participant; size?: 'sm' | 'lg' }) => (
    <Avatar size={size} className="rounded-lg">
        {participant?.logo_url ? (
            <AvatarImage src={participant.logo_url} alt={participantName(participant)} className="rounded-lg object-contain" />
        ) : (
            <AvatarFallback className="rounded-lg">{participantInitials(participant)}</AvatarFallback>
        )}
    </Avatar>
);

interface StatTileProps {
    icon: typeof Users;
    label: string;
    value: number;
    accent: string;
}

const StatTile = ({ icon: Icon, label, value, accent }: StatTileProps) => (
    <div className="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3">
        <span className={`flex size-9 items-center justify-center rounded-lg ${accent}`}>
            <Icon className="size-5" />
        </span>
        <div>
            <p className="text-xl font-bold leading-none">{value}</p>
            <p className="mt-1 text-xs text-muted-foreground">{label}</p>
        </div>
    </div>
);

export default function DrawResult({ event, pools: initialPools, canEdit, drawVersions }: DrawResultProps) {
    const { flash } = usePage<{ flash: Flash }>().props;
    const [editing, setEditing] = useState(false);
    const [pools, setPools] = useState(initialPools);
    const [pendingMoves, setPendingMoves] = useState<Record<string, string>>({});
    const [restoreVersion, setRestoreVersion] = useState<DrawResultProps['drawVersions'][number] | null>(null);

    const allParticipants = pools.flatMap(p =>
        p.event_participants.map(ep => ({ ...ep, currentPoolId: p.id }))
    );

    const totalParticipants = allParticipants.length;
    const totalFixtures = pools.reduce((sum, p) => sum + p.fixtures.length, 0);
    const completedFixtures = pools.reduce(
        (sum, p) => sum + p.fixtures.filter(f => f.status === 'completed').length,
        0
    );
    const completion = totalFixtures > 0 ? Math.round((completedFixtures / totalFixtures) * 100) : 0;

    const handlePoolChange = (epId: string, targetPoolId: string) => {
        setPendingMoves(prev => ({ ...prev, [epId]: targetPoolId }));
    };

    const handleSave = () => {
        const entries = Object.entries(pendingMoves);
        if (entries.length === 0) {
            setEditing(false);
            return;
        }

        let i = 0;
        const processNext = () => {
            if (i >= entries.length) {
                setEditing(false);
                setPendingMoves({});
                router.reload({ only: ['pools', 'flash'] });
                return;
            }
            const [epId, targetPoolId] = entries[i];
            i++;
            router.post(route('events.draw.move-participant', event.slug), {
                event_participant_id: epId,
                target_pool_id: targetPoolId,
            }, {
                preserveScroll: true,
                onFinish: () => processNext(),
                onError: () => processNext(),
            });
        };
        processNext();
    };

    const handleCancel = () => {
        setPendingMoves({});
        setEditing(false);
        setPools(initialPools);
    };

    const handleGenerateFixtures = () => {
        router.post(route('events.generate-fixtures', event.slug), {}, { preserveScroll: true });
    };

    const showGenerateFixtures = canEdit && pools.length > 0 && totalFixtures === 0;

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center gap-4">
                    <Link href={route('events.index')}>
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="mr-1 size-4" />
                            Back
                        </Button>
                    </Link>
                    <h2 className="text-xl font-semibold leading-tight">Draw Result</h2>
                    <div className="ml-auto flex items-center gap-2">
                        {editing ? (
                            <>
                                <Button variant="outline" size="sm" onClick={handleCancel}>
                                    <X className="mr-1 size-4" /> Cancel
                                </Button>
                                <Button size="sm" onClick={handleSave} disabled={Object.keys(pendingMoves).length === 0}>
                                    <Check className="mr-1 size-4" /> Save Changes
                                </Button>
                            </>
                        ) : (
                            <>
                                {canEdit && (
                                    <Button variant="outline" size="sm" onClick={() => setEditing(true)}>
                                        <Pencil className="mr-1 size-4" /> Edit Pools
                                    </Button>
                                )}
                                {showGenerateFixtures && (
                                    <Button size="sm" onClick={handleGenerateFixtures}>
                                        <Swords className="mr-1 size-4" /> Generate Fixtures
                                    </Button>
                                )}
                            </>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={`Draw Result · ${event.name}`} />

            {flash?.success && (
                <div className="mb-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-700">{flash.success}</div>
            )}
            {flash?.error && (
                <div className="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700">{flash.error}</div>
            )}

            <div className="space-y-6">
                <Card className="overflow-hidden">
                    <div className="h-1.5 bg-gradient-to-r from-emerald-500 via-emerald-400 to-teal-400" />
                    <CardHeader>
                        <div className="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <div className="mb-2 flex flex-wrap items-center gap-2">
                                    <Badge variant="secondary">{event.sport?.name ?? 'Sport'}</Badge>
                                    {event.sport_category && <Badge variant="outline">{event.sport_category.name}</Badge>}
                                    {event.tournament && <Badge variant="outline">{event.tournament.name}</Badge>}
                                    {event.format && <Badge variant="outline">{event.format.replace(/_/g, ' ')}</Badge>}
                                </div>
                                <CardTitle className="text-2xl">{event.name}</CardTitle>
                                <CardDescription className="mt-1">
                                    {pools.length} pool{pools.length !== 1 ? 's' : ''} · {totalParticipants} participant{totalParticipants !== 1 ? 's' : ''} · {totalFixtures} fixture{totalFixtures !== 1 ? 's' : ''}
                                </CardDescription>
                            </div>
                            <div className="w-full max-w-xs">
                                <div className="mb-1.5 flex items-center justify-between text-xs text-muted-foreground">
                                    <span className="flex items-center gap-1"><CheckCircle2 className="size-3.5 text-emerald-500" /> Completion</span>
                                    <span className="font-semibold text-slate-700">{completion}%</span>
                                </div>
                                <div className="h-2 overflow-hidden rounded-full bg-slate-100">
                                    <div className="h-full rounded-full bg-emerald-500 transition-all" style={{ width: `${completion}%` }} />
                                </div>
                                <p className="mt-1.5 text-xs text-muted-foreground">{completedFixtures} of {totalFixtures} fixtures completed</p>
                            </div>
                        </div>
                        <div className="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <StatTile icon={LayoutGrid} label="Pools" value={pools.length} accent="bg-violet-50 text-violet-600" />
                            <StatTile icon={Users} label="Participants" value={totalParticipants} accent="bg-sky-50 text-sky-600" />
                            <StatTile icon={Swords} label="Fixtures" value={totalFixtures} accent="bg-emerald-50 text-emerald-600" />
                            <StatTile icon={CheckCircle2} label="Completed" value={completedFixtures} accent="bg-amber-50 text-amber-600" />
                        </div>
                    </CardHeader>
                </Card>

                {editing && (
                    <div className="flex items-center gap-2 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                        <Info className="size-4 shrink-0" />
                        Edit mode: choose a new pool for each participant you want to move, then save.{' '}
                        {totalFixtures === 0
                            ? 'Fixtures will be generated after you click Generate Fixtures.'
                            : 'Fixtures will be regenerated automatically after saving.'}
                    </div>
                )}

                {showGenerateFixtures && (
                    <div className="flex items-center gap-2 rounded-md border border-sky-200 bg-sky-50 p-3 text-sm text-sky-800">
                        <Info className="size-4 shrink-0" />
                        Groups are ready but fixtures have not been generated yet. Review and adjust the assignment below (Edit Pools), then click Generate Fixtures.
                    </div>
                )}

                {pools.length === 0 && (
                    <div className="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-slate-300 bg-white py-16 text-center">
                        <span className="flex size-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                            <ClipboardList className="size-6" />
                        </span>
                        <div>
                            <p className="font-semibold text-slate-700">No draw has been performed yet</p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Run a draw from the Events page to create groups, then generate fixtures from here.
                            </p>
                        </div>
                    </div>
                )}

                <div className="grid gap-6 md:grid-cols-2">
                    {pools.map((pool, index) => {
                        const poolCompleted = pool.fixtures.filter(f => f.status === 'completed').length;
                        const poolProgress = pool.fixtures.length > 0 ? Math.round((poolCompleted / pool.fixtures.length) * 100) : 0;

                        return (
                            <Card key={pool.id} className="flex flex-col overflow-hidden">
                                <CardHeader className="border-b border-slate-100 bg-slate-50/60">
                                    <div className="flex items-center justify-between gap-3">
                                        <div className="flex items-center gap-2.5">
                                            <span className="flex size-8 items-center justify-center rounded-lg bg-slate-900 text-sm font-bold text-white">
                                                {String(index + 1).padStart(2, '0')}
                                            </span>
                                            <div>
                                                <CardTitle className="flex items-center gap-2 text-lg leading-tight">
                                                    <Users className="size-4 text-muted-foreground" />
                                                    {pool.name}
                                                </CardTitle>
                                                <CardDescription>
                                                    {pool.event_participants.length} participant{pool.event_participants.length !== 1 ? 's' : ''} · {pool.fixtures.length} fixture{pool.fixtures.length !== 1 ? 's' : ''}
                                                </CardDescription>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <span className="text-sm font-bold text-slate-700">{poolCompleted}/{pool.fixtures.length}</span>
                                            <div className="h-1.5 w-16 overflow-hidden rounded-full bg-slate-200">
                                                <div className={`h-full rounded-full transition-all ${poolProgress === 100 ? 'bg-emerald-500' : 'bg-sky-500'}`} style={{ width: `${poolProgress}%` }} />
                                            </div>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent className="flex-1 space-y-5">
                                    {pool.event_participants.length > 0 && (
                                        <div>
                                            <h4 className="mb-2 flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                                <Users className="size-3" />
                                                Participants
                                            </h4>
                                            <div className="space-y-1.5">
                                                {pool.event_participants.map((ep) => {
                                                    const pendingTarget = pendingMoves[ep.id];
                                                    const willMove = pendingTarget !== undefined;
                                                    const shortName = participantName(ep.participant);
                                                    const fullName = participantFullName(ep.participant);

                                                    return (
                                                        <div
                                                            key={ep.id}
                                                            className={`flex items-center gap-3 rounded-lg border px-3 py-2 transition-colors ${
                                                                willMove ? 'border-amber-300 bg-amber-50' : 'border-slate-200 bg-white'
                                                            }`}
                                                        >
                                                            <ParticipantAvatar participant={ep.participant} />
                                                            <div className="min-w-0 flex-1">
                                                                <p className="flex items-center gap-2 text-sm font-semibold text-slate-800">
                                                                    {shortName}
                                                                    {fullName !== shortName && (
                                                                        <span className="truncate text-xs font-normal text-muted-foreground" title={fullName}>
                                                                            {fullName}
                                                                        </span>
                                                                    )}
                                                                </p>
                                                                {ep.seed_number && (
                                                                    <p className="mt-0.5 text-xs text-muted-foreground">Seed #{ep.seed_number}</p>
                                                                )}
                                                            </div>
                                                            {editing && (
                                                                <select
                                                                    className="rounded-md border border-input bg-background px-2 py-1.5 text-xs"
                                                                    value={pendingTarget ?? pool.id}
                                                                    onChange={(e) => handlePoolChange(ep.id, e.target.value)}
                                                                >
                                                                    {pools.map((p) => (
                                                                        <option key={p.id} value={p.id}>
                                                                            {p.name}{p.id === pool.id ? ' (current)' : ''}
                                                                        </option>
                                                                    ))}
                                                                </select>
                                                            )}
                                                            {!editing && ep.seed_number && (
                                                                <Badge variant="secondary" className="text-xs">Seed #{ep.seed_number}</Badge>
                                                            )}
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    )}

                                    {pool.fixtures.length > 0 && (
                                        <div>
                                            <h4 className="mb-2 flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                                <Swords className="size-3" />
                                                Fixtures
                                            </h4>
                                            <div className="overflow-hidden rounded-lg border border-slate-200">
                                                <Table>
                                                    <TableHeader>
                                                        <TableRow className="bg-slate-50">
                                                            <TableHead className="w-10 text-center">#</TableHead>
                                                            <TableHead>Round</TableHead>
                                                            <TableHead>Home</TableHead>
                                                            <TableHead className="w-8 text-center">vs</TableHead>
                                                            <TableHead>Away</TableHead>
                                                            <TableHead>Status</TableHead>
                                                        </TableRow>
                                                    </TableHeader>
                                                    <TableBody>
                                                        {pool.fixtures.map((f) => (
                                                            <TableRow key={f.id}>
                                                                <TableCell className="text-center text-xs text-muted-foreground">
                                                                    {f.match_number}
                                                                </TableCell>
                                                                <TableCell>
                                                                    <Badge variant="outline" className="text-xs">
                                                                        R{f.round ?? '-'}
                                                                    </Badge>
                                                                </TableCell>
                                                                <TableCell>
                                                                    <div className="flex items-center gap-2">
                                                                        <ParticipantAvatar participant={f.home_participant} size="sm" />
                                                                        <span className="font-medium" title={participantFullName(f.home_participant)}>
                                                                            {participantName(f.home_participant)}
                                                                        </span>
                                                                    </div>
                                                                </TableCell>
                                                                <TableCell className="text-center text-xs text-muted-foreground">vs</TableCell>
                                                                <TableCell>
                                                                    <div className="flex items-center gap-2">
                                                                        <ParticipantAvatar participant={f.away_participant} size="sm" />
                                                                        <span className="font-medium" title={participantFullName(f.away_participant)}>
                                                                            {participantName(f.away_participant)}
                                                                        </span>
                                                                    </div>
                                                                </TableCell>
                                                                <TableCell>{statusBadge(f.status)}</TableCell>
                                                            </TableRow>
                                                        ))}
                                                    </TableBody>
                                                </Table>
                                            </div>
                                        </div>
                                    )}

                                    {pool.event_participants.length === 0 && pool.fixtures.length === 0 && (
                                        <p className="py-6 text-center text-sm text-muted-foreground">Empty pool</p>
                                    )}
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <History className="size-4" /> Draw history
                        </CardTitle>
                        <CardDescription>Versioned allocation and fixture snapshots for audit and rollback.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {drawVersions.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No draw versions recorded yet.</p>
                        ) : (
                            <div className="space-y-2">
                                {drawVersions.map((version) => (
                                    <div key={version.id} className="flex flex-wrap items-center justify-between gap-3 rounded-lg border p-3">
                                        <div>
                                            <p className="text-sm font-medium">Version {version.version} · {version.action.replace(/_/g, ' ')}</p>
                                            <p className="text-xs text-muted-foreground">
                                                {new Date(version.created_at).toLocaleString()} · {version.actor?.name ?? 'System'}
                                                {version.seed ? ` · Seed ${version.seed}` : ''}
                                            </p>
                                        </div>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            disabled={!canEdit}
                                            onClick={() => setRestoreVersion(version)}
                                        >
                                            Restore
                                        </Button>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Dialog open={restoreVersion !== null} onOpenChange={(open) => !open && setRestoreVersion(null)}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Restore draw version {restoreVersion?.version}?</DialogTitle>
                            <DialogDescription>
                                The current draw will be preserved as a new snapshot before this version is restored.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <DialogClose asChild><Button variant="outline">Cancel</Button></DialogClose>
                            <Button
                                onClick={() => restoreVersion && router.post(
                                    route('events.draw.rollback', [event.slug, restoreVersion.id]),
                                    {},
                                    { onSuccess: () => setRestoreVersion(null) },
                                )}
                            >
                                Restore version
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AuthenticatedLayout>
    );
}
