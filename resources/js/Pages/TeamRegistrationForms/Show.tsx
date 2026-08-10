import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Printer } from 'lucide-react';
import { useI18n } from '@/lib/i18n';

type Member = {
    id: string;
    name: string;
    role: string;
    matrix_no: string | null;
    identification_no: string | null;
    phone: string | null;
};

interface Props {
    organization: { name: string; logo_url: string | null };
    branding: { tournament_logo_url: string | null; secretariat_address: string; form_reference: string };
    registration: { id: string; status: string; registration_date: string | null };
    participant: { name: string; team_name: string | null; logo_url: string | null };
    event: { name: string; sport: string; category: string; tournament: string; session: string; period: string };
    officials: Member[];
    athletes: Member[];
    quotaRows: { officials: number; athletes: number };
    generatedDate: string;
}

const roleLabels: Record<string, string> = {
    athlete_male: 'Male Athlete',
    athlete_female: 'Female Athlete',
    assistant_manager: 'Assistant Manager',
    manager: 'Team Manager',
    coach: 'Coach',
    physio: 'Physiotherapist',
};

function rows(members: Member[], quota: number) {
    const listedMembers = members.slice(0, quota);

    return [...listedMembers, ...Array.from({ length: Math.max(0, quota - listedMembers.length) }, (_, index) => ({
        id: `blank-${index}`, name: '', role: '', matrix_no: '', identification_no: '', phone: '',
    }))];
}

export default function Show(props: Props) {
    const { t } = useI18n();
    const officialRows = rows(props.officials, props.quotaRows.officials);
    const athleteRows = rows(props.athletes, props.quotaRows.athletes);

    return (
        <AuthenticatedLayout>
            <Head title={`${t('Team Registration Form')} - ${props.event.sport}`} />
            <style>{`
                @media print {
                    @page { size: A4 portrait; margin: 8mm 10mm; }
                    body * { visibility: hidden !important; }
                    .team-form-sheet, .team-form-sheet * { visibility: visible !important; }
                    .team-form-sheet { position: absolute; inset: 0; width: 100%; border: 0 !important; box-shadow: none !important; padding: 0 !important; }
                    .print-hidden { display: none !important; }
                    .form-table th, .form-table td { padding: 2px 4px !important; height: 18px !important; }
                    .signature-block { break-inside: avoid; }
                }
            `}</style>

            <div className="mx-auto max-w-5xl space-y-5 p-4 sm:p-6 lg:p-8">
                <div className="print-hidden flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">{t('Team Registration Form')}</h1>
                        <p className="text-sm text-muted-foreground">{props.participant.name} - {props.event.sport} ({props.event.category})</p>
                    </div>
                    <div className="flex gap-2">
                        <Button asChild variant="outline"><Link href={route('event-participants.index')}><ArrowLeft className="mr-2 size-4" />{t('Back to Event Registrations')}</Link></Button>
                        <Button onClick={() => window.print()}><Printer className="mr-2 size-4" />{t('Print Form')}</Button>
                    </div>
                </div>

                <section className="team-form-sheet rounded-sm border bg-white px-8 py-6 text-[10px] leading-tight text-black shadow-sm">
                    <header className="grid grid-cols-[92px_1fr_92px] items-start gap-3">
                        <div className="text-center">
                            {props.organization.logo_url && <img src={props.organization.logo_url} alt={t('Organization logo')} className="mx-auto h-14 w-20 object-contain" />}
                        </div>
                        <div className="whitespace-pre-line text-center">
                            <p className="text-[11px] font-bold uppercase">{props.organization.name}</p>
                            <p className="mt-1 text-[9px] font-semibold uppercase">{props.branding.secretariat_address}</p>
                        </div>
                        <div className="text-center">
                            {props.branding.tournament_logo_url ? <img src={props.branding.tournament_logo_url} alt={t('Tournament logo')} className="mx-auto h-14 w-20 object-contain" /> : <div className="h-14" />}
                            <div className="mt-1 border border-black px-1 py-1 font-bold">{props.branding.form_reference}</div>
                        </div>
                    </header>

                    <div className="mt-3 border-y-2 border-black py-2 text-center">
                        <h2 className="text-[14px] font-bold uppercase">{t('Team Registration Form')}</h2>
                        <p className="mt-1 font-bold uppercase">{props.event.session}</p>
                    </div>

                    <div className="mt-3 grid grid-cols-[110px_1fr_95px_1fr] gap-y-1 border border-black p-2">
                        <span className="font-bold">{t('Faculty / Team')}</span><span>: {props.participant.team_name || props.participant.name}</span>
                        <span className="font-bold">{t('Form Date')}</span><span>: {props.generatedDate}</span>
                        <span className="font-bold">{t('Sport')}</span><span>: {props.event.sport}</span>
                        <span className="font-bold">{t('Category')}</span><span>: {props.event.category}</span>
                        <span className="font-bold">{t('Tournament')}</span><span>: {props.event.tournament}</span>
                        <span className="font-bold">{t('Competition Dates')}</span><span>: {props.event.period}</span>
                    </div>

                    <h3 className="mt-3 border border-black bg-slate-100 px-2 py-1 font-bold uppercase">A. {t('Team Officials')}</h3>
                    <table className="form-table w-full border-collapse">
                        <thead><tr><th className="w-8 border border-black p-1">{t('No.')}</th><th className="border border-black p-1 text-left">{t('Full Name')}</th><th className="w-28 border border-black p-1 text-left">{t('Role')}</th><th className="w-32 border border-black p-1">{t('ID / Passport No.')}</th><th className="w-28 border border-black p-1">{t('Phone No.')}</th></tr></thead>
                        <tbody>{officialRows.map((member, index) => <tr key={member.id}><td className="border border-black p-1 text-center">{index + 1}</td><td className="border border-black p-1 font-medium">{member.name}</td><td className="border border-black p-1">{roleLabels[member.role] || ''}</td><td className="border border-black p-1 text-center">{member.identification_no}</td><td className="border border-black p-1 text-center">{member.phone}</td></tr>)}</tbody>
                    </table>

                    <h3 className="mt-3 border border-black bg-slate-100 px-2 py-1 font-bold uppercase">B. {t('Players / Athletes')}</h3>
                    <table className="form-table w-full border-collapse">
                        <thead><tr><th className="w-8 border border-black p-1">{t('No.')}</th><th className="border border-black p-1 text-left">{t('Full Name')}</th><th className="w-28 border border-black p-1">{t('Matrix No.')}</th><th className="w-32 border border-black p-1">{t('ID / Passport No.')}</th><th className="w-24 border border-black p-1">{t('Gender')}</th></tr></thead>
                        <tbody>{athleteRows.map((member, index) => <tr key={member.id}><td className="border border-black p-1 text-center">{index + 1}</td><td className="border border-black p-1 font-medium">{member.name}</td><td className="border border-black p-1 text-center">{member.matrix_no}</td><td className="border border-black p-1 text-center">{member.identification_no}</td><td className="border border-black p-1 text-center">{member.role === 'athlete_male' ? t('Male') : member.role === 'athlete_female' ? t('Female') : ''}</td></tr>)}</tbody>
                    </table>

                    <div className="signature-block mt-3 border border-black p-2">
                        <p className="text-justify">{t('I certify that all player and official information listed above is accurate...')}</p>
                        <div className="mt-6 grid grid-cols-2 gap-12">
                            <div><div className="border-b border-black" /><p className="mt-1">{t('Team Manager Signature')}</p><p className="mt-2">{t('Name:')} __________________________________</p><p className="mt-2">{t('Date:')} __________________________________</p></div>
                            <div><div className="border-b border-black" /><p className="mt-1">{t('Dean / Head of Contingent Verification')}</p><p className="mt-2">{t('Name:')} __________________________________</p><p className="mt-2">{t('Official Stamp:')}</p></div>
                        </div>
                    </div>
                    <p className="mt-2 text-[8px] text-slate-600">{t('This form is generated from STMS registration records...')}</p>
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
