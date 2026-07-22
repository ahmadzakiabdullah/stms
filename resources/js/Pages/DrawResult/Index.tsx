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
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Users, Swords } from 'lucide-react';
import type { Event, Pool, Fixture, Participant, EventParticipant } from '@/types';

interface PoolWithRelations extends Pool {
    event_participants: (EventParticipant & { participant: Participant })[];
    fixtures: (Fixture & { home_participant?: Participant; away_participant?: Participant })[];
}

interface DrawResultProps {
    event: Event & { tournament?: { name: string }; sport?: { name: string }; sport_category?: { name: string } };
    pools: PoolWithRelations[];
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

export default function DrawResult({ event, pools }: DrawResultProps) {
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
                </div>
            }
        >
            <Head title="Draw Result" />

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
                                            {pool.event_participants.map((ep) => (
                                                <div
                                                    key={ep.id}
                                                    className="flex items-center justify-between rounded-md border px-3 py-2 text-sm"
                                                >
                                                    <span className="font-medium">
                                                        {ep.participant?.team_name || ep.participant?.name || 'Unknown'}
                                                    </span>
                                                    {ep.seed_number && (
                                                        <Badge variant="secondary" className="text-xs">
                                                            Seed #{ep.seed_number}
                                                        </Badge>
                                                    )}
                                                </div>
                                            ))}
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
