import { PageHeader } from '@/components/PageHeader';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ConfirmDialog } from '@/components/ConfirmDialog';
import { EmptyState } from '@/components/EmptyState';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Download, FileText, Plus, Trash2, Upload, Users } from 'lucide-react';
import { useState } from 'react';
import { useI18n } from '@/lib/i18n';
import { displayName } from '@/lib/displayName';
import type { Event, EventParticipant, Participant, SquadMember } from '@/types';

type Registration = EventParticipant & {
    event?: Event & { sport?: { name: string }; sport_category?: { name: string }; tournament?: { name: string; session?: { name: string } } };
    squad_members?: SquadMember[];
};

interface Props {
    participant: Participant | null;
    registrations: Registration[];
    eventRegistrationStartDate?: string | null;
    eventRegistrationDeadline?: string | null;
    squadRegistrationStartDate?: string | null;
    squadRegistrationDeadline?: string | null;
}

const roleLabels: Record<SquadMember['role'], string> = {
    athlete_male: 'Athlete (Male)', athlete_female: 'Athlete (Female)', manager: 'Manager',
    assistant_manager: 'Assistant Manager', coach: 'Coach', physio: 'Physio',
};

const officialRoles: SquadMember['role'][] = ['manager', 'assistant_manager', 'coach', 'physio'];

export default function FacultyRegistrations({ participant, registrations, squadRegistrationStartDate, squadRegistrationDeadline }: Props) {
    const { t, locale } = useI18n();
    const [activeId, setActiveId] = useState<string | null>(null);
    const [addOpen, setAddOpen] = useState(false);
    const [deleteMember, setDeleteMember] = useState<SquadMember | null>(null);
    const [importOpen, setImportOpen] = useState(false);
    const [importFile, setImportFile] = useState<File | null>(null);
    const [form, setForm] = useState({ name: '', role: 'manager' as SquadMember['role'], matrix_no: '', identification_no: '', phone: '' });
    const squadWindowOpen = (!squadRegistrationStartDate || new Date(squadRegistrationStartDate) <= new Date())
        && (!squadRegistrationDeadline || new Date(squadRegistrationDeadline) >= new Date());
    const active = registrations.find((registration) => registration.id === activeId) ?? null;

    const addMember = () => {
        if (!active) return;
        router.post(route('faculty.squad.store'), { ...form, event_participant_id: active.id }, {
            onSuccess: () => { setAddOpen(false); setForm({ name: '', role: 'manager', matrix_no: '', identification_no: '', phone: '' }); },
        });
    };

    const submitImport = () => {
        if (!active || !importFile) return;
        const data = new FormData();
        data.append('event_participant_id', active.id);
        data.append('file', importFile);
        router.post(route('faculty.squad.import'), data, { forceFormData: true, onSuccess: () => { setImportOpen(false); setImportFile(null); } });
    };

    return (
        <AuthenticatedLayout>
            <Head title={t('Managers & Athletes')} />
            <div className="space-y-6 p-4 md:p-6">
                <PageHeader title={t('Managers & Athletes')} description={participant?.name ? `${t('Manage officials and athletes for')} ${displayName(participant.name, locale)}` : t('Manage faculty officials and athletes')} />
                <div>
                    <Button asChild variant="outline" size="sm"><Link href={route('dashboard')}><ArrowLeft className="mr-2 size-4" />{t('Back to Dashboard')}</Link></Button>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2"><Users className="size-5" />{t('Event registrations')}</CardTitle>
                        <CardDescription>{t('Select a confirmed event registration to add managers, officials, and athletes.')}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {registrations.length === 0 ? <EmptyState title={t('Not registered for any events yet.')} /> : (
                            <div className="space-y-4">
                                {registrations.map((registration) => {
                                    const members = registration.squad_members ?? [];
                                    const confirmed = registration.status === 'confirmed';
                                    const opened = activeId === registration.id;
                                    return (
                                        <section key={registration.id} className="rounded-lg border">
                                            <button type="button" onClick={() => setActiveId(opened ? null : registration.id)} className="flex w-full flex-wrap items-center justify-between gap-3 p-4 text-left hover:bg-muted/40">
                                                <div>
                                                    <div className="font-semibold">{displayName(registration.event?.name, locale) || t('Event')}</div>
                                                    <div className="text-sm text-muted-foreground">{displayName(registration.event?.sport?.name, locale)} · {displayName(registration.event?.sport_category?.name, locale)} · {displayName(registration.event?.tournament?.name, locale)}</div>
                                                </div>
                                                <div className="flex items-center gap-2"><Badge variant={confirmed ? 'default' : 'secondary'}>{t(registration.status)}</Badge><Badge variant="outline">{members.length} {t('members')}</Badge></div>
                                            </button>
                                            {opened && <div className="space-y-4 border-t p-4">
                                                {!confirmed ? <p className="text-sm text-amber-700">{t('Squad members can be added after the dean confirms this registration.')}</p> : <>
                                                    {!squadWindowOpen && <p className="text-sm text-amber-700">{t('Officials and athlete registration is not currently open.')}</p>}
                                                    <div className="flex flex-wrap gap-2">
                                                        <Button size="sm" onClick={() => { setForm({ name: '', role: 'manager', matrix_no: '', identification_no: '', phone: '' }); setAddOpen(true); }} disabled={!squadWindowOpen}><Plus className="mr-2 size-4" />{t('Add Member')}</Button>
                                                        <Button size="sm" variant="outline" onClick={() => setImportOpen(true)} disabled={!squadWindowOpen}><Upload className="mr-2 size-4" />{t('Import Excel')}</Button>
                                                        <Button asChild size="sm" variant="outline"><Link href={route('event-participants.team-form', registration.id)}><FileText className="mr-2 size-4" />{t('Team Form')}</Link></Button>
                                                    </div>
                                                    <div className="overflow-x-auto">
                                                        <Table>
                                                            <TableHeader><TableRow><TableHead>{t('Name')}</TableHead><TableHead>{t('Role')}</TableHead><TableHead>{t('Matrix No.')}</TableHead><TableHead>{t('IC / Passport')}</TableHead><TableHead>{t('Phone')}</TableHead><TableHead /></TableRow></TableHeader>
                                                            <TableBody>{members.length === 0 ? <TableRow><TableCell colSpan={6} className="text-center text-muted-foreground">{t('No squad members yet.')}</TableCell></TableRow> : members.map((member) => <TableRow key={member.id}><TableCell className="font-medium">{member.name}</TableCell><TableCell>{t(roleLabels[member.role])}</TableCell><TableCell>{member.matrix_no || '—'}</TableCell><TableCell>{member.identification_no || '—'}</TableCell><TableCell>{member.phone || '—'}</TableCell><TableCell><Button variant="ghost" size="icon" aria-label={t('Remove member')} onClick={() => setDeleteMember(member)} disabled={!squadWindowOpen}><Trash2 className="size-4 text-destructive" /></Button></TableCell></TableRow>)}</TableBody>
                                                        </Table>
                                                    </div>
                                                </>}
                                            </div>}
                                        </section>
                                    );
                                })}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            <Dialog open={addOpen} onOpenChange={setAddOpen}>
                <DialogContent>
                    <DialogHeader><DialogTitle>{t('Add Squad Member')}</DialogTitle><DialogDescription>{displayName(active?.event?.name, locale)}</DialogDescription></DialogHeader>
                    <div className="grid gap-4 py-2">
                        <div className="grid gap-2"><Label htmlFor="roster-name">{t('Full Name')} *</Label><Input id="roster-name" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} /></div>
                        <div className="grid gap-2"><Label>{t('Role')} *</Label><Select value={form.role} onValueChange={(role) => setForm({ ...form, role: role as SquadMember['role'] })}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent>{Object.entries(roleLabels).map(([value, label]) => <SelectItem key={value} value={value}>{t(label)}</SelectItem>)}</SelectContent></Select></div>
                        <div className="grid gap-2"><Label htmlFor="roster-matrix">{t('Matrix No.')} *</Label><Input id="roster-matrix" value={form.matrix_no} onChange={(e) => setForm({ ...form, matrix_no: e.target.value })} /></div>
                        <div className="grid gap-2"><Label htmlFor="roster-ic">{t('IC / Passport')}</Label><Input id="roster-ic" value={form.identification_no} onChange={(e) => setForm({ ...form, identification_no: e.target.value })} /></div>
                        <div className="grid gap-2"><Label htmlFor="roster-phone">{t('Phone')} {officialRoles.includes(form.role) && '*'}</Label><Input id="roster-phone" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} /></div>
                    </div>
                    <DialogFooter><Button variant="outline" onClick={() => setAddOpen(false)}>{t('Cancel')}</Button><Button onClick={addMember} disabled={!form.name || !form.matrix_no || (officialRoles.includes(form.role) && !form.phone)}>{t('Add')}</Button></DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={importOpen} onOpenChange={setImportOpen}>
                <DialogContent><DialogHeader><DialogTitle>{t('Import Squad Members')}</DialogTitle><DialogDescription>{displayName(active?.event?.name, locale)}</DialogDescription></DialogHeader>
                    <div className="grid gap-4 py-2"><Link href={route('faculty.squad.template')} className="flex items-center gap-2 text-sm text-primary"><Download className="size-4" />{t('Download template')}</Link><div className="grid gap-2"><Label htmlFor="roster-import">{t('Excel/CSV File')}</Label><Input id="roster-import" type="file" accept=".xlsx,.xls,.csv" onChange={(e) => setImportFile(e.target.files?.[0] ?? null)} /></div></div>
                    <DialogFooter><Button variant="outline" onClick={() => setImportOpen(false)}>{t('Cancel')}</Button><Button onClick={submitImport} disabled={!importFile}>{t('Import')}</Button></DialogFooter>
                </DialogContent>
            </Dialog>
            <ConfirmDialog open={!!deleteMember} onOpenChange={(open) => !open && setDeleteMember(null)} title={t('Remove squad member?')} description={t('This member will be removed from the event roster.')} confirmLabel={t('Remove')} destructive onConfirm={() => { if (deleteMember) router.delete(route('faculty.squad.destroy', deleteMember.id), { onSuccess: () => setDeleteMember(null) }); }} />
        </AuthenticatedLayout>
    );
}
