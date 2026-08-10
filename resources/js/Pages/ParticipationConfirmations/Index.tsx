import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, router } from '@inertiajs/react';
import { Check, FileCheck2, Printer } from 'lucide-react';
import { useI18n } from '@/lib/i18n';

type Option = { id: string; name: string; period?: string };
type ConfirmationRow = {
    id: string;
    sport: string;
    category: string;
    status: string;
    yes: boolean;
    no: boolean;
};
type Phase = { id: string; name: string; period: string; rows: ConfirmationRow[] };

interface Props {
    canSelectParticipant: boolean;
    organization: { name: string; logo_url: string | null };
    branding: { tournament_logo_url: string | null; secretariat_address: string };
    participant: { id: string; name: string; slug: string } | null;
    dean: { name: string } | null;
    participants: Option[];
    sessions: Option[];
    filters: { participant_id: string; session_id: string };
    phases: Phase[];
    generatedDate: string;
}

function ConfirmationMark({ checked }: { checked: boolean }) {
    return (
        <span className="confirmation-mark inline-flex size-5 items-center justify-center border border-black">
            {checked && <Check className="size-4 stroke-[3]" aria-label="Selected" />}
        </span>
    );
}

export default function Index(props: Props) {
    const { t } = useI18n();
    const selectedSession = props.sessions.find((item) => item.id === props.filters.session_id);

    const filter = (key: keyof Props['filters'], value: string) => {
        router.get(route('participation-confirmations.index'), { ...props.filters, [key]: value }, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title={t('Participation Confirmation')} />

            <style>{`
                @media print {
                    @page { size: A4 portrait; margin: 10mm 12mm; }
                    body * { visibility: hidden !important; }
                    .participation-print-sheet, .participation-print-sheet * { visibility: visible !important; }
                    .participation-print-sheet { position: absolute; inset: 0; width: 100%; padding: 0 !important; border: 0 !important; box-shadow: none !important; }
                    .print-hidden { display: none !important; }
                    .print-avoid-break { break-inside: avoid; }
                    .participation-print-sheet { font-size: 8px !important; line-height: 1.05 !important; }
                    .form-header { gap: 6px !important; }
                    .form-header-logo { height: 48px !important; }
                    .form-header-logo img { height: 44px !important; }
                    .form-title { margin-top: 4px !important; padding-top: 5px !important; padding-bottom: 5px !important; }
                    .form-meta, .form-instruction { margin-top: 6px !important; }
                    .phase-list { margin-top: 6px !important; gap: 5px !important; }
                    .phase-heading { margin-bottom: 1px !important; }
                    .phase-table { font-size: 8px !important; }
                    .phase-table th, .phase-table td { padding: 1px 4px !important; height: 14px !important; line-height: 1 !important; }
                    .confirmation-mark { width: 11px !important; height: 11px !important; }
                    .confirmation-footer { margin-top: 8px !important; }
                    .confirmation-footer-grid { margin-top: 8px !important; gap: 20px !important; }
                    .signature-space { height: 22px !important; }
                    .stamp-box { height: 64px !important; }
                }
            `}</style>

            <div className="mx-auto max-w-5xl space-y-6 p-4 sm:p-6 lg:p-8">
                <div className="print-hidden flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div className="flex items-center gap-2">
                            <FileCheck2 className="h-7 w-7 text-primary" />
                            <h1 className="text-2xl font-bold tracking-tight">{t('Participation Confirmation')}</h1>
                        </div>
                        <p className="mt-1 text-sm text-muted-foreground">{t('Official faculty participation confirmation form.')}</p>
                    </div>
                    <Button onClick={() => window.print()} disabled={!props.participant}>
                        <Printer className="mr-2 h-4 w-4" /> {t('Print form')}
                    </Button>
                </div>

                <Card className="print-hidden">
                    <CardHeader className="pb-3"><CardTitle className="text-base">{t('Display options')}</CardTitle></CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-2">
                        {props.canSelectParticipant && (
                            <label className="space-y-1.5 text-sm font-medium">{t('Faculty / participant')}
                                <select className="mt-1 block h-10 w-full rounded-md border bg-background px-3 text-sm" value={props.filters.participant_id} onChange={(e) => filter('participant_id', e.target.value)}>
                                    {props.participants.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
                                </select>
                            </label>
                        )}
                        <label className="space-y-1.5 text-sm font-medium">{t('Session')}
                            <select className="mt-1 block h-10 w-full rounded-md border bg-background px-3 text-sm" value={props.filters.session_id} onChange={(e) => filter('session_id', e.target.value)}>
                                {props.sessions.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
                            </select>
                        </label>
                    </CardContent>
                </Card>

                <section className="participation-print-sheet rounded-sm border bg-white px-8 py-7 text-[12px] leading-snug text-black shadow-sm">
                    <header className="form-header grid grid-cols-[105px_1fr_105px] items-start gap-4">
                        <div className="flex flex-col items-center text-center">
                            <div className="form-header-logo flex h-20 items-start justify-center">
                                <img
                                    src="/portal/images/utem-logo.svg?v=20260804"
                                    alt="UTeM logo"
                                    width={100}
                                    height={64}
                                    className="block h-16 w-[100px] object-contain"
                                />
                            </div>
                            <div className="text-[8px] font-medium">https://www.utem.edu.my</div>
                            <div className="mt-1.5 w-full border border-black px-1 py-1 text-[9px] font-bold uppercase">{t('Received on')}</div>
                        </div>
                        <div className="whitespace-pre-line text-center text-[11px] leading-relaxed">
                            <p className="font-bold uppercase">{props.branding.secretariat_address}</p>
                        </div>
                        <div className="flex flex-col items-center text-center">
                            <div className="form-header-logo flex h-20 items-start justify-center">
                                {props.branding.tournament_logo_url ? (
                                    <img src={props.branding.tournament_logo_url} alt="SAF logo" className="max-h-16 max-w-[100px] object-contain" />
                                ) : <div className="mt-2 text-[10px] text-slate-500">SAF Logo</div>}
                            </div>
                            <div className="text-[8px] font-medium">https://saf.utem.edu.my</div>
                            <div className="mt-1.5 w-full border border-black px-1 py-1 text-[9px] font-bold uppercase">SAF 03/05</div>
                        </div>
                    </header>

                    <div className="form-title mt-2 border-y-2 border-black py-3 text-center">
                        <h2 className="text-[15px] font-bold uppercase">{t('Participation Confirmation Form')}</h2>
                        <p className="mt-1 font-bold uppercase">{selectedSession?.name ?? 'No session selected'}</p>
                    </div>

                    <div className="form-meta mt-4 grid grid-cols-[150px_1fr] gap-y-1 text-[12px]">
                        <span className="font-bold">{t('Faculty / Participant')}</span><span>: {props.participant?.name ?? '-'}</span>
                        <span className="font-bold">{t('Session Period')}</span><span>: {selectedSession?.period ?? '-'}</span>
                    </div>

                    <p className="form-instruction mt-4 text-justify">Please confirm the faculty participation for each sport and category listed below by referring to the Yes or No column.</p>

                    <div className="phase-list mt-4 space-y-6">
                        {props.phases.map((phase, phaseIndex) => (
                            <div key={phase.id} className="phase-section">
                                <div className="phase-heading mb-1 flex items-end justify-between font-bold uppercase">
                                    <h3>{phase.name || `Phase ${phaseIndex + 1}`}</h3>
                                    <span className="text-[10px] font-normal normal-case">{phase.period}</span>
                                </div>
                                <table className="phase-table w-full border-collapse text-[11px]">
                                    <thead>
                                        <tr>
                                            <th rowSpan={2} className="w-11 border border-black px-2 py-1.5 text-center">{t('No.')}</th>
                                            <th rowSpan={2} className="border border-black px-2 py-1.5 text-left">{t('Sport')}</th>
                                            <th rowSpan={2} className="border border-black px-2 py-1.5 text-left">{t('Category')}</th>
                                            <th colSpan={2} className="w-28 border border-black px-2 py-1 text-center">{t('Confirmation')}</th>
                                        </tr>
                                        <tr><th className="w-14 border border-black py-1 text-center">{t('Yes')}</th><th className="w-14 border border-black py-1 text-center">{t('No')}</th></tr>
                                    </thead>
                                    <tbody>
                                        {phase.rows.map((row, index) => (
                                            <tr key={row.id}>
                                                <td className="border border-black px-2 py-1.5 text-center">{index + 1}</td>
                                                <td className="border border-black px-2 py-1.5">{row.sport}</td>
                                                <td className="border border-black px-2 py-1.5">{row.category}</td>
                                                <td className="border border-black py-1 text-center"><ConfirmationMark checked={row.yes} /></td>
                                                <td className="border border-black py-1 text-center"><ConfirmationMark checked={row.no} /></td>
                                            </tr>
                                        ))}
                                        {phase.rows.length === 0 && <tr><td colSpan={5} className="border border-black p-4 text-center">{t('No participation records.')}</td></tr>}
                                    </tbody>
                                </table>
                            </div>
                        ))}
                        {props.phases.length === 0 && <div className="border border-black p-6 text-center">{t('No tournament phases are configured for this session.')}</div>}
                    </div>

                    <div className="confirmation-footer print-avoid-break mt-8">
                        <p>I hereby confirm that the participation information stated above is correct.</p>
                        <div className="confirmation-footer-grid mt-6 grid grid-cols-[1fr_180px] gap-12">
                            <div className="space-y-3">
                                <div className="grid grid-cols-[125px_1fr] items-end"><span>Name of Dean</span><span className="border-b border-black px-2 pb-1 font-bold">: {props.dean?.name ?? '-'}</span></div>
                                <div className="grid grid-cols-[125px_1fr] items-end"><span>Signature</span><span className="signature-space h-10 border-b border-black">:</span></div>
                                <div className="grid grid-cols-[125px_1fr] items-end"><span>Date</span><span className="border-b border-black px-2 pb-1">: {props.generatedDate}</span></div>
                            </div>
                            <div className="stamp-box flex h-28 items-center justify-center border border-black text-center text-[10px] uppercase text-slate-500">Official Stamp</div>
                        </div>
                    </div>
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
