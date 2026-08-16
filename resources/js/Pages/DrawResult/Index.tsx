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
import { useEffect, useState } from 'react';
import type { Event, Pool, Fixture, Participant, EventParticipant, Flash } from '@/types';
import { formatDateTime, useI18n } from '@/lib/i18n';

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

const statusBadge = (status: string, t: (key: string) => string) => {
    const s = statusStyles[status] || { label: status, className: 'border-slate-200 bg-slate-50 text-slate-600' };
    return <Badge variant="outline" className={`border ${s.className}`}>{t(s.label)}</Badge>;
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

const Matchup = ({ home, away }: { home?: Participant; away?: Participant }) => (
    <div className="grid w-full grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-2 sm:gap-3">
        <div className="flex min-w-0 items-center justify-end gap-2 text-right">
            <span className="truncate text-sm font-semibold" title={participantFullName(home)}>{participantName(home)}</span>
            <ParticipantAvatar participant={home} />
        </div>
        <span className="rounded-full bg-slate-100 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-slate-500">VS</span>
        <div className="flex min-w-0 items-center gap-2 text-left">
            <ParticipantAvatar participant={away} />
            <span className="truncate text-sm font-semibold" title={participantFullName(away)}>{participantName(away)}</span>
        </div>
    </div>
);

interface StatTileProps {
    icon: typeof Users;
    label: string;
    value: number;
    accent: string;
}

const StatTile = ({ icon: Icon, label, value, accent }: StatTileProps) => (
    <div className="flex min-w-0 items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-3 shadow-sm sm:px-4">
        <span className={`flex size-9 items-center justify-center rounded-lg ${accent}`}>
            <Icon className="size-5" />
        </span>
        <div className="min-w-0">
            <p className="text-xl font-bold leading-none">{value}</p>
            <p className="mt-1 truncate text-xs text-muted-foreground">{label}</p>
        </div>
    </div>
);

export default function DrawResult({ event, pools: initialPools, canEdit, drawVersions }: DrawResultProps) {
    const { flash } = usePage<{ flash: Flash }>().props;
    const { t, locale } = useI18n();
    const [editing, setEditing] = useState(false);
    const [pools, setPools] = useState(initialPools);
    const [pendingMoves, setPendingMoves] = useState<Record<string, string>>({});
    const [pendingSeeds, setPendingSeeds] = useState<Record<string, number>>({});
    const [isSaving, setIsSaving] = useState(false);
    const [restoreVersion, setRestoreVersion] = useState<DrawResultProps['drawVersions'][number] | null>(null);

    useEffect(() => {
        setPools(initialPools);
    }, [initialPools]);

    const allParticipants = pools.flatMap(p =>
        p.event_participants.map(ep => ({ ...ep, currentPoolId: p.id }))
    );

    const totalParticipants = allParticipants.length;
    const allFixtures = pools
        .flatMap(pool => pool.fixtures.map(fixture => ({ ...fixture, poolName: pool.name })))
        .sort((first, second) => first.match_number - second.match_number);
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
        const entryIds = new Set([...Object.keys(pendingMoves), ...Object.keys(pendingSeeds)]);
        const entries = Array.from(entryIds).map(id => [id, pendingMoves[id] ?? pools.find(pool => pool.event_participants.some(item => item.id === id))?.id ?? ''] as const);
        if (entries.length === 0) {
            setEditing(false);
            return;
        }

        let i = 0;
        setIsSaving(true);
        const processNext = () => {
            if (i >= entries.length) {
                setEditing(false);
                setPendingMoves({});
                setPendingSeeds({});
                setIsSaving(false);
                router.reload({ only: ['pools', 'flash'] });
                return;
            }
            const [epId, targetPoolId] = entries[i];
            i++;
            router.post(route('events.draw.move-participant', event.slug), {
                event_participant_id: epId,
                target_pool_id: targetPoolId,
                seed_number: pendingSeeds[epId] ?? pools.flatMap(p => p.event_participants).find(item => item.id === epId)?.seed_number,
            }, {
                preserveScroll: true,
                onFinish: () => processNext(),
                onError: () => processNext(),
            });
        };
        processNext();
    };

    const handleCancel = () => {
        if (isSaving) return;
        setPendingMoves({});
        setPendingSeeds({});
        setEditing(false);
        setPools(initialPools);
    };

    const handleGenerateFixtures = () => {
        router.post(route('events.generate-fixtures', event.slug), {}, { preserveScroll: true });
    };

    const handleAutoAssign = () => {
        router.post(route('events.draw', event.slug), { format: event.format || 'group_knockout' }, { preserveScroll: true });
    };

    const showGenerateFixtures = pools.length > 0 && totalFixtures === 0;
    const currentStep = pools.length === 0 ? 1 : totalFixtures === 0 ? 2 : 3;
    const pendingChangeCount = new Set([...Object.keys(pendingMoves), ...Object.keys(pendingSeeds)]).size;

    const workflowSteps = [
        { number: 1, label: t('Group draw'), description: t('Assign participants') },
        { number: 2, label: t('Fixtures'), description: t('Create match schedule') },
        { number: 3, label: t('Competition'), description: t('Track match progress') },
    ];

    return (
        <AuthenticatedLayout
            header={
                <div className="flex min-w-0 flex-wrap items-center gap-2 sm:gap-4">
                    <Link href={route('events.index')}>
                        <Button variant="ghost" size="sm" className="px-2 sm:px-3">
                            <ArrowLeft className="mr-1 size-4" />
                            {t('Back')}
                        </Button>
                    </Link>
                    <h2 className="min-w-0 flex-1 truncate text-lg font-semibold leading-tight sm:text-xl">{t('Draw Result')}</h2>
                    <div className="order-3 flex w-full items-center justify-end gap-2 sm:order-none sm:ml-auto sm:w-auto">
                        {editing ? (
                            <>
                                <Button variant="outline" size="sm" onClick={handleCancel} disabled={isSaving}>
                                    <X className="mr-1 size-4" /> {t('Cancel')}
                                </Button>
                                <Button size="sm" onClick={handleSave} disabled={pendingChangeCount === 0 || isSaving}>
                                    <Check className="mr-1 size-4" /> {isSaving ? t('Saving...') : `${t('Save Changes')} (${pendingChangeCount})`}
                                </Button>
                            </>
                        ) : (
                            <>
                                {canEdit && pools.length > 0 && totalFixtures === 0 && (
                                    <Button variant="outline" size="sm" onClick={() => setEditing(true)}>
                                        <Pencil className="mr-1 size-4" /> {t('Manual Assign')}
                                    </Button>
                                )}
                                {canEdit && pools.length === 0 && (
                                    <Button size="sm" onClick={handleAutoAssign}>
                                        <Users className="mr-1 size-4" /> {t('Auto Assign Groups')}
                                    </Button>
                                )}
                                {showGenerateFixtures && (
                                    <Button size="sm" onClick={handleGenerateFixtures} disabled={!canEdit}
                                        title={!canEdit ? t('Fixtures cannot be changed after a match has started.') : t('Create fixtures for all pools')}>
                                        <Swords className="mr-1 size-4" /> {t('Create Fixtures')}
                                    </Button>
                                )}
                            </>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={`${t('Draw Result')} · ${event.name}`} />

            {flash?.success && (
                <div className="mb-4 rounded-md bg-emerald-50 p-3 text-sm text-emerald-700">{flash.success}</div>
            )}
            {flash?.error && (
                <div className="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700">{flash.error}</div>
            )}

            <div className="space-y-5 sm:space-y-6">
                <Card className="overflow-hidden border-slate-200 shadow-sm">
                    <div className="h-1.5 bg-gradient-to-r from-emerald-500 via-emerald-400 to-teal-400" />
                    <CardHeader className="p-4 sm:p-6">
                        <div className="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <div className="mb-2 flex flex-wrap items-center gap-2">
                                    <Badge variant="secondary">{event.sport?.name ?? t('Sport')}</Badge>
                                    {event.sport_category && <Badge variant="outline">{event.sport_category.name}</Badge>}
                                    {event.tournament && <Badge variant="outline">{event.tournament.name}</Badge>}
                                    {event.format && <Badge variant="outline">{event.format.replace(/_/g, ' ')}</Badge>}
                                </div>
                                <CardTitle className="text-xl sm:text-2xl">{event.name}</CardTitle>
                                <CardDescription className="mt-1">
                                    {pools.length} {t('Pools')} · {totalParticipants} {t('Participants')} · {totalFixtures} {t('Fixtures')}
                                </CardDescription>
                            </div>
                            <div className="w-full rounded-xl bg-slate-50 p-3 sm:max-w-xs">
                                <div className="mb-1.5 flex items-center justify-between text-xs text-muted-foreground">
                                    <span className="flex items-center gap-1"><CheckCircle2 className="size-3.5 text-emerald-500" /> {t('Completion')}</span>
                                    <span className="font-semibold text-slate-700">{completion}%</span>
                                </div>
                                <div className="h-2 overflow-hidden rounded-full bg-slate-100">
                                    <div className="h-full rounded-full bg-emerald-500 transition-all" style={{ width: `${completion}%` }} />
                                </div>
                                <p className="mt-1.5 text-xs text-muted-foreground">
                                    {completedFixtures} {t('of')} {totalFixtures} {t('fixtures completed')}
                                </p>
                            </div>
                        </div>
                        <div className="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <StatTile icon={LayoutGrid} label={t('Pools')} value={pools.length} accent="bg-violet-50 text-violet-600" />
                            <StatTile icon={Users} label={t('Participants')} value={totalParticipants} accent="bg-sky-50 text-sky-600" />
                            <StatTile icon={Swords} label={t('Fixtures')} value={totalFixtures} accent="bg-emerald-50 text-emerald-600" />
                            <StatTile icon={CheckCircle2} label={t('Completed')} value={completedFixtures} accent="bg-amber-50 text-amber-600" />
                        </div>
                    </CardHeader>
                </Card>

                <section aria-labelledby="draw-workflow-title" className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <div className="mb-4 flex flex-wrap items-end justify-between gap-2">
                        <div>
                            <h3 id="draw-workflow-title" className="font-semibold text-slate-900">{t('Competition setup')}</h3>
                            <p className="text-sm text-muted-foreground">{t('Follow these steps to prepare and monitor the event.')}</p>
                        </div>
                        <Badge variant="secondary">{t('Step')} {currentStep} {t('of')} 3</Badge>
                    </div>
                    <ol className="grid gap-2 sm:grid-cols-3">
                        {workflowSteps.map((step) => {
                            const complete = step.number < currentStep;
                            const active = step.number === currentStep;

                            return (
                                <li
                                    key={step.number}
                                    aria-current={active ? 'step' : undefined}
                                    className={`flex items-center gap-3 rounded-lg border p-3 ${
                                        active
                                            ? 'border-emerald-300 bg-emerald-50'
                                            : complete
                                                ? 'border-slate-200 bg-slate-50'
                                                : 'border-slate-200 bg-white'
                                    }`}
                                >
                                    <span className={`flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-bold ${
                                        complete
                                            ? 'bg-emerald-600 text-white'
                                            : active
                                                ? 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-300'
                                                : 'bg-slate-100 text-slate-500'
                                    }`}>
                                        {complete ? <Check className="size-4" /> : step.number}
                                    </span>
                                    <div className="min-w-0">
                                        <p className={`text-sm font-semibold ${active ? 'text-emerald-900' : 'text-slate-800'}`}>{step.label}</p>
                                        <p className="truncate text-xs text-muted-foreground">{step.description}</p>
                                    </div>
                                </li>
                            );
                        })}
                    </ol>
                </section>

                {editing && (
                    <div className="flex items-center gap-2 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                        <Info className="size-4 shrink-0" />
                        {t('Manual mode: choose a new group for each participant, then save the group assignment.')}{' '}
                        {totalFixtures === 0
                            ? t('Fixtures will be generated after you click Create Fixtures.')
                            : t('Reset the draw before changing groups after fixtures exist.')}
                    </div>
                )}

                {showGenerateFixtures && (
                    <div className="flex items-center gap-2 rounded-md border border-sky-200 bg-sky-50 p-3 text-sm text-sky-800">
                        <Info className="size-4 shrink-0" />
                        {t('Groups are ready but fixtures have not been generated yet...')}
                    </div>
                )}

                {pools.length === 0 && (
                    <div className="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-slate-300 bg-white py-16 text-center">
                        <span className="flex size-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                            <ClipboardList className="size-6" />
                        </span>
                        <div>
                            <p className="font-semibold text-slate-700">{t('No draw has been performed yet')}</p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {t('Run a draw from the Events page to create groups...')}
                            </p>
                        </div>
                    </div>
                )}

                {allFixtures.length > 0 && (
                    <section aria-labelledby="full-schedule-title">
                        <div className="mb-3 flex flex-wrap items-end justify-between gap-2">
                            <div>
                                <h3 id="full-schedule-title" className="text-lg font-semibold text-slate-900">{t('Full match schedule')}</h3>
                                <p className="text-sm text-muted-foreground">{t('All groups in official match order for shared venue scheduling.')}</p>
                            </div>
                            <Badge variant="outline">{allFixtures.length} {t('Fixtures')}</Badge>
                        </div>
                        <Card className="overflow-hidden border-slate-200 shadow-sm">
                            <div className="divide-y divide-slate-100">
                                {allFixtures.map((fixture) => (
                                    <article
                                        key={fixture.id}
                                        className="grid gap-3 p-4 transition-colors hover:bg-slate-50/70 lg:grid-cols-[9rem_minmax(18rem,1fr)_12rem] lg:items-center lg:px-5"
                                    >
                                        <div className="flex items-center justify-between gap-3 lg:block">
                                            <div className="flex items-center gap-2">
                                                <span className="flex size-8 items-center justify-center rounded-lg bg-slate-900 text-xs font-bold text-white">
                                                    {fixture.match_number}
                                                </span>
                                                <div>
                                                    <p className="text-sm font-semibold text-slate-800">{fixture.poolName}</p>
                                                    <p className="text-xs text-muted-foreground">{t('Round')} {fixture.round ?? '-'}</p>
                                                </div>
                                            </div>
                                            <div className="lg:hidden">{statusBadge(fixture.status, t)}</div>
                                        </div>

                                        <div className="rounded-xl border border-slate-100 bg-white px-2 py-3 sm:px-4">
                                            <Matchup home={fixture.home_participant} away={fixture.away_participant} />
                                        </div>

                                        <div className="flex items-center justify-between gap-3 text-xs text-muted-foreground lg:block lg:text-right">
                                            <div className="hidden lg:mb-1 lg:block">{statusBadge(fixture.status, t)}</div>
                                            <p>{fixture.scheduled_at ? formatDateTime(fixture.scheduled_at, locale) : t('Time TBD')}</p>
                                            <p className="mt-0.5 font-medium text-slate-600">{fixture.venue || t('Venue TBD')}</p>
                                        </div>
                                    </article>
                                ))}
                            </div>
                        </Card>
                    </section>
                )}

                <div className="grid gap-5 xl:grid-cols-2">
                    {pools.map((pool, index) => {
                        const poolCompleted = pool.fixtures.filter(f => f.status === 'completed').length;
                        const poolProgress = pool.fixtures.length > 0 ? Math.round((poolCompleted / pool.fixtures.length) * 100) : 0;

                        return (
                            <Card key={pool.id} className="flex min-w-0 flex-col overflow-hidden border-slate-200 shadow-sm">
                                <CardHeader className="border-b border-slate-100 bg-slate-50/60 p-4 sm:p-6">
                                    <div className="flex flex-wrap items-center justify-between gap-3">
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
                                                    {pool.event_participants.length} {t('Participants')} · {pool.fixtures.length} {t('Fixtures')}
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
                                <CardContent className="flex-1 space-y-5 p-4 sm:p-6">
                                    {pool.event_participants.length > 0 && (
                                        <div>
                                            <h4 className="mb-2 flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                                <Users className="size-3" />
                                                {t('Participants')}
                                            </h4>
                                            <div className="space-y-1.5">
                                                {pool.event_participants.map((ep, participantIndex) => {
                                                    const pendingTarget = pendingMoves[ep.id];
                                                    const willMove = pendingTarget !== undefined;
                                                    const shortName = participantName(ep.participant);
                                                    const fullName = participantFullName(ep.participant);
                                                    const displayPosition = ep.seed_number ?? participantIndex + 1;

                                                    return (
                                                        <div
                                                            key={ep.id}
                                                            className={`flex flex-wrap items-center gap-3 rounded-lg border px-3 py-2 transition-colors sm:flex-nowrap ${
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
                                                             </div>
                                                            {editing && (<div className="flex w-full gap-2 pl-11 sm:w-auto sm:pl-0">
                                                                <select
                                                                    className="min-w-0 flex-1 rounded-md border border-input bg-background px-2 py-1.5 text-xs sm:flex-none"
                                                                    value={pendingTarget ?? pool.id}
                                                                    onChange={(e) => handlePoolChange(ep.id, e.target.value)}
                                                                >
                                                                    {pools.map((p) => (
                                                                         <option key={p.id} value={p.id}>
                                                                             {p.name}{p.id === pool.id ? ` (${t('(current)')})` : ''}
                                                                         </option>
                                                                    ))}
                                                                </select>
                                                                <select
                                                                    className="w-16 rounded-md border border-input bg-background px-2 py-1.5 text-xs"
                                                                    value={pendingSeeds[ep.id] ?? displayPosition}
                                                                    onChange={(e) => setPendingSeeds(prev => ({ ...prev, [ep.id]: Number(e.target.value) }))}
                                                                    aria-label={t('Position')}
                                                                >
                                                                    <option value="">—</option>
                                                                    {Array.from({ length: Math.max(1, event.pool_size ?? Math.max(...pools.map((item) => item.event_participants.length), 1)) }, (_, seed) => (
                                                                        <option key={seed + 1} value={seed + 1}>#{seed + 1}</option>
                                                                    ))}
                                                                </select>
                                                            </div>)}
                                                             {!editing && (
                                                                 <Badge variant="secondary" className="text-xs">#{displayPosition}</Badge>
                                                             )}
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    )}

                                    {pool.event_participants.length === 0 && pool.fixtures.length === 0 && (
                                        <p className="py-6 text-center text-sm text-muted-foreground">{t('Empty pool')}</p>
                                    )}
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>

                <Card className="border-slate-200 shadow-sm">
                    <details>
                        <summary className="flex cursor-pointer list-none items-center justify-between gap-3 p-4 sm:p-6">
                            <span className="flex min-w-0 items-center gap-3">
                                <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                                    <History className="size-4" />
                                </span>
                                <span className="min-w-0">
                                    <span className="block text-sm font-semibold sm:text-base">{t('Draw history')}</span>
                                    <span className="block truncate text-xs text-muted-foreground sm:text-sm">{drawVersions.length} {t('saved versions')}</span>
                                </span>
                            </span>
                            <Badge variant="outline">{t('View history')}</Badge>
                        </summary>
                        <CardContent className="border-t border-slate-100 pt-4 sm:pt-6">
                            <p className="mb-4 text-sm text-muted-foreground">{t('Versioned allocation and fixture snapshots for audit and rollback.')}</p>
                        {drawVersions.length === 0 ? (
                            <p className="text-sm text-muted-foreground">{t('No draw versions recorded yet.')}</p>
                        ) : (
                            <div className="space-y-2">
                                {drawVersions.map((version) => (
                                    <div key={version.id} className="flex flex-wrap items-center justify-between gap-3 rounded-lg border p-3">
                                        <div>
                                            <p className="text-sm font-medium">{t('Version')} {version.version} · {version.action.replace(/_/g, ' ')}</p>
                                            <p className="text-xs text-muted-foreground">
                                                {new Date(version.created_at).toLocaleString()} · {version.actor?.name ?? t('System')}
                                                {version.seed ? ` · ${t('Seed')} ${version.seed}` : ''}
                                            </p>
                                        </div>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            disabled={!canEdit}
                                            onClick={() => setRestoreVersion(version)}
                                        >
                                            {t('Restore')}
                                        </Button>
                                    </div>
                                ))}
                            </div>
                        )}
                        </CardContent>
                    </details>
                </Card>

                    <Dialog open={restoreVersion !== null} onOpenChange={(open) => !open && setRestoreVersion(null)}>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>{t('Restore version')} {restoreVersion?.version}?</DialogTitle>
                                <DialogDescription>
                                    The current draw will be preserved as a new snapshot before this version is restored.
                                </DialogDescription>
                            </DialogHeader>
                            <DialogFooter>
                                <DialogClose asChild><Button variant="outline">{t('Cancel')}</Button></DialogClose>
                                <Button
                                    onClick={() => restoreVersion && router.post(
                                        route('events.draw.rollback', [event.slug, restoreVersion.id]),
                                        {},
                                        { onSuccess: () => setRestoreVersion(null) },
                                    )}
                                >
                                    {t('Restore version')}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
            </div>
        </AuthenticatedLayout>
    );
}
