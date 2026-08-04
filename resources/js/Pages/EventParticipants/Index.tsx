import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
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

import Pagination from '@/components/Pagination';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ChevronDown, Check, CircleX, FileText, LayoutGrid, List, Plus, Search, X } from 'lucide-react';
import { Fragment, useEffect, useMemo, useRef, useState } from 'react';
import type {
    Event as EventType,
    EventParticipant as EventParticipantType,
    Participant,
    Paginated,
    Flash,
    SquadMember,
} from '@/types';

interface ParticipantWithEvents extends Participant {
    event_participants?: (EventParticipantType & {
        event?: EventType & { sport?: { name: string }; sport_category?: { name: string }; tournament?: { name: string } };
    })[];
}

interface EventParticipantsIndexProps {
    participants: Paginated<ParticipantWithEvents> | ParticipantWithEvents[];
    events: (EventType & { sport?: { name: string }; sport_category?: { name: string }; tournament?: { name: string } })[];
    faculties?: { id: string; name: string }[];
    isFacultyRepresentative?: boolean;
    statusCounts?: Record<string, number>;
}

const squadRoleConfig: Record<string, { label: string; class: string }> = {
    athlete_male: { label: 'Male Athlete', class: 'bg-blue-100 text-blue-700' },
    athlete_female: { label: 'Female Athlete', class: 'bg-pink-100 text-pink-700' },
    assistant_manager: { label: 'Asst. Manager', class: 'bg-orange-100 text-orange-700' },
    manager: { label: 'Manager', class: 'bg-purple-100 text-purple-700' },
    coach: { label: 'Coach', class: 'bg-amber-100 text-amber-700' },
    physio: { label: 'Physio', class: 'bg-teal-100 text-teal-700' },
};

const statusConfig: Record<string, { label: string; class: string; variant: 'default' | 'secondary' | 'destructive' | 'outline' }> = {
    pending: { label: 'Pending', class: 'bg-amber-100 text-amber-700 border-amber-200', variant: 'outline' },
    confirmed: { label: 'Confirmed', class: 'bg-emerald-100 text-emerald-700 border-emerald-200', variant: 'default' },
    withdrawn: { label: 'Withdrawn', class: 'bg-gray-100 text-gray-500 border-gray-200', variant: 'secondary' },
    disqualified: { label: 'Disqualified', class: 'bg-red-100 text-red-700 border-red-200', variant: 'destructive' },
    rejected: { label: 'Rejected', class: 'bg-rose-100 text-rose-700 border-rose-200', variant: 'destructive' },
};

const sportIcon: Record<string, string> = {
    badminton: '🏸', 'bola sepak': '⚽', 'bola keranjang': '🏀', 'bola tampar': '🏐',
    hoki: '🏑', ragbi: '🏉', olahraga: '🏃', renang: '🏊', memanah: '🎯',
    pingpong: '🏓', taekwondo: '🥋', silat: '⚔️', catur: '♟️', efootball: '🎮',
};

function getSportIcon(name?: string): string {
    if (!name) return '🏅';
    const lower = name.toLowerCase();
    for (const [key, icon] of Object.entries(sportIcon)) {
        if (lower.includes(key)) return icon;
    }
    return '🏅';
}

const sportColors: Record<string, string> = {
    badminton: 'border-l-emerald-400', 'bola sepak': 'border-l-sky-400',
    'bola keranjang': 'border-l-orange-400', 'bola tampar': 'border-l-blue-400',
    hoki: 'border-l-amber-400', ragbi: 'border-l-red-400',
    memanah: 'border-l-purple-400', catur: 'border-l-stone-400',
};

function getSportBorder(name?: string): string {
    if (!name) return 'border-l-gray-300';
    const lower = name.toLowerCase();
    for (const [key, color] of Object.entries(sportColors)) {
        if (lower.includes(key)) return color;
    }
    return 'border-l-gray-300';
}

const tournamentColors = ['border-l-emerald-400', 'border-l-sky-400', 'border-l-violet-400', 'border-l-rose-400', 'border-l-amber-400', 'border-l-teal-400'];

function AddEventDialog({
    open, onClose, participantId, participantName, events, participants,
}: {
    open: boolean; onClose: () => void; participantId: string; participantName: string;
    events: EventParticipantsIndexProps['events'];
    participants?: ParticipantWithEvents[];
}) {
    const [selectedEventId, setSelectedEventId] = useState('');
    const [selectedParticipantId, setSelectedParticipantId] = useState(participantId);
    const [search, setSearch] = useState('');
    const [groupBy, setGroupBy] = useState<'tournament' | 'sport'>('sport');

    const registeredIds = useMemo(() => {
        const pid = selectedParticipantId || participantId;
        if (!pid) return [];
        const p = participants?.find(p => p.id === pid);
        return p?.event_participants?.map(ep => ep.event_id) ?? [];
    }, [selectedParticipantId, participantId, participants]);

    const filtered = useMemo(() => {
        const q = search.toLowerCase();
        return events.filter(
            (e) => !registeredIds.includes(e.id) &&
                (e.name.toLowerCase().includes(q) || e.sport?.name?.toLowerCase().includes(q) ||
                 e.sport_category?.name?.toLowerCase().includes(q) || e.tournament?.name?.toLowerCase().includes(q))
        );
    }, [search, events, registeredIds]);

    const grouped = useMemo(() => {
        const g: Record<string, typeof filtered> = {};
        for (const e of filtered) {
            const key = groupBy === 'tournament' ? (e.tournament?.name || 'Other') : (e.sport?.name || 'Other');
            if (!g[key]) g[key] = [];
            g[key].push(e);
        }
        return g;
    }, [filtered, groupBy]);

    const handleRegister = () => {
        const pid = selectedParticipantId || participantId;
        if (!selectedEventId || !pid) return;
        router.post(route('event-participants.store'), { event_id: selectedEventId, participant_id: pid }, {
            onSuccess: () => { setSelectedEventId(''); setSelectedParticipantId(''); setSearch(''); onClose(); },
        });
    };

    return (
        <Dialog open={open} onOpenChange={(o) => { if (!o) { setSelectedEventId(''); setSelectedParticipantId(''); setSearch(''); onClose(); } }}>
            <DialogContent className="max-w-lg">
                <DialogHeader>
                    <DialogTitle>Register to Event</DialogTitle>
                    <DialogDescription>Add an event for <strong>{participantName}</strong></DialogDescription>
                </DialogHeader>
                <div className="grid gap-4 py-4">
                    {!participantId && participants && (
                        <div className="grid gap-2">
                            <Label htmlFor="dialog-participant">Participant</Label>
                            <select id="dialog-participant" value={selectedParticipantId}
                                onChange={(e) => setSelectedParticipantId(e.target.value)}
                                className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm" required>
                                <option value="">-- Select Participant --</option>
                                {participants.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                            </select>
                        </div>
                    )}
                    <div className="relative">
                        <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input placeholder="Search by event, sport, or tournament..." value={search}
                            onChange={(e) => setSearch(e.target.value)} className="pl-9" />
                    </div>
                    <div className="flex items-center gap-2 text-xs text-muted-foreground">
                        <span>Group by:</span>
                        <button type="button" onClick={() => setGroupBy('sport')}
                            className={`px-2 py-1 rounded ${groupBy === 'sport' ? 'bg-primary/10 text-primary font-medium' : 'hover:bg-muted'}`}>Sport</button>
                        <button type="button" onClick={() => setGroupBy('tournament')}
                            className={`px-2 py-1 rounded ${groupBy === 'tournament' ? 'bg-primary/10 text-primary font-medium' : 'hover:bg-muted'}`}>Tournament</button>
                    </div>
                    <div className="grid gap-2">
                        <Label>Available Events</Label>
                        <div className="max-h-60 overflow-y-auto rounded-md border">
                            {filtered.length === 0 && (
                                <p className="p-3 text-sm text-muted-foreground">{search ? 'No matching events.' : 'Already registered for all available events.'}</p>
                            )}
                            {Object.entries(grouped).map(([groupName, evts], gi) => (
                                <div key={groupName}>
                                    <div className="sticky top-0 bg-muted/80 px-3 py-1.5 text-xs font-semibold uppercase tracking-wider text-muted-foreground backdrop-blur flex items-center gap-2">
                                        <span className={`inline-block size-2 rounded-full ${tournamentColors[gi % tournamentColors.length]}`} />
                                        {groupName} <span className="font-normal text-[10px]">({evts.length})</span>
                                    </div>
                                    {evts.map((evt) => (
                                        <label key={evt.id}
                                            className={`flex items-center gap-3 px-3 py-2 text-sm cursor-pointer transition ${selectedEventId === evt.id ? 'bg-primary/10 font-medium' : 'hover:bg-muted/50'}`}>
                                            <input type="radio" name="event" value={evt.id}
                                                checked={selectedEventId === evt.id}
                                                onChange={(e) => setSelectedEventId(e.target.value)} className="size-4" />
                                            <span className="text-lg">{getSportIcon(evt.sport?.name)}</span>
                                            <div className="min-w-0 flex-1">
                                                <div className="truncate">{evt.name}</div>
                                                <div className="text-xs text-muted-foreground">{evt.sport?.name}{evt.sport_category?.name ? ` · ${evt.sport_category.name}` : ''}{evt.tournament?.name ? ` · ${evt.tournament.name}` : ''}</div>
                                            </div>
                                        </label>
                                    ))}
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" onClick={() => { setSelectedEventId(''); setSelectedParticipantId(''); setSearch(''); onClose(); }}>Cancel</Button>
                    <Button onClick={handleRegister} disabled={!selectedEventId}><Plus className="mr-2 size-4" />Register</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function ConfirmUnregisterDialog({ open, onClose, onConfirm, participantName, eventName }: {
    open: boolean; onClose: () => void; onConfirm: () => void; participantName: string; eventName: string;
}) {
    return (
        <Dialog open={open} onOpenChange={(o) => { if (!o) onClose(); }}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Unregister {participantName}?</DialogTitle>
                    <DialogDescription>Remove <strong>{participantName}</strong> from <strong>{eventName}</strong>? This action cannot be undone.</DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" onClick={onClose}>Cancel</Button>
                    <Button variant="destructive" onClick={onConfirm}>Yes, Unregister</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function ConfirmRejectDialog({ open, onClose, onConfirm, participantName, eventName }: {
    open: boolean; onClose: () => void; onConfirm: () => void; participantName: string; eventName: string;
}) {
    return (
        <Dialog open={open} onOpenChange={(o) => { if (!o) onClose(); }}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Reject registration?</DialogTitle>
                    <DialogDescription>Reject <strong>{participantName}</strong> from <strong>{eventName}</strong>? The faculty representative will be notified.</DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" onClick={onClose}>Cancel</Button>
                    <Button variant="destructive" onClick={onConfirm}>Yes, Reject</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export default function EventParticipantsIndex({
    participants: participantsProp, events: eventsProp = [], faculties: facultiesProp = [],
    isFacultyRepresentative = false, statusCounts: statusCountsProp = {},
}: EventParticipantsIndexProps) {
    const { flash } = usePage().props;
    const participantsList = Array.isArray(participantsProp) ? participantsProp : participantsProp?.data ?? [];
    const events = Array.isArray(eventsProp) ? eventsProp : eventsProp ?? [];
    const faculties = Array.isArray(facultiesProp) ? facultiesProp : [];
    const statusCounts = (statusCountsProp && typeof statusCountsProp === 'object' && !Array.isArray(statusCountsProp)) ? statusCountsProp : {};

    const defaultTab = isFacultyRepresentative ? 'events' : 'registrations';
    const [activeTab, setActiveTab] = useState<'registrations' | 'events'>(defaultTab);
    const [viewMode, setViewMode] = useState<'grid' | 'table'>(isFacultyRepresentative ? 'grid' : 'table');

    const searchTimerRef = useRef<ReturnType<typeof setTimeout>>();
    const filtersRef = useRef({ search: '', sport_id: '', category_id: '', participant_id: '', status: '' });
    const [searchInput, setSearchInput] = useState('');
    const [filterSportId, setFilterSportId] = useState('');
    const [filterCategoryId, setFilterCategoryId] = useState('');
    const [filterParticipantId, setFilterParticipantId] = useState('');
    const [filterStatus, setFilterStatus] = useState('');

    useEffect(() => {
        const p = new URLSearchParams(window.location.search);
        const initial = { search: p.get('search') ?? '', sport_id: p.get('sport_id') ?? '', category_id: p.get('category_id') ?? '', participant_id: p.get('participant_id') ?? '', status: p.get('status') ?? '' };
        filtersRef.current = initial;
        setSearchInput(initial.search); setFilterSportId(initial.sport_id); setFilterCategoryId(initial.category_id); setFilterParticipantId(initial.participant_id); setFilterStatus(initial.status);
    }, []);

    const doNavigate = () => {
        const params: Record<string, string> = {};
        const f = filtersRef.current;
        if (f.search) params.search = f.search;
        if (f.sport_id) params.sport_id = f.sport_id;
        if (f.category_id) params.category_id = f.category_id;
        if (f.participant_id) params.participant_id = f.participant_id;
        if (f.status) params.status = f.status;
        router.get(route('event-participants.index'), params, { preserveScroll: true, preserveState: true });
    };

    const handleSearchChange = (value: string) => {
        setSearchInput(value);
        filtersRef.current.search = value;
        if (searchTimerRef.current) clearTimeout(searchTimerRef.current);
        searchTimerRef.current = setTimeout(doNavigate, 300);
    };

    const handleSportChange = (value: string) => {
        if (searchTimerRef.current) clearTimeout(searchTimerRef.current);
        setFilterSportId(value); setFilterCategoryId('');
        filtersRef.current.sport_id = value; filtersRef.current.category_id = '';
        doNavigate();
    };

    const handleCategoryChange = (value: string) => {
        if (searchTimerRef.current) clearTimeout(searchTimerRef.current);
        setFilterCategoryId(value);
        filtersRef.current.category_id = value;
        doNavigate();
    };

    const handleParticipantChange = (value: string) => {
        if (searchTimerRef.current) clearTimeout(searchTimerRef.current);
        setFilterParticipantId(value);
        filtersRef.current.participant_id = value;
        doNavigate();
    };

    const handleStatusChange = (value: string) => {
        if (searchTimerRef.current) clearTimeout(searchTimerRef.current);
        setFilterStatus(value);
        filtersRef.current.status = value;
        doNavigate();
    };

    const handleClearFilters = () => {
        if (searchTimerRef.current) clearTimeout(searchTimerRef.current);
        setSearchInput(''); setFilterSportId(''); setFilterCategoryId(''); setFilterParticipantId(''); setFilterStatus('');
        filtersRef.current = { search: '', sport_id: '', category_id: '', participant_id: '', status: '' };
        router.get(route('event-participants.index'), {}, { preserveScroll: true, preserveState: true });
    };

    const sports = useMemo(() => {
        const map = new Map<string, { id: string; name: string }>();
        for (const e of events) if (e.sport?.id) map.set(e.sport.id, { id: e.sport.id, name: e.sport.name });
        return [...map.values()].sort((a, b) => a.name.localeCompare(b.name));
    }, [events]);

    const categories = useMemo(() => {
        const map = new Map<string, { id: string; name: string; sport_id: string }>();
        for (const e of events) {
            if (!e.sport_category) continue;
            if (filterSportId && e.sport_id !== filterSportId) continue;
            map.set(e.sport_category.id, { id: e.sport_category.id, name: e.sport_category.name, sport_id: e.sport_id });
        }
        return [...map.values()].sort((a, b) => a.name.localeCompare(b.name));
    }, [events, filterSportId]);

    const totalRegistrations = useMemo(() => participantsList.reduce((sum, p) => sum + (p.event_participants?.length ?? 0), 0), [participantsList]);

    const statusCards = useMemo(() => {
        const cards: Array<{ key: string; label: string; count: number }> = [
            { key: '', label: 'All', count: totalRegistrations },
            { key: 'pending', label: 'Pending', count: statusCounts.pending ?? 0 },
            { key: 'confirmed', label: 'Confirmed', count: statusCounts.confirmed ?? 0 },
            { key: 'rejected', label: 'Rejected', count: statusCounts.rejected ?? 0 },
        ];
        for (const k of ['withdrawn', 'disqualified']) {
            if ((statusCounts[k] ?? 0) > 0) cards.push({ key: k, label: statusConfig[k]?.label ?? k, count: statusCounts[k] ?? 0 });
        }
        return cards;
    }, [statusCounts, totalRegistrations]);

    const [addTarget, setAddTarget] = useState<{ id: string; name: string } | null>(null);
    const [unregTarget, setUnregTarget] = useState<{ id: string; participantName: string; eventName: string } | null>(null);
    const [rejectTarget, setRejectTarget] = useState<{ epId: string; participantName: string; eventName: string } | null>(null);
    const [expandedEp, setExpandedEp] = useState<string | null>(null);

    const handleUnregister = () => {
        if (!unregTarget) return;
        router.delete(route('event-participants.destroy', unregTarget.id), {
            preserveScroll: true, onSuccess: () => setUnregTarget(null),
        });
    };

    const approveRegistration = (epId: string) => {
        router.patch(route('event-participants.status', epId), { status: 'confirmed' }, {
            preserveScroll: true,
        });
    };

    const rejectRegistration = () => {
        if (!rejectTarget) return;
        router.patch(route('event-participants.status', rejectTarget.epId), { status: 'rejected' }, {
            preserveScroll: true, onSuccess: () => setRejectTarget(null),
        });
    };

    const quickRegister = (eventId: string) => {
        router.post(route('event-participants.store'), { event_id: eventId }, {
            onSuccess: () => router.reload({ only: ['participants'] }),
        });
    };

    const hasActiveFilters = searchInput || filterSportId || filterCategoryId || filterParticipantId || filterStatus;

    const registeredEventIds = useMemo(() => {
        const ids = new Set<string>();
        for (const p of participantsList) for (const ep of (p.event_participants ?? [])) ids.add(ep.event_id);
        return ids;
    }, [participantsList]);

    const eventRegistry = useMemo(() => {
        const map = new Map<string, { event: (typeof events)[number]; isRegistered: boolean; registrations: Array<{ participant: ParticipantWithEvents; ep: EventParticipantType }> }>();
        for (const evt of events) map.set(evt.id, { event: evt, isRegistered: false, registrations: [] });
        for (const p of participantsList) {
            for (const ep of (p.event_participants ?? [])) {
                const entry = map.get(ep.event_id);
                if (!entry) continue;
                entry.isRegistered = true;
                entry.registrations.push({ participant: p, ep });
            }
        }
        return [...map.values()].sort((a, b) => a.event.name.localeCompare(b.event.name));
    }, [participantsList, events]);

    const registrationRows = useMemo(() => {
        const rows: Array<{ ep: EventParticipantType; participant: ParticipantWithEvents; event: (typeof events)[number] }> = [];
        for (const p of participantsList) {
            for (const ep of (p.event_participants ?? [])) {
                const evt = events.find(e => e.id === ep.event_id);
                if (!evt) continue;
                rows.push({ ep, participant: p, event: evt });
            }
        }
        return rows.sort((a, b) => a.event.name.localeCompare(b.event.name));
    }, [participantsList, events]);

    const tabLabel = isFacultyRepresentative
        ? { registrations: 'My Registrations', events: 'Available Events' }
        : { registrations: 'All Registrations', events: 'All Events' };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Event Registrations</h1>
                        <p className="text-sm text-muted-foreground">
                            {isFacultyRepresentative ? "Manage your faculty's event participation" : 'Overview of every faculty\'s event participation'}
                        </p>
                    </div>
                </div>
            }
        >
            <Head title="Event Registrations" />

            {flash?.success && <div className="mb-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-700">{flash.success}</div>}
            {flash?.error && <div className="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700">{flash.error}</div>}

            {/* Status stat cards */}
            <div className="mb-4 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-5">
                {statusCards.map((card) => {
                    const isActive = filterStatus === card.key;
                    return (
                        <button key={card.key || 'all'} type="button" onClick={() => handleStatusChange(card.key)}
                            className={`flex flex-col items-start gap-0.5 rounded-lg border bg-card px-3 py-2 text-left transition ${isActive ? 'border-primary ring-1 ring-primary' : 'hover:bg-muted/50'}`}>
                            <span className="text-lg font-semibold leading-none tabular-nums">{card.count}</span>
                            <span className="text-[10px] font-medium uppercase tracking-wide text-muted-foreground">{card.label}</span>
                        </button>
                    );
                })}
                <div className="flex flex-col items-start gap-0.5 rounded-lg border bg-card px-3 py-2">
                    <span className="text-lg font-semibold leading-none tabular-nums">{events.length}</span>
                    <span className="text-[10px] font-medium uppercase tracking-wide text-muted-foreground">Events</span>
                </div>
            </div>

            {/* Tabs + View toggle */}
            <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                <div className="flex items-center gap-1 rounded-lg border bg-muted/20 p-0.5">
                    <button onClick={() => setActiveTab('registrations')}
                        className={`rounded-md px-3 py-1.5 text-xs font-medium transition ${activeTab === 'registrations' ? 'bg-background text-foreground shadow-xs' : 'text-muted-foreground hover:text-foreground'}`}>
                        {tabLabel.registrations}
                        {activeTab === 'registrations' && registrationRows.length > 0 && <span className="ml-1.5 text-[10px] text-muted-foreground">({registrationRows.length})</span>}
                    </button>
                    <button onClick={() => setActiveTab('events')}
                        className={`rounded-md px-3 py-1.5 text-xs font-medium transition ${activeTab === 'events' ? 'bg-background text-foreground shadow-xs' : 'text-muted-foreground hover:text-foreground'}`}>
                        {tabLabel.events}
                        {activeTab === 'events' && <span className="ml-1.5 text-[10px] text-muted-foreground">({eventRegistry.length})</span>}
                    </button>
                </div>

                <div className="flex items-center gap-2">
                    {activeTab === 'events' && (
                        <div className="flex items-center gap-1 rounded-lg border bg-muted/20 p-0.5">
                            <button onClick={() => setViewMode('grid')}
                                className={`rounded-md p-1.5 transition ${viewMode === 'grid' ? 'bg-background text-foreground shadow-xs' : 'text-muted-foreground hover:text-foreground'}`}
                                title="Grid view"><LayoutGrid className="size-3.5" /></button>
                            <button onClick={() => setViewMode('table')}
                                className={`rounded-md p-1.5 transition ${viewMode === 'table' ? 'bg-background text-foreground shadow-xs' : 'text-muted-foreground hover:text-foreground'}`}
                                title="Table view"><List className="size-3.5" /></button>
                        </div>
                    )}
                </div>
            </div>

            {/* Filters */}
            <div className="mb-4 flex flex-wrap items-center gap-2">
                <div className="relative">
                    <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input placeholder="Search..." value={searchInput}
                        onChange={(e) => handleSearchChange(e.target.value)}
                        className="h-8 w-40 lg:w-48 pl-8 text-xs" />
                </div>

                <select value={filterSportId} onChange={(e) => handleSportChange(e.target.value)}
                    className="h-8 rounded-md border border-input bg-background px-2 text-xs">
                    <option value="">All Sports</option>
                    {sports.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                </select>

                {categories.length > 0 && (
                    <select value={filterCategoryId} onChange={(e) => handleCategoryChange(e.target.value)}
                        className="h-8 rounded-md border border-input bg-background px-2 text-xs">
                        <option value="">All Categories</option>
                        {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                    </select>
                )}

                {!isFacultyRepresentative && faculties.length > 0 && (
                    <select value={filterParticipantId} onChange={(e) => handleParticipantChange(e.target.value)}
                        className="h-8 rounded-md border border-input bg-background px-2 text-xs">
                        <option value="">All Faculties</option>
                        {faculties.map((f) => <option key={f.id} value={f.id}>{f.name}</option>)}
                    </select>
                )}

                <select value={filterStatus} onChange={(e) => handleStatusChange(e.target.value)}
                    className="h-8 rounded-md border border-input bg-background px-2 text-xs">
                    <option value="">All Statuses</option>
                    {Object.entries(statusConfig).map(([key, cfg]) => (
                        <option key={key} value={key}>{cfg.label}</option>
                    ))}
                </select>

                {hasActiveFilters && (
                    <Button variant="ghost" size="sm" onClick={handleClearFilters} className="h-8 text-xs">Clear</Button>
                )}
            </div>

            {/* === TAB: Registrations (Table) === */}
            {activeTab === 'registrations' && (
                registrationRows.length === 0 ? (
                    <Card><CardContent className="py-10 text-center text-sm text-muted-foreground">
                        {hasActiveFilters ? 'No matching registrations.' : isFacultyRepresentative ? "You haven't registered for any events yet." : 'No registrations yet.'}
                    </CardContent></Card>
                ) : (
                    <Card>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Event</TableHead>
                                        {!isFacultyRepresentative && <TableHead>Faculty</TableHead>}
                                        <TableHead>Sport / Category</TableHead>
                                        <TableHead>Tournament</TableHead>
                                        {!isFacultyRepresentative && <TableHead>Squad</TableHead>}
                                        <TableHead>Status</TableHead>
                                        <TableHead className="w-36 text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {registrationRows.map(({ ep, participant, event: evt }) => {
                                        const cfg = statusConfig[ep.status] ?? statusConfig.pending;
                                        const members: SquadMember[] = Array.isArray(ep.squad_members) ? ep.squad_members : [];
                                        const maleCount = members.filter((m) => m.role === 'athlete_male').length;
                                        const femaleCount = members.filter((m) => m.role === 'athlete_female').length;
                                        const officialCount = members.filter((m) => m.role !== 'athlete_male' && m.role !== 'athlete_female').length;
                                        const isExpanded = expandedEp === ep.id;
                                        return (
                                            <Fragment key={ep.id}>
                                                <TableRow className={isExpanded ? 'bg-muted/30' : undefined}>
                                                    <TableCell className="font-medium text-xs">{evt.name}</TableCell>
                                                    {!isFacultyRepresentative && <TableCell className="text-xs">{participant.name}</TableCell>}
                                                    <TableCell className="text-xs text-muted-foreground">{evt.sport?.name}{evt.sport_category?.name ? ` / ${evt.sport_category.name}` : ''}</TableCell>
                                                    <TableCell className="text-xs text-muted-foreground">{evt.tournament?.name || '-'}</TableCell>
                                                    {!isFacultyRepresentative && (
                                                        <TableCell>
                                                            {members.length > 0 ? (
                                                                <button
                                                                    onClick={() => setExpandedEp(isExpanded ? null : ep.id)}
                                                                    className="inline-flex items-center gap-1 rounded-md border px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground transition hover:bg-muted hover:text-foreground"
                                                                    title="View squad members"
                                                                >
                                                                    <ChevronDown className={`size-3 transition-transform ${isExpanded ? 'rotate-180' : ''}`} />
                                                                    {members.length} member{members.length > 1 ? 's' : ''}
                                                                </button>
                                                            ) : (
                                                                <span className="text-[10px] text-muted-foreground">—</span>
                                                            )}
                                                        </TableCell>
                                                    )}
                                                    <TableCell><Badge variant={cfg.variant} className="text-[10px] px-1.5">{cfg.label}</Badge></TableCell>
                                                    <TableCell>
                                                        <div className="flex items-center gap-0.5">
                                                            <Link
                                                                href={route('event-participants.team-form', ep.id)}
                                                                className="inline-flex h-6 items-center gap-1 rounded-md border px-1.5 text-[10px] font-medium text-muted-foreground transition hover:bg-primary hover:text-primary-foreground"
                                                                title="View team registration form"
                                                                aria-label={`View team form for ${participant.name} - ${evt.name}`}
                                                            >
                                                                <FileText className="size-3" /> Form
                                                            </Link>
                                                            {!isFacultyRepresentative && ep.status === 'pending' && (
                                                                <>
                                                                    <button onClick={() => approveRegistration(ep.id)}
                                                                        className="inline-flex size-6 items-center justify-center rounded-md text-emerald-600 hover:bg-emerald-600 hover:text-white transition" title="Approve">
                                                                        <Check className="size-3" />
                                                                    </button>
                                                                    <button onClick={() => setRejectTarget({ epId: ep.id, participantName: participant.name, eventName: evt.name })}
                                                                        className="inline-flex size-6 items-center justify-center rounded-md text-muted-foreground hover:bg-destructive hover:text-destructive-foreground transition" title="Reject">
                                                                        <CircleX className="size-3" />
                                                                    </button>
                                                                </>
                                                            )}
                                                            <button onClick={() => setUnregTarget({ id: ep.id, participantName: participant.name, eventName: evt.name })}
                                                                className="inline-flex size-6 items-center justify-center rounded-md text-muted-foreground hover:bg-destructive hover:text-destructive-foreground transition" title="Unregister">
                                                                <X className="size-3" />
                                                            </button>
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                                {isExpanded && (
                                                    <TableRow className="bg-muted/20">
                                                        <TableCell colSpan={isFacultyRepresentative ? 5 : 7} className="p-0">
                                                            <div className="px-4 py-3">
                                                                <div className="mb-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                                                                    <span className="font-semibold">Squad ({members.length})</span>
                                                                    <span className="text-blue-600">{maleCount} male</span>
                                                                    <span className="text-pink-600">{femaleCount} female</span>
                                                                    <span className="text-purple-600">{officialCount} official{officialCount !== 1 ? 's' : ''}</span>
                                                                </div>
                                                                {members.length > 0 ? (
                                                                    <div className="grid gap-1 sm:grid-cols-2 lg:grid-cols-3">
                                                                        {members.map((m) => {
                                                                            const rc = squadRoleConfig[m.role] ?? { label: m.role, class: 'bg-gray-100 text-gray-600' };
                                                                            return (
                                                                                <div key={m.id} className="flex items-center gap-2 rounded-md border bg-background px-2 py-1.5 text-xs">
                                                                                    <span className={`shrink-0 rounded-full px-1.5 py-0.5 text-[9px] font-medium ${rc.class}`}>{rc.label}</span>
                                                                                    <span className="truncate font-medium">{m.name}</span>
                                                                                    {m.matrix_no && <span className="ml-auto shrink-0 tabular-nums text-muted-foreground">{m.matrix_no}</span>}
                                                                                </div>
                                                                            );
                                                                        })}
                                                                    </div>
                                                                ) : (
                                                                    <p className="text-xs text-muted-foreground">No squad members added yet.</p>
                                                                )}
                                                            </div>
                                                        </TableCell>
                                                    </TableRow>
                                                )}
                                            </Fragment>
                                        );
                                    })}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )
            )}

            {/* === TAB: Events (Grid or Table) === */}
            {activeTab === 'events' && (
                events.length === 0 ? (
                    <Card><CardContent className="py-10 text-center text-sm text-muted-foreground">
                        {hasActiveFilters ? 'No matching events.' : 'No events available.'}
                    </CardContent></Card>
                ) : viewMode === 'table' ? (
                    <Card>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Event</TableHead>
                                        <TableHead>Sport / Category</TableHead>
                                        <TableHead>Tournament</TableHead>
                                        <TableHead>Registered</TableHead>
                                        <TableHead className="w-24" />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {events.map((evt) => {
                                        const regEntry = eventRegistry.find(e => e.event.id === evt.id);
                                        const isRegistered = regEntry?.isRegistered ?? false;
                                        const registrations = regEntry?.registrations ?? [];
                                        const deadlinePassed = (evt as any).registration_deadline && new Date((evt as any).registration_deadline) < new Date();
                                        return (
                                            <TableRow key={evt.id}>
                                                <TableCell className="font-medium text-xs">{evt.name}</TableCell>
                                                <TableCell className="text-xs text-muted-foreground">{evt.sport?.name}{evt.sport_category?.name ? ` / ${evt.sport_category.name}` : ''}</TableCell>
                                                <TableCell className="text-xs text-muted-foreground">{evt.tournament?.name || '-'}</TableCell>
                                                <TableCell className="text-xs">{isRegistered ? `${registrations.length} faculty` : '-'}</TableCell>
                                                <TableCell>
                                                    {isFacultyRepresentative ? (
                                                        isRegistered ? <span className="text-[10px] text-muted-foreground">Registered</span> : deadlinePassed
                                                            ? <span className="text-[10px] text-destructive">Deadline passed</span>
                                                            : <Button variant="outline" size="sm" onClick={() => quickRegister(evt.id)} className="h-6 text-[10px] px-2">Register</Button>
                                                    ) : (
                                                        <Button variant="outline" size="sm" onClick={() => setAddTarget({ id: '', name: evt.name })} className="h-6 text-[10px] px-2">Add</Button>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                        {events.map((evt) => {
                            const regEntry = eventRegistry.find(e => e.event.id === evt.id);
                            const isRegistered = regEntry?.isRegistered ?? false;
                            const registrations = regEntry?.registrations ?? [];
                            const deadlinePassed = (evt as any).registration_deadline && new Date((evt as any).registration_deadline) < new Date();

                            return (
                                <div key={evt.id} className={`rounded-lg border bg-card text-card-foreground shadow-xs overflow-hidden ${getSportBorder(evt.sport?.name)} border-l-4`}>
                                    <div className="px-3 py-2 flex items-center gap-2">
                                        <span className="text-base shrink-0">{getSportIcon(evt.sport?.name)}</span>
                                        <div className="min-w-0 flex-1 leading-tight">
                                            <div className="text-xs font-semibold truncate">{evt.name}</div>
                                            <div className="text-[10px] text-muted-foreground truncate">{evt.sport?.name}{evt.sport_category?.name ? ` · ${evt.sport_category.name}` : ''}</div>
                                        </div>
                                        {isFacultyRepresentative ? (
                                            !isRegistered && !deadlinePassed && (
                                                <Button variant="outline" size="sm" onClick={() => quickRegister(evt.id)} className="h-6 text-[10px] px-2 shrink-0">
                                                    <Plus className="size-3 mr-0.5" />Register
                                                </Button>
                                            )
                                        ) : (
                                            <Button variant="outline" size="sm" onClick={() => setAddTarget({ id: '', name: evt.name })} className="h-6 text-[10px] px-2 shrink-0">
                                                <Plus className="size-3 mr-0.5" />Add
                                            </Button>
                                        )}
                                    </div>
                                    {isRegistered && (
                                        <div className="border-t px-3 py-1.5 bg-muted/20">
                                            {registrations.map(({ participant: p, ep }) => {
                                                const cfg = statusConfig[ep.status] ?? statusConfig.pending;
                                                return (
                                                    <div key={ep.id} className="flex items-center gap-1.5 py-0.5 text-xs group">
                                                        <span className="truncate flex-1 min-w-0">{p.name}</span>
                                                        <Badge variant={cfg.variant} className="h-4 text-[9px] px-1 shrink-0">{cfg.label}</Badge>
                                                        <button onClick={() => setUnregTarget({ id: ep.id, participantName: p.name, eventName: evt.name })}
                                                            className="inline-flex size-4 items-center justify-center rounded-full text-muted-foreground opacity-0 group-hover:opacity-100 hover:bg-destructive hover:text-destructive-foreground shrink-0" title="Unregister">
                                                            <X className="size-2.5" />
                                                        </button>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                )
            )}

            <div className="mt-4">
                <Pagination paginator={participantsProp} />
            </div>

            <AddEventDialog open={!!addTarget} onClose={() => setAddTarget(null)}
                participantId={addTarget?.id ?? ''} participantName={addTarget?.name ?? ''}
                events={events}
                participants={isFacultyRepresentative ? undefined : participantsList} />

            <ConfirmUnregisterDialog open={!!unregTarget} onClose={() => setUnregTarget(null)}
                onConfirm={handleUnregister} participantName={unregTarget?.participantName ?? ''}
                eventName={unregTarget?.eventName ?? ''} />

            <ConfirmRejectDialog open={!!rejectTarget} onClose={() => setRejectTarget(null)}
                onConfirm={rejectRegistration} participantName={rejectTarget?.participantName ?? ''}
                eventName={rejectTarget?.eventName ?? ''} />
        </AuthenticatedLayout>
    );
}
