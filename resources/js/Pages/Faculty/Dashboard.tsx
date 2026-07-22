import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Download, Plus, Search, Trash2, Upload, Users } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import type { Event, EventParticipant, Participant, SportCategory, SquadMember } from '@/types';

interface FacultyDashboardProps {
    participant: Participant | null;
    registrations: (EventParticipant & {
        event?: Event & { sport?: { id: string; name: string }; sport_category?: { id: string; name: string }; tournament?: { id: string; name: string; session?: { name: string } } };
        squad_members?: SquadMember[];
    })[];
    totals: { male: number; female: number; officials: number };
    availableEvents: (Event & { sport?: { name: string }; sport_category?: { name: string }; tournament?: { name: string } })[];
    sportCategories: (SportCategory & { sport?: { name: string } })[];
}

interface SquadMemberForm {
    name: string;
    role: SquadMember['role'];
    identification_no: string;
    phone: string;
}

const roleLabels: Record<SquadMember['role'], string> = {
    athlete_male: 'Athlete (Male)',
    athlete_female: 'Athlete (Female)',
    assistant_manager: 'Assistant Manager',
    manager: 'Manager',
    coach: 'Coach',
    physio: 'Physio',
};

const roleColors: Record<SquadMember['role'], string> = {
    athlete_male: 'bg-blue-100 text-blue-700',
    athlete_female: 'bg-pink-100 text-pink-700',
    assistant_manager: 'bg-orange-100 text-orange-700',
    manager: 'bg-purple-100 text-purple-700',
    coach: 'bg-amber-100 text-amber-700',
    physio: 'bg-teal-100 text-teal-700',
};

export default function FacultyDashboard({
    participant,
    registrations,
    totals,
    availableEvents,
    sportCategories,
}: FacultyDashboardProps) {
    const { flash } = usePage().props;
    const [activeRegId, setActiveRegId] = useState<string | null>(null);
    const [addSquadOpen, setAddSquadOpen] = useState(false);
    const [newRegOpen, setNewRegOpen] = useState(false);
    const [squadForm, setSquadForm] = useState<SquadMemberForm>({
        name: '', role: 'athlete_male', identification_no: '', phone: '',
    });
    const [selectedEventId, setSelectedEventId] = useState('');
    const [deleteSquadId, setDeleteSquadId] = useState<string | null>(null);
    const [importOpen, setImportOpen] = useState(false);
    const [importRegId, setImportRegId] = useState<string | null>(null);
    const [importFile, setImportFile] = useState<File | null>(null);

    const activeReg = registrations.find(r => r.id === activeRegId);
    const selectedEvent = availableEvents.find(e => e.id === selectedEventId);

    const registeredEventIds = new Set(registrations.map(r => r.event?.id).filter(Boolean));
    const unregisteredEvents = availableEvents.filter(e => !registeredEventIds.has(e.id));

    const officialRoles = ['assistant_manager', 'manager', 'coach', 'physio'] as const;
    const activeRegAllowedRoles = (() => {
        if (!activeReg?.event?.sport_category_id) return ['athlete_male', 'athlete_female', ...officialRoles] as const;
        const sc = sportCategories.find(c => c.id === activeReg.event!.sport_category_id);
        const allowed = sc?.allowed_roles ?? ['athlete_male', 'athlete_female'];
        return [...allowed, ...officialRoles] as const;
    })();

    const getQuota = (reg: typeof registrations[0]) => {
        const sc = sportCategories.find(c =>
            c.id === reg.event?.sport_category_id
        );
        const male = sc?.max_male_athletes ?? 0;
        const female = sc?.max_female_athletes ?? 0;
        const totalAthletes = sc?.max_athletes_total ?? 0;
        const isTotalBased = sc?.quota_mode !== 'gender_based' && totalAthletes > 0;
        const officials = sc?.max_officials ?? 0;
        const members = reg.squad_members ?? [];
        const currentMale = members.filter(m => m.role === 'athlete_male').length;
        const currentFemale = members.filter(m => m.role === 'athlete_female').length;
        const currentOfficials = members.filter(m => ['assistant_manager', 'manager', 'coach', 'physio'].includes(m.role)).length;
        return {
            male,
            female,
            totalAthletes,
            isTotalBased,
            minMale: sc?.min_male_athletes ?? 0,
            minFemale: sc?.min_female_athletes ?? 0,
            officials,
            currentMale,
            currentFemale,
            currentAthletes: currentMale + currentFemale,
            currentOfficials,
        };
    };

    const handleAddSquad = () => {
        if (!activeRegId || !squadForm.name) return;
        if (!activeRegAllowedRoles.includes(squadForm.role)) {
            setSquadForm(f => ({ ...f, role: activeRegAllowedRoles[0] }));
        }
        router.post(route('faculty.squad.store'), {
            event_participant_id: activeRegId,
            ...squadForm,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setAddSquadOpen(false);
                setSquadForm({ name: '', role: 'athlete_male', identification_no: '', phone: '' });
            },
        });
    };

    const handleDeleteSquad = () => {
        if (!deleteSquadId) return;
        router.delete(route('faculty.squad.destroy', deleteSquadId), {
            preserveScroll: true,
            onSuccess: () => setDeleteSquadId(null),
        });
    };

    const handleImport = () => {
        if (!importRegId || !importFile) return;
        const formData = new FormData();
        formData.append('event_participant_id', importRegId);
        formData.append('file', importFile);
        router.post(route('faculty.squad.import'), formData, {
            preserveScroll: true,
            onSuccess: () => { setImportOpen(false); setImportFile(null); },
        });
    };

    const handleNewRegistration = () => {
        if (!selectedEventId || !participant) return;
        router.post(route('event-participants.store'), {
            event_id: selectedEventId,
            participant_id: participant.id,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setNewRegOpen(false);
                setSelectedEventId('');
            },
        });
    };

    const statusConfig: Record<string, { label: string; class: string }> = {
        pending: { label: 'Pending', class: 'bg-amber-100 text-amber-700' },
        confirmed: { label: 'Confirmed', class: 'bg-emerald-100 text-emerald-700' },
        withdrawn: { label: 'Withdrawn', class: 'bg-gray-100 text-gray-500' },
        disqualified: { label: 'Disqualified', class: 'bg-red-100 text-red-700' },
        rejected: { label: 'Rejected', class: 'bg-rose-100 text-rose-700' },
    };

    const [regSearch, setRegSearch] = useState('');
    const unregisteredEventsGrouped = useMemo(() => {
        const g: Record<string, typeof unregisteredEvents> = {};
        for (const e of unregisteredEvents) {
            const key = e.tournament?.name || 'Other';
            if (!g[key]) g[key] = [];
            g[key].push(e);
        }
        return g;
    }, [unregisteredEvents]);

    const filteredUnregEvents = useMemo(() => {
        const q = regSearch.toLowerCase();
        return unregisteredEvents.filter(
            (e) =>
                e.name.toLowerCase().includes(q) ||
                e.sport?.name?.toLowerCase().includes(q) ||
                e.sport_category?.name?.toLowerCase().includes(q) ||
                e.tournament?.name?.toLowerCase().includes(q)
        );
    }, [regSearch, unregisteredEvents]);

    const filteredUnregGrouped = useMemo(() => {
        const g: Record<string, typeof filteredUnregEvents> = {};
        for (const e of filteredUnregEvents) {
            const key = e.tournament?.name || 'Other';
            if (!g[key]) g[key] = [];
            g[key].push(e);
        }
        return g;
    }, [filteredUnregEvents]);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Faculty Dashboard</h1>
                        <p className="text-sm text-muted-foreground">
                            {participant ? participant.name : 'No faculty profile linked'}
                        </p>
                    </div>
                    <Button onClick={() => { setNewRegOpen(true); setSelectedEventId(''); }} disabled={!participant}>
                        <Plus className="mr-2 size-4" />
                        Register for Event
                    </Button>
                </div>
            }
        >
            <Head title="Faculty Dashboard" />

            {flash?.success && (
                <div className="mb-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-700">{flash.success}</div>
            )}
            {flash?.error && (
                <div className="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700">{flash.error}</div>
            )}

            {!participant ? (
                <Card>
                    <CardContent className="py-10 text-center">
                        <Users className="mx-auto mb-4 size-10 text-muted-foreground" />
                        <p className="text-muted-foreground">Your account is not linked to any faculty profile.</p>
                        <p className="text-xs text-muted-foreground mt-1">Contact admin to link your account to a faculty.</p>
                    </CardContent>
                </Card>
            ) : (
                <>
                    <div className="grid gap-4 md:grid-cols-4">
                        <Card>
                            <CardHeader className="pb-2">
                                <CardDescription>Events Registered</CardDescription>
                                <CardTitle className="text-3xl">{registrations.length}</CardTitle>
                            </CardHeader>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardDescription>Male Athletes</CardDescription>
                                <CardTitle className="text-3xl">{totals.male}</CardTitle>
                            </CardHeader>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardDescription>Female Athletes</CardDescription>
                                <CardTitle className="text-3xl">{totals.female}</CardTitle>
                            </CardHeader>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardDescription>Officials</CardDescription>
                                <CardTitle className="text-3xl">{totals.officials}</CardTitle>
                            </CardHeader>
                        </Card>
                    </div>

                    <div className="mt-6 grid gap-6 xl:grid-cols-2">
                        <Card className="xl:col-span-2">
                            <CardHeader>
                                <CardTitle>My Registrations</CardTitle>
                                <CardDescription>Click to manage squad members for each event</CardDescription>
                            </CardHeader>
                            <CardContent>
                                {registrations.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">Not registered for any events yet.</p>
                                ) : (
                                    <div className="space-y-2">
                                        {registrations.map((reg) => {
                                            const q = getQuota(reg);
                                            const scfg = statusConfig[reg.status] ?? statusConfig.pending;
                                            return (
                                                <div key={reg.id} className="rounded-lg border">
                                                    <button
                                                        type="button"
                                                        className="flex w-full items-center justify-between p-3 text-left hover:bg-muted/50"
                                                        onClick={() => setActiveRegId(activeRegId === reg.id ? null : reg.id)}
                                                    >
                                                        <div className="min-w-0 flex-1">
                                                            <div className="flex items-center gap-2">
                                                                <span className="font-medium truncate">{reg.event?.name}</span>
                                                                <span className={`shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium leading-none ${scfg.class}`}>
                                                                    {scfg.label}
                                                                </span>
                                                            </div>
                                                            <div className="text-xs text-muted-foreground">
                                                                {reg.event?.sport?.name} · {reg.event?.sport_category?.name}
                                                                {' · '}{reg.event?.tournament?.name}
                                                            </div>
                                                        </div>
                                                        <div className="flex items-center gap-3 shrink-0 ml-3">
                                                            <span className="text-xs text-muted-foreground">
                                                                {(reg.squad_members?.length ?? 0)} members
                                                            </span>
                                                            <Badge variant="secondary">
                                                                {q.currentAthletes + q.currentOfficials}/{(q.isTotalBased ? q.totalAthletes : q.male + q.female) + q.officials}
                                                            </Badge>
                                                        </div>
                                                    </button>

                                                    {activeRegId === reg.id && (
                                                        <div className="border-t px-3 py-3 space-y-3">
                                                            {reg.status !== 'confirmed' ? (
                                                                <p className="text-xs text-amber-600 text-center py-2">
                                                                    Registration is <strong>{statusConfig[reg.status]?.label ?? reg.status}</strong>. Squad members can only be added after the dean confirms this registration.
                                                                </p>
                                                            ) : (
                                                                <>
                                                                    <div className="flex items-center justify-between">
                                                                        <div className="flex gap-3 text-xs">
                                                                            {q.isTotalBased ? (
                                                                                <span className="text-blue-600">
                                                                                    Athletes: {q.currentAthletes}/{q.totalAthletes}
                                                                                    {(q.minMale || q.minFemale) ? ` (min M ${q.minMale}, min F ${q.minFemale})` : ''}
                                                                                </span>
                                                                            ) : (
                                                                                <>
                                                                                    <span className="text-blue-600">Male: {q.currentMale}/{q.male}</span>
                                                                                    <span className="text-pink-600">Female: {q.currentFemale}/{q.female}</span>
                                                                                </>
                                                                            )}
                                                                            <span className="text-purple-600">Officials: {q.currentOfficials}/{q.officials}</span>
                                                                        </div>
                                                                        <div className="flex gap-2">
                                                                            <Button size="sm" onClick={() => { setAddSquadOpen(true); setSquadForm({ name: '', role: activeRegAllowedRoles[0], identification_no: '', phone: '' }); }}>
                                                                                <Plus className="mr-1 size-3" /> Add Member
                                                                            </Button>
                                                                            <Button variant="outline" size="sm" onClick={() => { setImportRegId(reg.id); setImportOpen(true); }}>
                                                                                <Upload className="mr-1 size-3" /> Import Excel
                                                                            </Button>
                                                                        </div>
                                                                    </div>

                                                                    <Table>
                                                                        <TableHeader>
                                                                            <TableRow>
                                                                                <TableHead>Name</TableHead>
                                                                                <TableHead>Role</TableHead>
                                                                                <TableHead>IC / Passport</TableHead>
                                                                                <TableHead>Phone</TableHead>
                                                                                <TableHead className="w-12" />
                                                                            </TableRow>
                                                                        </TableHeader>
                                                                        <TableBody>
                                                                            {(reg.squad_members?.length ?? 0) === 0 ? (
                                                                                <TableRow><TableCell colSpan={5} className="text-center text-muted-foreground text-sm">No squad members yet.</TableCell></TableRow>
                                                                            ) : (
                                                                                reg.squad_members?.map((m) => (
                                                                                    <TableRow key={m.id}>
                                                                                        <TableCell className="font-medium">{m.name}</TableCell>
                                                                                        <TableCell>
                                                                                            <span className={`rounded-full px-2 py-0.5 text-xs ${roleColors[m.role]}`}>{roleLabels[m.role]}</span>
                                                                                        </TableCell>
                                                                                        <TableCell>{m.identification_no || '-'}</TableCell>
                                                                                        <TableCell>{m.phone || '-'}</TableCell>
                                                                                        <TableCell>
                                                                                            <Button variant="ghost" size="sm" className="text-destructive" onClick={() => setDeleteSquadId(m.id)}>
                                                                                                <Trash2 className="size-3" />
                                                                                            </Button>
                                                                                        </TableCell>
                                                                                    </TableRow>
                                                                                ))
                                                                            )}
                                                                        </TableBody>
                                                                    </Table>
                                                                </>
                                                            )}
                                                        </div>
                                                    )}
                                                </div>
                                            );
                                        })}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </>
            )}

            {/* Register for Event Dialog */}
            <Dialog open={newRegOpen} onOpenChange={(o) => { if (!o) { setNewRegOpen(false); setRegSearch(''); setSelectedEventId(''); } }}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Register for Event</DialogTitle>
                        <DialogDescription>
                            {participant ? `Choose a sport/category for ${participant.name}` : 'Select an event to register'}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-4 py-4">
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                placeholder="Search by event, sport, or tournament..."
                                value={regSearch}
                                onChange={(e) => setRegSearch(e.target.value)}
                                className="pl-9"
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label>Available Events</Label>
                            <div className="max-h-64 overflow-y-auto rounded-md border">
                                {filteredUnregEvents.length === 0 && (
                                    <p className="p-3 text-sm text-muted-foreground">
                                        {regSearch ? 'No matching events.' : 'Already registered for all events.'}
                                    </p>
                                )}
                                {Object.entries(filteredUnregGrouped).map(([tournamentName, evts], gi) => (
                                    <div key={tournamentName}>
                                        <div className="sticky top-0 bg-muted/80 px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-muted-foreground backdrop-blur flex items-center gap-2">
                                            <span className="inline-block size-2 rounded-full bg-primary/60" />
                                            {tournamentName}
                                            <span className="font-normal text-[10px]">({evts.length})</span>
                                        </div>
                                        {evts.map((evt) => (
                                            <label
                                                key={evt.id}
                                                className={`flex items-center gap-3 px-3 py-2 text-sm cursor-pointer transition ${
                                                    selectedEventId === evt.id
                                                        ? 'bg-primary/10 font-medium'
                                                        : 'hover:bg-muted/50'
                                                }`}
                                            >
                                                <input
                                                    type="radio"
                                                    name="event"
                                                    value={evt.id}
                                                    checked={selectedEventId === evt.id}
                                                    onChange={(e) => setSelectedEventId(e.target.value)}
                                                    className="size-4"
                                                    disabled={(evt as any).registration_deadline ? new Date((evt as any).registration_deadline) < new Date() : false}
                                                />
                                                <span className="text-lg">
                                                    {evt.sport?.name === 'Badminton' ? '🏸' : evt.sport?.name === 'Football' ? '⚽' : evt.sport?.name === 'Basketball' ? '🏀' : '🏅'}
                                                </span>
                                                <div className="min-w-0 flex-1">
                                                    <div className="truncate">{evt.sport?.name} — {evt.sport_category?.name}</div>
                                                    <div className="text-xs text-muted-foreground truncate">{evt.name}</div>
                                                    {(evt as any).registration_deadline && new Date((evt as any).registration_deadline) < new Date() && (
                                                        <span className="text-[10px] text-destructive">(Deadline passed)</span>
                                                    )}
                                                </div>
                                            </label>
                                        ))}
                                    </div>
                                ))}
                            </div>
                        </div>

                        {selectedEvent && (
                            <div className="rounded-md bg-muted/50 px-3 py-2 text-xs text-muted-foreground">
                                {selectedEvent.tournament?.name} · {selectedEvent.sport?.name} · {selectedEvent.sport_category?.name}
                            </div>
                        )}
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => { setNewRegOpen(false); setRegSearch(''); setSelectedEventId(''); }}>
                            Cancel
                        </Button>
                        <Button onClick={handleNewRegistration} disabled={!selectedEventId}>
                            <Plus className="mr-2 size-4" />
                            Register
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Add Squad Member Dialog */}
            <Dialog open={addSquadOpen} onOpenChange={setAddSquadOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Add Squad Member</DialogTitle>
                        <DialogDescription>
                            {activeReg ? `For ${activeReg.event?.name}` : ''}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="member-name">Full Name</Label>
                            <Input id="member-name" value={squadForm.name} onChange={(e) => setSquadForm({ ...squadForm, name: e.target.value })} placeholder="e.g. Ali bin Ahmad" />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="member-role">Role</Label>
                            <select
                                id="member-role"
                                className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm"
                                value={squadForm.role}
                                onChange={(e) => setSquadForm({ ...squadForm, role: e.target.value as SquadMember['role'] })}
                            >
                                 {activeRegAllowedRoles.map((value) => (
                                     <option key={value} value={value}>{roleLabels[value]}</option>
                                 ))}
                            </select>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="member-ic">IC / Passport (optional)</Label>
                            <Input id="member-ic" value={squadForm.identification_no} onChange={(e) => setSquadForm({ ...squadForm, identification_no: e.target.value })} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="member-phone">Phone (optional)</Label>
                            <Input id="member-phone" value={squadForm.phone} onChange={(e) => setSquadForm({ ...squadForm, phone: e.target.value })} />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setAddSquadOpen(false)}>Cancel</Button>
                        <Button onClick={handleAddSquad} disabled={!squadForm.name}>Add</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Bulk Import Dialog */}
            <Dialog open={importOpen} onOpenChange={(o) => { if (!o) { setImportOpen(false); setImportFile(null); } }}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Import Squad Members</DialogTitle>
                        <DialogDescription>
                            Upload an Excel/CSV file with squad members for the selected event.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-4 py-4">
                        <Link
                            href={route('faculty.squad.template')}
                            className="flex items-center gap-2 text-sm text-primary hover:underline"
                        >
                            <Download className="size-4" /> Download template
                        </Link>
                        <div className="grid gap-2">
                            <Label htmlFor="import-file">Excel/CSV File</Label>
                            <Input
                                id="import-file"
                                type="file"
                                accept=".xlsx,.xls,.csv"
                                onChange={(e) => setImportFile(e.target.files?.[0] ?? null)}
                            />
                            <p className="text-xs text-muted-foreground">Columns: name, role, ic_passport, phone</p>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => { setImportOpen(false); setImportFile(null); }}>Cancel</Button>
                        <Button onClick={handleImport} disabled={!importFile}>
                            <Upload className="mr-2 size-4" /> Import
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Delete Squad Member Confirmation */}
            <Dialog open={!!deleteSquadId} onOpenChange={(isOpen) => !isOpen && setDeleteSquadId(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Remove Squad Member?</DialogTitle>
                        <DialogDescription>This action cannot be undone.</DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteSquadId(null)}>Cancel</Button>
                        <Button variant="destructive" onClick={handleDeleteSquad}>Remove</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}
