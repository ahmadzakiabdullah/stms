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
import { Ban, CalendarDays, Check, CheckCircle2, ChevronDown, CircleDashed, CircleX, Clock, ClipboardList, FileText, Filter, Inbox, LayoutGrid, List, Pencil, Phone, Plus, RotateCcw, Search, SearchX, Trash2, UserPlus, Users, X, XCircle } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Fragment, useEffect, useMemo, useRef, useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import { useI18n } from '@/lib/i18n';
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

const statusDot: Record<string, string> = {
    pending: 'bg-amber-500',
    confirmed: 'bg-emerald-500',
    withdrawn: 'bg-gray-400',
    disqualified: 'bg-red-500',
    rejected: 'bg-rose-500',
};

const statIcon: Record<string, { icon: LucideIcon; tint: string; iconClass: string }> = {
    '': { icon: ClipboardList, tint: 'bg-primary/10', iconClass: 'text-primary' },
    pending: { icon: Clock, tint: 'bg-amber-100', iconClass: 'text-amber-600' },
    confirmed: { icon: CheckCircle2, tint: 'bg-emerald-100', iconClass: 'text-emerald-600' },
    rejected: { icon: XCircle, tint: 'bg-rose-100', iconClass: 'text-rose-600' },
    withdrawn: { icon: CircleDashed, tint: 'bg-gray-100', iconClass: 'text-gray-500' },
    disqualified: { icon: Ban, tint: 'bg-red-100', iconClass: 'text-red-600' },
};

function initialsOf(name = ''): string {
    return name.split(' ').filter(Boolean).slice(0, 2).map((p) => p[0]?.toUpperCase()).join('');
}

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
    const { t } = useI18n();
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
                    <DialogTitle>{t('Register to Event')}</DialogTitle>
                    <DialogDescription>{participantName
                        ? <>{t('Add an event for...')} <strong>{participantName}</strong></>
                        : <>{t('Select a participant and choose an event...')}</>}</DialogDescription>
                </DialogHeader>
                <div className="grid gap-4 py-4">
                    {!participantId && participants && (
                        <div className="grid gap-2">
                            <Label htmlFor="dialog-participant">{t('Participant')}</Label>
                            <select id="dialog-participant" value={selectedParticipantId}
                                onChange={(e) => setSelectedParticipantId(e.target.value)}
                                className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm" required>
                                <option value="">{t('-- Select Participant --')}</option>
                                {participants.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                            </select>
                        </div>
                    )}
                    <div className="relative">
                        <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input placeholder={t('Search by event, sport, or tournament...')} value={search}
                            onChange={(e) => setSearch(e.target.value)} className="pl-9" />
                    </div>
                    <div className="flex items-center gap-2 text-xs text-muted-foreground">
                        <span>{t('Group by:')}</span>
                        <button type="button" onClick={() => setGroupBy('sport')}
                            className={`px-2 py-1 rounded ${groupBy === 'sport' ? 'bg-primary/10 text-primary font-medium' : 'hover:bg-muted'}`}>{t('Sport')}</button>
                        <button type="button" onClick={() => setGroupBy('tournament')}
                            className={`px-2 py-1 rounded ${groupBy === 'tournament' ? 'bg-primary/10 text-primary font-medium' : 'hover:bg-muted'}`}>{t('Tournament')}</button>
                    </div>
                    <div className="grid gap-2">
                        <Label>{t('Available Events')}</Label>
                        <div className="max-h-60 overflow-y-auto rounded-md border">
                            {filtered.length === 0 && (
                                <p className="p-3 text-sm text-muted-foreground">{search ? t('No matching events.') : t('Already registered for all available events.')}</p>
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
                    <Button variant="outline" onClick={() => { setSelectedEventId(''); setSelectedParticipantId(''); setSearch(''); onClose(); }}>{t('Cancel')}</Button>
                    <Button onClick={handleRegister} disabled={!selectedEventId}><Plus className="mr-2 size-4" />{t('Register')}</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function ConfirmUnregisterDialog({ open, onClose, onConfirm, participantName, eventName }: {
    open: boolean; onClose: () => void; onConfirm: () => void; participantName: string; eventName: string;
}) {
    const { t } = useI18n();
    return (
        <Dialog open={open} onOpenChange={(o) => { if (!o) onClose(); }}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('Unregister')} {participantName}?</DialogTitle>
                    <DialogDescription>Remove <strong>{participantName}</strong> from <strong>{eventName}</strong>? This action cannot be undone.</DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" onClick={onClose}>{t('Cancel')}</Button>
                    <Button variant="destructive" onClick={onConfirm}>{t('Yes, Unregister')}</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function ConfirmRejectDialog({ open, onClose, onConfirm, participantName, eventName }: {
    open: boolean; onClose: () => void; onConfirm: () => void; participantName: string; eventName: string;
}) {
    const { t } = useI18n();
    return (
        <Dialog open={open} onOpenChange={(o) => { if (!o) onClose(); }}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('Reject registration?')}</DialogTitle>
                    <DialogDescription>Reject <strong>{participantName}</strong> from <strong>{eventName}</strong>? The faculty representative will be notified.</DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" onClick={onClose}>{t('Cancel')}</Button>
                    <Button variant="destructive" onClick={onConfirm}>{t('Yes, Reject')}</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

const SQUAD_ROLE_KEYS = ['manager', 'assistant_manager', 'coach', 'physio', 'athlete_male', 'athlete_female'] as SquadMember['role'][];

const selectClass = 'flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50';

function Field({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="grid gap-1">
            <Label className="text-[11px] font-medium text-muted-foreground">{label}</Label>
            {children}
        </div>
    );
}

function SquadAddForm({ epId }: { epId: string }) {
    const { t } = useI18n();
    const [name, setName] = useState('');
    const [role, setRole] = useState<SquadMember['role']>('athlete_male');
    const [matrixNo, setMatrixNo] = useState('');
    const [identificationNo, setIdentificationNo] = useState('');
    const [phone, setPhone] = useState('');
    const [busy, setBusy] = useState(false);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        setBusy(true);
        router.post(route('event-participants.squad.store', epId), {
            name, role, matrix_no: matrixNo, identification_no: identificationNo || null, phone: phone || null,
        }, {
            preserveScroll: true, preserveState: true,
            onSuccess: () => { setName(''); setMatrixNo(''); setIdentificationNo(''); setPhone(''); },
            onFinish: () => setBusy(false),
        });
    };

    return (
        <form onSubmit={submit} className="mt-4 rounded-lg border bg-muted/20 p-3">
            <div className="mb-3 flex flex-wrap items-center justify-between gap-1">
                <div className="flex items-center gap-2 text-sm font-semibold">
                    <UserPlus className="size-4 text-primary" /> {t('Add Squad Member')}
                </div>
                <span className="text-[11px] text-muted-foreground">{t('Phone number is required for officials.')}</span>
            </div>
            <div className="grid gap-2.5 sm:grid-cols-2 lg:grid-cols-6">
                <div className="sm:col-span-2"><Field label={t('Full Name')}><Input value={name} onChange={(e) => setName(e.target.value)} placeholder={t('e.g. Ali bin Ahmad')} required /></Field></div>
                <div><Field label={t('Role')}>
                    <select value={role} onChange={(e) => setRole(e.target.value as SquadMember['role'])} className={selectClass}>
                        {SQUAD_ROLE_KEYS.map((k) => <option key={k} value={k}>{squadRoleConfig[k].label}</option>)}
                    </select>
                </Field></div>
                <div><Field label={t('Matrix No.')}><Input value={matrixNo} onChange={(e) => setMatrixNo(e.target.value)} placeholder={t('e.g. B062310001')} required /></Field></div>
                <div><Field label={t('IC / Passport')}><Input value={identificationNo} onChange={(e) => setIdentificationNo(e.target.value)} placeholder={t('Optional')} /></Field></div>
                <div><Field label={t('Phone')}><Input value={phone} onChange={(e) => setPhone(e.target.value)} placeholder={t('e.g. 012-3456789')} /></Field></div>
            </div>
            <div className="mt-3 flex justify-end">
                <Button type="submit" disabled={busy}>
                    <Plus className="size-4 mr-1.5" /> {t('Add Member')}
                </Button>
            </div>
        </form>
    );
}

function SquadEditForm({ epId, member, onCancel }: { epId: string; member: SquadMember; onCancel: () => void }) {
    const { t } = useI18n();
    const [name, setName] = useState(member.name);
    const [role, setRole] = useState<SquadMember['role']>(member.role);
    const [matrixNo, setMatrixNo] = useState(member.matrix_no ?? '');
    const [identificationNo, setIdentificationNo] = useState(member.identification_no ?? '');
    const [phone, setPhone] = useState(member.phone ?? '');
    const [busy, setBusy] = useState(false);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        setBusy(true);
        router.put(route('event-participants.squad.update', [epId, member.id]), {
            name, role, matrix_no: matrixNo, identification_no: identificationNo || null, phone: phone || null,
        }, {
            preserveScroll: true, preserveState: true,
            onSuccess: onCancel,
            onFinish: () => setBusy(false),
        });
    };

    return (
        <form onSubmit={submit} className="rounded-md border bg-background p-2.5">
            <div className="mb-2 flex items-center justify-between">
                <span className="text-xs font-semibold text-muted-foreground">{t('Edit Squad Member')}</span>
                <button type="button" onClick={onCancel} className="text-xs text-muted-foreground hover:text-foreground">{t('Cancel')}</button>
            </div>
            <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-6">
                <div className="sm:col-span-2"><Field label={t('Full Name')}><Input value={name} onChange={(e) => setName(e.target.value)} required className="h-8 text-sm" /></Field></div>
                <div><Field label={t('Role')}>
                    <select value={role} onChange={(e) => setRole(e.target.value as SquadMember['role'])} className={selectClass + ' h-8'}>
                        {SQUAD_ROLE_KEYS.map((k) => <option key={k} value={k}>{squadRoleConfig[k].label}</option>)}
                    </select>
                </Field></div>
                <div><Field label={t('Matrix No.')}><Input value={matrixNo} onChange={(e) => setMatrixNo(e.target.value)} required className="h-8 text-sm" /></Field></div>
                <div><Field label={t('IC / Passport')}><Input value={identificationNo} onChange={(e) => setIdentificationNo(e.target.value)} className="h-8 text-sm" /></Field></div>
                <div><Field label={t('Phone')}><Input value={phone} onChange={(e) => setPhone(e.target.value)} className="h-8 text-sm" /></Field></div>
            </div>
            <div className="mt-2.5 flex justify-end gap-2">
                <Button type="submit" size="sm" disabled={busy}><Check className="size-3.5 mr-1" /> {t('Save')}</Button>
            </div>
        </form>
    );
}

function ConfirmSquadDeleteDialog({ open, onClose, onConfirm, memberName }: {
    open: boolean; onClose: () => void; onConfirm: () => void; memberName: string;
}) {
    const { t } = useI18n();
    return (
        <Dialog open={open} onOpenChange={(o) => { if (!o) onClose(); }}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('Remove squad member?')}</DialogTitle>
                    <DialogDescription>Remove <strong>{memberName}</strong> from the squad? This action cannot be undone.</DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" onClick={onClose}>{t('Cancel')}</Button>
                    <Button variant="destructive" onClick={onConfirm}>{t('Yes, Remove')}</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export default function EventParticipantsIndex({
    participants: participantsProp, events: eventsProp = [], faculties: facultiesProp = [],
    isFacultyRepresentative = false, statusCounts: statusCountsProp = {},
}: EventParticipantsIndexProps) {
    const { flash, auth } = usePage().props;
    const { t } = useI18n();
    const userRoles = auth?.user?.roles?.map((r) => r.name) ?? [];
    const canManageSquad = userRoles.includes('super-admin') || userRoles.includes('org-admin');
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
            { key: '', label: t('All'), count: totalRegistrations },
            { key: 'pending', label: t('Pending'), count: statusCounts.pending ?? 0 },
            { key: 'confirmed', label: t('Confirmed'), count: statusCounts.confirmed ?? 0 },
            { key: 'rejected', label: t('Rejected'), count: statusCounts.rejected ?? 0 },
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
    const [editingMemberId, setEditingMemberId] = useState<string | null>(null);
    const [squadDeleteTarget, setSquadDeleteTarget] = useState<{ epId: string; memberId: string; memberName: string } | null>(null);

    const handleUnregister = () => {
        if (!unregTarget) return;
        router.delete(route('event-participants.destroy', unregTarget.id), {
            preserveScroll: true, onSuccess: () => setUnregTarget(null),
        });
    };

    const approveRegistration = (epId: string) => {
        router.post(route('event-participants.status-post', epId), { status: 'confirmed' }, {
            preserveScroll: true,
        });
    };

    const rejectRegistration = () => {
        if (!rejectTarget) return;
        router.post(route('event-participants.status-post', rejectTarget.epId), { status: 'rejected' }, {
            preserveScroll: true, onSuccess: () => setRejectTarget(null),
        });
    };

    const handleSquadDelete = () => {
        if (!squadDeleteTarget) return;
        router.delete(route('event-participants.squad.destroy', [squadDeleteTarget.epId, squadDeleteTarget.memberId]), {
            preserveScroll: true, onSuccess: () => setSquadDeleteTarget(null),
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
        ? { registrations: t('My Registrations'), events: t('Available Events') }
        : { registrations: t('All Registrations'), events: t('All Events') };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">{t('Registrations & Squads')}</h1>
                        <p className="text-sm text-muted-foreground">
                            {isFacultyRepresentative ? t('Manage your faculty\'s event participation') : t('Overview of every faculty\'s event participation')}
                        </p>
                    </div>
                    {isFacultyRepresentative ? (
                        <Button onClick={() => setActiveTab('events')}>
                            <Plus className="size-4 mr-1.5" /> {t('Register to Event')}
                        </Button>
                    ) : (
                        <Button onClick={() => setAddTarget({ id: '', name: '' })}>
                            <UserPlus className="size-4 mr-1.5" /> {t('New Registration')}
                        </Button>
                    )}
                </div>
            }
        >
            <Head title={t('Registrations & Squads')} />

            {flash?.success && (
                <div className="mb-4 flex items-start gap-2.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3.5 py-2.5 text-sm text-emerald-800">
                    <CheckCircle2 className="mt-0.5 size-4 shrink-0" />
                    <span>{flash.success}</span>
                </div>
            )}
            {flash?.error && (
                <div className="mb-4 flex items-start gap-2.5 rounded-lg border border-red-200 bg-red-50 px-3.5 py-2.5 text-sm text-red-800">
                    <XCircle className="mt-0.5 size-4 shrink-0" />
                    <span>{flash.error}</span>
                </div>
            )}

            {/* Status stat cards */}
            <div className="mb-4 grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-6">
                {statusCards.map((card) => {
                    const isActive = filterStatus === card.key;
                    const cfg = statIcon[card.key] ?? statIcon[''];
                    const Icon = cfg.icon;
                    return (
                        <button key={card.key || 'all'} type="button" onClick={() => handleStatusChange(isActive ? '' : card.key)}
                            title={isActive ? t('Clear status filter') : `Filter by ${card.label.toLowerCase()}`}
                            className={`flex items-center gap-3 rounded-xl border bg-card px-3.5 py-3 text-left transition ${isActive ? 'border-primary bg-primary/[0.04] ring-1 ring-primary' : 'hover:border-primary/40 hover:bg-muted/40'}`}>
                            <span className={`flex size-9 shrink-0 items-center justify-center rounded-lg ${cfg.tint}`}>
                                <Icon className={`size-4 ${cfg.iconClass}`} />
                            </span>
                            <span className="min-w-0">
                                <span className="block text-xl font-semibold leading-none tabular-nums">{card.count}</span>
                                <span className="mt-1 block truncate text-[11px] font-medium text-muted-foreground">{card.label}</span>
                            </span>
                        </button>
                    );
                })}
                <div className="flex items-center gap-3 rounded-xl border bg-card px-3.5 py-3">
                    <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-sky-100">
                        <CalendarDays className="size-4 text-sky-600" />
                    </span>
                    <span className="min-w-0">
                        <span className="block text-xl font-semibold leading-none tabular-nums">{events.length}</span>
                        <span className="mt-1 block truncate text-[11px] font-medium text-muted-foreground">Events</span>
                    </span>
                </div>
            </div>

            {/* Tabs + View toggle */}
            <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                <div className="inline-flex items-center gap-0.5 rounded-lg border bg-muted/20 p-0.5">
                    <button onClick={() => setActiveTab('registrations')}
                        className={`inline-flex items-center gap-1.5 rounded-md px-3.5 py-2 text-sm font-medium transition ${activeTab === 'registrations' ? 'bg-background text-foreground shadow-xs' : 'text-muted-foreground hover:text-foreground'}`}>
                        {tabLabel.registrations}
                        <span className={`rounded-full px-1.5 py-0.5 text-[10px] font-semibold tabular-nums ${activeTab === 'registrations' ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'}`}>
                            {registrationRows.length}
                        </span>
                    </button>
                    <button onClick={() => setActiveTab('events')}
                        className={`inline-flex items-center gap-1.5 rounded-md px-3.5 py-2 text-sm font-medium transition ${activeTab === 'events' ? 'bg-background text-foreground shadow-xs' : 'text-muted-foreground hover:text-foreground'}`}>
                        {tabLabel.events}
                        <span className={`rounded-full px-1.5 py-0.5 text-[10px] font-semibold tabular-nums ${activeTab === 'events' ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'}`}>
                            {eventRegistry.length}
                        </span>
                    </button>
                </div>

                {activeTab === 'events' && (
                    <div className="inline-flex items-center gap-0.5 rounded-lg border bg-muted/20 p-0.5">
                        <button onClick={() => setViewMode('grid')}
                            className={`rounded-md p-1.5 transition ${viewMode === 'grid' ? 'bg-background text-foreground shadow-xs' : 'text-muted-foreground hover:text-foreground'}`}
                            aria-label="Grid view" title="Grid view"><LayoutGrid className="size-3.5" /></button>
                        <button onClick={() => setViewMode('table')}
                            className={`rounded-md p-1.5 transition ${viewMode === 'table' ? 'bg-background text-foreground shadow-xs' : 'text-muted-foreground hover:text-foreground'}`}
                            aria-label="Table view" title="Table view"><List className="size-3.5" /></button>
                    </div>
                )}
            </div>

            {/* Filters toolbar */}
            <div className="mb-4 flex flex-wrap items-center gap-2 rounded-xl border bg-card p-2.5">
                <span className="hidden items-center gap-1.5 pl-1 text-xs font-semibold uppercase tracking-wide text-muted-foreground md:inline-flex">
                    <Filter className="size-3.5" /> {t('Filters')}
                </span>
                <div className="relative min-w-52 flex-1">
                    <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input placeholder={t('Search event, sport, category...')} value={searchInput}
                        onChange={(e) => handleSearchChange(e.target.value)}
                        className="h-9 pl-9" />
                </div>

                <select value={filterSportId} onChange={(e) => handleSportChange(e.target.value)}
                    className="h-9 rounded-md border border-input bg-background px-2.5 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <option value="">{t('All Sports')}</option>
                    {sports.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                </select>

                {categories.length > 0 && (
                    <select value={filterCategoryId} onChange={(e) => handleCategoryChange(e.target.value)}
                        className="h-9 rounded-md border border-input bg-background px-2.5 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <option value="">{t('All Categories')}</option>
                        {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                    </select>
                )}

                {!isFacultyRepresentative && faculties.length > 0 && (
                    <select value={filterParticipantId} onChange={(e) => handleParticipantChange(e.target.value)}
                        className="h-9 rounded-md border border-input bg-background px-2.5 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <option value="">{t('All Faculties')}</option>
                        {faculties.map((f) => <option key={f.id} value={f.id}>{f.name}</option>)}
                    </select>
                )}

                <select value={filterStatus} onChange={(e) => handleStatusChange(e.target.value)}
                    className="h-9 rounded-md border border-input bg-background px-2.5 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <option value="">{t('All Statuses')}</option>
                    {Object.entries(statusConfig).map(([key, cfg]) => (
                        <option key={key} value={key}>{cfg.label}</option>
                    ))}
                </select>

                {hasActiveFilters && (
                    <Button variant="outline" size="sm" onClick={handleClearFilters} className="h-9 text-xs">
                        <RotateCcw className="size-3.5 mr-1" /> {t('Clear filters')}
                    </Button>
                )}
            </div>

            {/* === TAB: Registrations (Table) === */}
            {activeTab === 'registrations' && (
                registrationRows.length === 0 ? (
                    <Card><CardContent className="flex flex-col items-center gap-3 px-6 py-14 text-center">
                        <span className="flex size-12 items-center justify-center rounded-full bg-muted">
                            {hasActiveFilters
                                ? <SearchX className="size-5 text-muted-foreground" />
                                : <Inbox className="size-5 text-muted-foreground" />}
                        </span>
                        <div>
                            <p className="text-sm font-semibold">
                                {hasActiveFilters ? t('No matching registrations') : t('No registrations yet')}
                            </p>
                            <p className="mt-1 text-xs text-muted-foreground">
                                {hasActiveFilters
                                    ? t('Try adjusting your search or filters.')
                                    : isFacultyRepresentative
                                        ? t('Browse available events and register your faculty.')
                                        : t('Register the first faculty to an event to get started.')}
                            </p>
                        </div>
                        {hasActiveFilters ? (
                            <Button variant="outline" size="sm" onClick={handleClearFilters}>
                                <RotateCcw className="size-3.5 mr-1" /> {t('Clear filters')}
                            </Button>
                        ) : isFacultyRepresentative ? (
                            <Button size="sm" onClick={() => setActiveTab('events')}>
                                <Plus className="size-3.5 mr-1" /> {t('Browse Events')}
                            </Button>
                        ) : (
                            <Button size="sm" onClick={() => setAddTarget({ id: '', name: '' })}>
                                <UserPlus className="size-3.5 mr-1" /> {t('New Registration')}
                            </Button>
                        )}
                    </CardContent></Card>
                ) : (
                    <Card>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow className="hover:bg-transparent">
                                        <TableHead>{t('Event')}</TableHead>
                                        {!isFacultyRepresentative && <TableHead>{t('Faculty')}</TableHead>}
                                        {!isFacultyRepresentative && <TableHead className="w-24">{t('Squad')}</TableHead>}
                                        <TableHead>{t('Status')}</TableHead>
                                        <TableHead className="w-44 text-right">{t('Actions')}</TableHead>
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
                                        const isConfirmed = ep.status === 'confirmed';
                                        const canManageThisSquad = canManageSquad && isConfirmed;
                                        return (
                                            <Fragment key={ep.id}>
                                                <TableRow className={isExpanded ? 'bg-muted/40' : undefined}>
                                                    <TableCell>
                                                        <div className="flex items-center gap-2.5">
                                                            <span className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-muted text-sm">{getSportIcon(evt.sport?.name)}</span>
                                                            <div className="min-w-0">
                                                                <div className="truncate text-sm font-medium">{evt.name}</div>
                                                                <div className="truncate text-xs text-muted-foreground">
                                                                    {evt.sport?.name}{evt.sport_category?.name ? ` · ${evt.sport_category.name}` : ''}{evt.tournament?.name ? ` · ${evt.tournament.name}` : ''}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </TableCell>
                                                    {!isFacultyRepresentative && (
                                                        <TableCell>
                                                            <div className="flex items-center gap-2">
                                                                <span className="flex size-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-[10px] font-semibold text-primary">
                                                                    {initialsOf(participant.name)}
                                                                </span>
                                                                <span className="truncate text-sm">{participant.name}</span>
                                                            </div>
                                                        </TableCell>
                                                    )}
                                                    {!isFacultyRepresentative && (
                                                        <TableCell>
                                                            {(members.length > 0 || canManageThisSquad) ? (
                                                                <button
                                                                    onClick={() => { setExpandedEp(isExpanded ? null : ep.id); setEditingMemberId(null); }}
                                                                    className={`inline-flex items-center gap-1.5 rounded-md border px-2 py-1 text-xs font-medium transition ${isExpanded ? 'border-primary bg-primary/5 text-primary' : 'text-muted-foreground hover:bg-muted hover:text-foreground'}`}
                                                                    title={canManageThisSquad ? t('Manage squad members') : t('View squad members')}
                                                                >
                                                                    <Users className="size-3.5" />
                                                                    {members.length}
                                                                    <ChevronDown className={`size-3 transition-transform ${isExpanded ? 'rotate-180' : ''}`} />
                                                                </button>
                                                            ) : (
                                                                <span className="text-xs text-muted-foreground">—</span>
                                                            )}
                                                        </TableCell>
                                                    )}
                                                    <TableCell>
                                                        <Badge variant={cfg.variant} className="gap-1.5 px-2 py-0.5 text-[11px] font-medium">
                                                            <span className={`size-1.5 rounded-full ${statusDot[ep.status] ?? 'bg-muted-foreground'}`} />
                                                            {cfg.label}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex items-center justify-end gap-1">
                                                            <Link
                                                                href={route('event-participants.team-form', ep.id)}
                                                                className="inline-flex size-8 items-center justify-center rounded-md border border-input text-muted-foreground transition hover:bg-primary hover:text-primary-foreground"
                                                                 title={t('Team registration form')}
                                                                aria-label={`View team form for ${participant.name} - ${evt.name}`}
                                                            >
                                                                <FileText className="size-3.5" />
                                                            </Link>
                                                            {!isFacultyRepresentative && ep.status === 'pending' && (
                                                                <>
                                                                    <button onClick={() => approveRegistration(ep.id)}
                                                                        className="inline-flex size-8 items-center justify-center rounded-md border border-emerald-200 text-emerald-600 transition hover:bg-emerald-600 hover:text-white"                                                                          aria-label={t('Approve')} title={t('Approve')}>
                                                                        <Check className="size-3.5" />
                                                                    </button>
                                                                    <button onClick={() => setRejectTarget({ epId: ep.id, participantName: participant.name, eventName: evt.name })}
                                                                        className="inline-flex size-8 items-center justify-center rounded-md border border-rose-200 text-rose-600 transition hover:bg-rose-600 hover:text-white"                                                                          aria-label={t('Reject')} title={t('Reject')}>
                                                                        <CircleX className="size-3.5" />
                                                                    </button>
                                                                </>
                                                            )}
                                                             <button onClick={() => setUnregTarget({ id: ep.id, participantName: participant.name, eventName: evt.name })}
                                                                 className="inline-flex size-8 items-center justify-center rounded-md border border-input text-muted-foreground transition hover:bg-destructive hover:text-destructive-foreground" aria-label={t('Unregister')} title={t('Unregister')}>
                                                                <X className="size-3.5" />
                                                            </button>
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                                {isExpanded && (
                                                    <TableRow className="bg-muted/20">
                                                        <TableCell colSpan={isFacultyRepresentative ? 3 : 5} className="p-0">
                                                            <div className="px-4 py-4">
                                                                <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                                                                    <div className="flex items-center gap-2">
                                                                        <Users className="size-4 text-primary" />
                                                                        <span className="text-sm font-semibold">Squad Members</span>
                                                                        <span className="rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-semibold text-primary tabular-nums">{members.length}</span>
                                                                    </div>
                                                                    <div className="flex items-center gap-1.5 text-[11px] font-medium">
                                                                        <span className="rounded-full bg-blue-100 px-2 py-0.5 text-blue-700">{maleCount} Male</span>
                                                                        <span className="rounded-full bg-pink-100 px-2 py-0.5 text-pink-700">{femaleCount} Female</span>
                                                                        <span className="rounded-full bg-purple-100 px-2 py-0.5 text-purple-700">{officialCount} Official{officialCount !== 1 ? 's' : ''}</span>
                                                                    </div>
                                                                </div>
                                                                {members.length > 0 ? (
                                                                    <div className="divide-y overflow-hidden rounded-lg border bg-background">
                                                                        {members.map((m) => {
                                                                            const rc = squadRoleConfig[m.role] ?? { label: m.role, class: 'bg-gray-100 text-gray-600' };
                                                                            if (editingMemberId === m.id) {
                                                                                return (
                                                                                    <div key={m.id} className="px-3 py-2">
                                                                                        <SquadEditForm epId={ep.id} member={m}
                                                                                            onCancel={() => setEditingMemberId(null)} />
                                                                                    </div>
                                                                                );
                                                                            }
                                                                            return (
                                                                                <div key={m.id} className="group flex flex-wrap items-center gap-x-3 gap-y-1 px-3 py-2">
                                                                                    <span className={`w-28 shrink-0 rounded-full px-2 py-0.5 text-center text-[10px] font-semibold ${rc.class}`}>{rc.label}</span>
                                                                                    <span className="text-sm font-medium">{m.name}</span>
                                                                                    {m.matrix_no && <span className="tabular-nums text-xs text-muted-foreground">{m.matrix_no}</span>}
                                                                                    {m.phone && <span className="inline-flex items-center gap-1 text-xs text-muted-foreground"><Phone className="size-3" />{m.phone}</span>}
                                                                                    {canManageThisSquad && (
                                                                                        <span className="ml-auto flex items-center gap-1 opacity-100 transition sm:opacity-0 sm:group-hover:opacity-100">
                                                                                            <Button variant="outline" size="sm" className="h-7 px-2 text-xs" onClick={() => setEditingMemberId(m.id)}>
                                                                                             <Pencil className="size-3 mr-1" /> {t('Edit')}
                                                                                             </Button>
                                                                                             <Button variant="outline" size="sm" className="h-7 px-2 text-xs text-destructive hover:bg-destructive hover:text-destructive-foreground"
                                                                                                 onClick={() => setSquadDeleteTarget({ epId: ep.id, memberId: m.id, memberName: m.name })}>
                                                                                                 <Trash2 className="size-3 mr-1" /> {t('Remove')}
                                                                                            </Button>
                                                                                        </span>
                                                                                    )}
                                                                                </div>
                                                                            );
                                                                        })}
                                                                    </div>
                                                                ) : (
                                                                    <div className="rounded-lg border border-dashed bg-background px-4 py-6 text-center">
                                                                        <p className="text-sm font-medium text-muted-foreground">No squad members yet</p>
                                                                        <p className="mt-0.5 text-xs text-muted-foreground/70">Officials should be added first, followed by athletes.</p>
                                                                    </div>
                                                                )}
                                                                {!isConfirmed && canManageSquad && (
                                                                    <p className="mt-3 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                                                        <Clock className="mt-0.5 size-3.5 shrink-0" />
                                                                        <span><strong>Pending approval.</strong> Squad members can only be added after this registration is confirmed.</span>
                                                                    </p>
                                                                )}
                                                                {canManageThisSquad && <SquadAddForm epId={ep.id} />}
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
                    <Card><CardContent className="flex flex-col items-center gap-3 px-6 py-14 text-center">
                        <span className="flex size-12 items-center justify-center rounded-full bg-muted">
                            <CalendarDays className="size-5 text-muted-foreground" />
                        </span>
                        <div>
                            <p className="text-sm font-semibold">No events available</p>
                            <p className="mt-1 text-xs text-muted-foreground">Events will appear here once they are created.</p>
                        </div>
                    </CardContent></Card>
                ) : viewMode === 'table' ? (
                    <Card>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>{t('Event')}</TableHead>
                                        <TableHead>{t('Sport / Category')}</TableHead>
                                        <TableHead>{t('Tournament')}</TableHead>
                                        <TableHead>{t('Registered')}</TableHead>
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
                                                <TableCell className="text-sm font-medium">{evt.name}</TableCell>
                                                <TableCell className="text-xs text-muted-foreground">{evt.sport?.name}{evt.sport_category?.name ? ` / ${evt.sport_category.name}` : ''}</TableCell>
                                                <TableCell className="text-xs text-muted-foreground">{evt.tournament?.name || '-'}</TableCell>
                                                <TableCell className="text-xs">{isRegistered ? `${registrations.length} faculty` : '-'}</TableCell>
                                                <TableCell>
                                                    {isFacultyRepresentative ? (
                                                         isRegistered ? <span className="text-xs font-medium text-emerald-600">Registered</span> : deadlinePassed
                                                             ? <span className="text-xs text-destructive">Deadline passed</span>
                                                             : <Button variant="outline" size="sm" onClick={() => quickRegister(evt.id)} className="h-7 text-xs">{t('Register')}</Button>
                                                    ) : (
                                                         <Button variant="outline" size="sm" onClick={() => setAddTarget({ id: '', name: evt.name })} className="h-7 text-xs">
                                                             <Plus className="size-3 mr-1" /> {t('Add')}
                                                         </Button>
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
                                    <div className="px-3 py-2.5 flex items-center gap-2">
                                        <span className="text-lg shrink-0">{getSportIcon(evt.sport?.name)}</span>
                                        <div className="min-w-0 flex-1 leading-tight">
                                            <div className="text-sm font-semibold truncate">{evt.name}</div>
                                            <div className="text-[11px] text-muted-foreground truncate">{evt.sport?.name}{evt.sport_category?.name ? ` · ${evt.sport_category.name}` : ''}</div>
                                        </div>
                                        {isFacultyRepresentative ? (
                                            !isRegistered && !deadlinePassed && (
                                                <Button variant="outline" size="sm" onClick={() => quickRegister(evt.id)} className="h-7 text-xs px-2.5 shrink-0">
                                                    <Plus className="size-3 mr-0.5" />{t('Register')}
                                                </Button>
                                            )
                                        ) : (
                                            <Button variant="outline" size="sm" onClick={() => setAddTarget({ id: '', name: evt.name })} className="h-7 text-xs px-2.5 shrink-0">
                                                <Plus className="size-3 mr-0.5" />{t('Add')}
                                            </Button>
                                        )}
                                    </div>
                                    {isRegistered && (
                                        <div className="border-t px-3 py-1.5 bg-muted/20">
                                            {registrations.map(({ participant: p, ep }) => {
                                                const cfg = statusConfig[ep.status] ?? statusConfig.pending;
                                                return (
                                                    <div key={ep.id} className="flex items-center gap-2 py-1 text-xs group">
                                                        <span className="truncate flex-1 min-w-0 font-medium">{p.name}</span>
                                                        <Badge variant={cfg.variant} className="gap-1 h-4 text-[9px] px-1 shrink-0">
                                                            <span className={`size-1 rounded-full ${statusDot[ep.status] ?? 'bg-muted-foreground'}`} />
                                                            {cfg.label}
                                                        </Badge>
                                                        <button onClick={() => setUnregTarget({ id: ep.id, participantName: p.name, eventName: evt.name })}
                                                            className="inline-flex size-4 items-center justify-center rounded-full text-muted-foreground opacity-0 group-hover:opacity-100 hover:bg-destructive hover:text-destructive-foreground shrink-0" aria-label={t('Unregister')} title={t('Unregister')}>
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

            <ConfirmSquadDeleteDialog open={!!squadDeleteTarget} onClose={() => setSquadDeleteTarget(null)}
                onConfirm={handleSquadDelete} memberName={squadDeleteTarget?.memberName ?? ''} />
        </AuthenticatedLayout>
    );
}
