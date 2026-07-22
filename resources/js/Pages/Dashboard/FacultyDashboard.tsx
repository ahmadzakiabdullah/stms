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
import { Head, Link, usePage } from '@inertiajs/react';
import { Activity, ArrowRight, ListChecks, ShieldCheck, Trophy, Users, type LucideIcon } from 'lucide-react';
import { type PageProps, type SquadMember } from '@/types';

interface FacultyRegistration {
    id: string;
    event: {
        id: string;
        name: string;
        sport?: { id: string; name: string } | null;
        sport_category?: { id: string; name: string } | null;
        start_date: string;
    } | null;
    squad_members: SquadMember[];
}

interface FacultyDashboardProps {
    facultyRegistrations?: FacultyRegistration[];
    facultyMale?: number;
    facultyFemale?: number;
    facultyOfficials?: number;
}

export default function FacultyDashboard({
    facultyRegistrations = [],
    facultyMale = 0,
    facultyFemale = 0,
    facultyOfficials = 0,
}: FacultyDashboardProps) {
    const { auth, app } = usePage<PageProps>().props;
    const user = auth?.user;

    const eventsCount = facultyRegistrations.length;

    const statCards: { label: string; value: number; icon: LucideIcon; desc: string }[] = [
        { label: 'My Events', value: eventsCount, icon: Trophy, desc: 'Registered events' },
        { label: 'Male Athletes', value: facultyMale, icon: Users, desc: 'Squad members' },
        { label: 'Female Athletes', value: facultyFemale, icon: Users, desc: 'Squad members' },
        { label: 'Officials', value: facultyOfficials, icon: ShieldCheck, desc: 'Manager/Coach/Physio' },
    ];

    return (
        <>
            <Head title="Dashboard" />

            <div className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div className="text-sm text-muted-foreground">{user?.name}</div>
                    <h1 className="mt-1 text-2xl font-semibold tracking-tight">{app?.name || 'Dashboard'}</h1>
                </div>
                <div className="flex flex-wrap gap-2">
                    <Button asChild variant="outline">
                        <Link href={route('faculty.dashboard')}>
                            <ListChecks className="mr-2 size-4" />
                            Squad Management
                        </Link>
                    </Button>
                    <Button asChild>
                        <Link href={route('event-participants.index')}>
                            <Trophy className="mr-2 size-4" />
                            Event Registration
                        </Link>
                    </Button>
                </div>
            </div>

            <div className="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                {statCards.map((item) => {
                    const Icon = item.icon;
                    return (
                        <Card key={item.label}>
                            <CardHeader className="flex flex-row items-center justify-between gap-3">
                                <div>
                                    <CardDescription>{item.label}</CardDescription>
                                    <CardTitle className="mt-2 text-3xl">{item.value}</CardTitle>
                                </div>
                                <div className="flex size-11 items-center justify-center rounded-lg bg-primary/10">
                                    <Icon className="size-5 text-primary" />
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="text-sm text-muted-foreground">{item.desc}</div>
                            </CardContent>
                        </Card>
                    );
                })}
            </div>

            <div className="mt-6">
                <Card>
                    <CardHeader>
                        <CardTitle>My Event Registrations</CardTitle>
                        <CardDescription>Events your faculty is registered for</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {facultyRegistrations.length > 0 ? (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Event</TableHead>
                                        <TableHead>Sport</TableHead>
                                        <TableHead>Category</TableHead>
                                        <TableHead>Male</TableHead>
                                        <TableHead>Female</TableHead>
                                        <TableHead>Officials</TableHead>
                                        <TableHead>Date</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {facultyRegistrations.map((reg) => {
                                        const male = reg.squad_members.filter(m => m.role === 'athlete_male').length;
                                        const female = reg.squad_members.filter(m => m.role === 'athlete_female').length;
                                        const officials = reg.squad_members.filter(m => ['manager', 'coach', 'physio'].includes(m.role)).length;
                                        return (
                                            <TableRow key={reg.id}>
                                                <TableCell className="font-medium">{reg.event?.name || '—'}</TableCell>
                                                <TableCell>{reg.event?.sport?.name || '—'}</TableCell>
                                                <TableCell>{reg.event?.sport_category?.name || '—'}</TableCell>
                                                <TableCell>{male}</TableCell>
                                                <TableCell>{female}</TableCell>
                                                <TableCell>{officials}</TableCell>
                                                <TableCell className="text-sm text-muted-foreground">
                                                    {reg.event?.start_date
                                                        ? new Date(reg.event.start_date).toLocaleDateString('ms-MY', { day: 'numeric', month: 'short' })
                                                        : '—'}
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })}
                                </TableBody>
                            </Table>
                        ) : (
                            <div className="py-8 text-center">
                                <Activity className="mx-auto mb-3 size-10 text-muted-foreground/50" />
                                <p className="text-sm text-muted-foreground">Not registered for any events yet.</p>
                                <Button asChild className="mt-4">
                                    <Link href={route('event-participants.index')}>
                                        Register for Events <ArrowRight className="ml-2 size-4" />
                                    </Link>
                                </Button>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
