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
import { Badge } from '@/components/ui/badge';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Users, Swords, Pencil, X, Check } from 'lucide-react';
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
}

const statusBadge = (status: string) => {
    const map: Record<string, { label: string; variant: 'default' | 'secondary' | 'outline' | 'destructive' }> = {
        scheduled: { label: 'Scheduled', variant: 'outline' },
        in_progress: { label: 'In Progress', variant: 'default' },
        completed: { label: 'Completed', variant: 'secondary' },
        cancelled: { label: 'Cancelled', variant: 'destructive' },
    };
    const s = map[status] || { label: status, variant: 'outline' };
    return <Badge variant={s.variant}>{s.label}</Badge>;
};

export default function DrawResult({ event, pools: initialPools, canEdit }: DrawResultProps) {
    const { flash } = usePage<{ flash: Flash }>().props;
    const [editing, setEditing] = useState(false);
    const [pools, setPools] = useState(initialPools);
    const [pendingMoves, setPendingMoves] = useState<Record<string, string>>({});

    const allParticipants = pools.flatMap(p =>
        p.event_participants.map(ep => ({ ...ep, currentPoolId: p.id }))
    );

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
                        ) : canEdit && (
                            <Button variant="outline" size="sm" onClick={() => setEditing(true)}>
                                <Pencil className="mr-1 size-4" /> Edit Pools
                            </Button>
                        )}
                    </div>
                </div>
            }
        >
            <Head title="Draw Result" />

            {flash?.success && (
                <div className="mb-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-700">{flash.success}</div>
            )}
            {flash?.error && (
                <div className="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700">{flash.error}</div>
            )}

            {editing && (
                <div className="mb-4 rounded-md bg-amber-50 p-3 text-sm text-amber-700">
                    Edit mode: Select a new pool for each participant you want to move. Fixtures will be regenerated automatically after saving.
                </div>
            )}

            <div className="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-2xl">{event.name}</CardTitle>
                        <CardDescription>
                            {event.tournament?.name} &middot; {event.sport?.name}
                            {event.sport_category && ` - ${event.sport_category.name}`}
                            &middot; {event.format ?? 'Round Robin'}
                            &middot; {pools.length} Pool{pools.length !== 1 ? 's' : ''}
                        </CardDescription>
                    </CardHeader>
                </Card>

                <div className="grid gap-6 md:grid-cols-2">
                    {pools.length === 0 && (
                        <div className="col-span-full text-center text-muted-foreground py-12">
                            No draw has been performed for this event yet.
                        </div>
                    )}

                    {pools.map((pool) => (
                        <Card key={pool.id}>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-lg">
                                    <Users className="size-4 text-muted-foreground" />
                                    {pool.name}
                                </CardTitle>
                                <CardDescription>
                                    {pool.event_participants.length} participant{pool.event_participants.length !== 1 ? 's' : ''}
                                    &middot; {pool.fixtures.length} fixture{pool.fixtures.length !== 1 ? 's' : ''}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {pool.event_participants.length > 0 && (
                                    <div>
                                        <h4 className="mb-2 text-xs font-medium uppercase text-muted-foreground">
                                            Participants
                                        </h4>
                                        <div className="space-y-1">
                                            {pool.event_participants.map((ep) => {
                                                const pendingTarget = pendingMoves[ep.id];
                                                const willMove = pendingTarget !== undefined;
                                                return (
                                                    <div
                                                        key={ep.id}
                                                        className={`flex items-center justify-between rounded-md border px-3 py-2 text-sm ${
                                                            willMove ? 'border-amber-300 bg-amber-50' : ''
                                                        }`}
                                                    >
                                                        {editing ? (
                                                            <select
                                                                className="w-full rounded-md border border-input bg-background px-2 py-1 text-sm"
                                                                value={pendingTarget ?? pool.id}
                                                                onChange={(e) => handlePoolChange(ep.id, e.target.value)}
                                                            >
                                                                {pools.map((p) => (
                                                                    <option key={p.id} value={p.id}>
                                                                        {p.name}{p.id === pool.id ? ' (current)' : ''}
                                                                    </option>
                                                                ))}
                                                            </select>
                                                        ) : (
                                                            <span className="font-medium">
                                                                {ep.participant?.team_name || ep.participant?.name || 'Unknown'}
                                                            </span>
                                                        )}
                                                        {ep.seed_number && !editing && (
                                                            <Badge variant="secondary" className="text-xs">
                                                                Seed #{ep.seed_number}
                                                            </Badge>
                                                        )}
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    </div>
                                )}

                                {pool.fixtures.length > 0 && (
                                    <div>
                                        <h4 className="mb-2 flex items-center gap-1.5 text-xs font-medium uppercase text-muted-foreground">
                                            <Swords className="size-3" />
                                            Fixtures
                                        </h4>
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead className="w-12">#</TableHead>
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
                                                        <TableCell className="text-muted-foreground">
                                                            {f.match_number}
                                                        </TableCell>
                                                        <TableCell>R{f.round}</TableCell>
                                                        <TableCell className="font-medium">
                                                            {f.home_participant?.team_name || f.home_participant?.name || 'TBD'}
                                                        </TableCell>
                                                        <TableCell className="text-center text-muted-foreground">
                                                            vs
                                                        </TableCell>
                                                        <TableCell className="font-medium">
                                                            {f.away_participant?.team_name || f.away_participant?.name || 'TBD'}
                                                        </TableCell>
                                                        <TableCell>{statusBadge(f.status)}</TableCell>
                                                    </TableRow>
                                                ))}
                                            </TableBody>
                                        </Table>
                                    </div>
                                )}

                                {pool.event_participants.length === 0 && pool.fixtures.length === 0 && (
                                    <p className="py-4 text-center text-sm text-muted-foreground">Empty pool</p>
                                )}
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
