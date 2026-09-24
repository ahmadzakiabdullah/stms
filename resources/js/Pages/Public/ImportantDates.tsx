import PublicLayout from '@/Layouts/PublicLayout';
import PublicPageHero from '@/components/PublicPageHero';
import PublicStaleDataNotice from '@/components/PublicStaleDataNotice';
import { useI18n } from '@/lib/i18n';
import { CalendarDays } from 'lucide-react';

type Props = {
    app_name: string;
    competition: { name: string; description: string | null; organization: string | null } | null;
    updated_at?: string;
};

const importantDates = [
    ['1', 'Mesyuarat Jawatankuasa Induk Bil.1/2026', '25 Jun 2026'],
    ['2', 'Mesyuarat Jawatankuasa Pelaksana (Pelajar) Bil.1 (JK Tertinggi)', '15 Julai 2026'],
    ['3', 'Mesyuarat Jawatankuasa Pelaksana (Pelajar) Teknikal Bil.2 (JK TT + Wakil Kelab Sukan + Exco Kelab Sukan Fakulti)', 'Belum ditetapkan'],
    ['4', 'Mesyuarat Jawatankuasa Induk Bil.2 (DTNC HEPA + PPS + TDPP)', '21 Ogos 2026'],
    ['5', 'Mesyuarat Jawatankuasa Pelaksana (Pelajar) Teknikal Bil. 3 (JK Kelab Sukan)', 'September 2026 (Akan Ditentukan Kemudian)'],
    ['6', 'Mesyuarat Jawatankuasa Penyelaras (Staf Pusat Sukan) Bil. 1', '16 September 2026'],
    ['7', 'Tarikh Pengesahan Penyertaan Acara Sukan (Online)', '21 - 25 Sept. 2026'],
    ['8', 'Mesyuarat Jawatankuasa Pelaksana (Pelajar) Bil.4 - Fizikal', '1 Oktober 2026'],
    ['9', 'Mesyuarat Persediaan Jawatankuasa Pelajar (JK Teknikal) Acara Sukan Bil. 4', '2 Oktober 2026'],
    ['10', 'Latihan Persediaan Fakulti SAF 20, 2026 (Mengikut Jadual yang ditetapkan)', '28 Sept – 21 Okt 2026'],
    ['11', 'Undian Kumpulan Sukan & Tapak Gerai Fakulti – B. Mesyuarat Dewan Sukan', '13 Oktober 2026'],
    ['12', 'Taklimat Pengurus Pasukan SAF 20, 2026 – Dewan Sukan UTeM', '14 Oktober 2026'],
    ['13', 'Mesyuarat Persediaan Majlis Rasmi SAF Ke-20 Bil.1/2026 (JK Induk & AJK Majlis)', '15 Oktober 2026'],
    ['14', 'Road To SAF 20, 2026', '15 Oktober 2026'],
    ['15', 'Mesyuarat Jawatankuasa Pelaksana (Pelajar) Bil.3 & Mesy. Persediaan Majlis Perasmian - Fizikal', '16 Oktober 2026'],
    ['16', 'Raptai Penuh Majlis Perasmian & Penutup SAF 20, 2026 (8.00 Malam)', '21 Oktober 2026 (8.00 malam)'],
    ['17', 'Majlis Perasmian & Penutup SAF Kali Ke-20, 2026', '25 Oktober 2026 (8.00 malam)'],
    ['19', 'Tarikh Pendaftaran Pasukan Sukan SAF 20, 2026 (Online)', '12 – 18 Oktober 2026'],
    ['20', 'Pemeriksaan Kesihatan Atlet', '12 – 16 Oktober 2026'],
    ['21', 'Penghantaran Borang (Borang Jamin Diri - Google Form & Borang Medical) - Borang Upload Di Laman Web SAF Ke-20, 2026', '19 – 22 Oktober 2026'],
    ['22', 'Semakan Borang Pendaftaran Pasukan SAF 20, 2026 (Urusetia Kejohanan)', 'Belum ditetapkan'],
    ['23', 'Kejohanan Sukan Antara Fakulti (SAF) Kali Ke-20, 2026', '22 – 25 Oktober 2026'],
    ['24', 'Tapak Karnival SAF 20, 2026', '22 – 25 Oktober 2026'],
    ['25', 'Sesi Penyampaian Hadiah Setiap Acara', 'Selesai Perlawanan Akhir'],
    ['26', 'Majlis Perasmian & Penutup SAF 20, 2026', '25 Oktober 2026 (8.00 malam)'],
];

const importantDateEnglish: Record<string, [string, string]> = {
    '1': ['Main Committee Meeting No. 1/2026', '25 June 2026'],
    '2': ['Student Executive Committee Meeting No. 1 (Top Committee)', '15 July 2026'],
    '3': ['Student Technical Executive Committee Meeting No. 2 (Technical Committee + Sports Club Representatives + Faculty Sports Club Excos)', 'To be determined'],
    '4': ['Main Committee Meeting No. 2 (Deputy Vice-Chancellor HEPA + Sports Centre Director + Deputy Deans of Student Development)', '21 August 2026'],
    '5': ['Student Technical Executive Committee Meeting No. 3 (Sports Club Committee)', 'September 2026 (To be determined)'],
    '6': ['Sports Centre Staff Coordinating Committee Meeting No. 1', '16 September 2026'],
    '7': ['Online Confirmation of Sports Event Participation', '21–25 September 2026'],
    '8': ['Student Executive Committee Meeting No. 4 – Physical Meeting', '1 October 2026'],
    '9': ['Student Committee Preparation Meeting (Technical Committee) Sports Events No. 4', '2 October 2026'],
    '10': ['SAF 20, 2026 Faculty Preparation Training (According to the Scheduled Programme)', '28 September–21 October 2026'],
    '11': ['Sports Group Draw & Faculty Booth Site – Sports Hall Meeting', '13 October 2026'],
    '12': ['SAF 20, 2026 Team Managers Briefing – UTeM Sports Hall', '14 October 2026'],
    '13': ['SAF 20, 2026 Official Ceremony Preparation Meeting No. 1 (Main Committee & Ceremony Committee)', '15 October 2026'],
    '14': ['Road To SAF 20, 2026', '15 October 2026'],
    '15': ['Student Executive Committee Meeting No. 3 & Opening Ceremony Preparation Meeting – Physical Meeting', '16 October 2026'],
    '16': ['Full Rehearsal of SAF 20, 2026 Opening & Closing Ceremony (8:00 PM)', '21 October 2026 (8:00 PM)'],
    '17': ['20th SAF 2026 Opening & Closing Ceremony', '25 October 2026 (8:00 PM)'],
    '19': ['Online SAF 20, 2026 Sports Team Registration', '12–18 October 2026'],
    '20': ['Athlete Medical Examination', '12–16 October 2026'],
    '21': ['Submission of Forms (Self-Declaration Google Form & Medical Form) – Upload via SAF 20, 2026 Website', '19–22 October 2026'],
    '22': ['SAF 20, 2026 Team Registration Form Review (Championship Secretariat)', 'To be determined'],
    '23': ['20th Inter-Faculty Sports Championship (SAF), 2026', '22–25 October 2026'],
    '24': ['SAF 20, 2026 Carnival Site', '22–25 October 2026'],
    '25': ['Prize Presentation Session for Each Event', 'After the Final Match'],
    '26': ['SAF 20, 2026 Opening & Closing Ceremony', '25 October 2026 (8:00 PM)'],
};

export default function PublicImportantDates({ app_name, competition, updated_at }: Props) {
    const { t, locale } = useI18n();
    const isMalay = locale === 'ms';
    const title = isMalay ? 'TARIKH – TARIKH PENTING' : 'IMPORTANT DATES';

    return (
        <PublicLayout title={`${title} | ${competition?.name || app_name}`} appName={app_name} current="information" description={t('Important dates for the faculty sports championship.')} canonical={route('public.important-dates')}>
            <main>
                <PublicPageHero eyebrow={competition?.organization || t('Official competition')} title={title} intro={isMalay ? 'Jadual mesyuarat, persediaan dan acara utama kejohanan.' : 'Schedule of meetings, preparations and key championship events.'} icon={<CalendarDays className="size-4" />} />
                <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16">
                    <div className="mb-6 flex justify-end"><PublicStaleDataNotice updatedAt={updated_at} /></div>
                    <div className="public-card overflow-x-auto">
                        <table className="w-full min-w-[720px] border-collapse text-left text-sm">
                            <caption className="sr-only">{isMalay ? 'Tarikh-tarikh penting Kejohanan Sukan Antara Fakulti' : 'Important dates for the Inter-Faculty Sports Championship'}</caption>
                            <thead className="bg-[var(--public-dark)] text-white">
                                <tr>
                                    <th scope="col" className="w-20 px-5 py-4 font-black">{isMalay ? 'BIL' : 'NO.'}</th>
                                    <th scope="col" className="px-5 py-4 font-black">{isMalay ? 'PERKARA' : 'ITEM'}</th>
                                    <th scope="col" className="w-72 px-5 py-4 font-black">{isMalay ? 'TARIKH' : 'DATE'}</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-[var(--public-dark-border)] text-[var(--public-dark-faint)]">
                                {importantDates.map(([number, item, date]) => <tr key={number} className="align-top even:bg-[var(--public-background)]">
                                    <td className="px-5 py-4 font-black text-[var(--public-primary)]">{number}</td>
                                    <td className="px-5 py-4 font-semibold leading-6 text-[var(--public-text)]">{isMalay ? item : importantDateEnglish[number]?.[0] || item}</td>
                                    <td className="px-5 py-4 leading-6">{isMalay ? date : importantDateEnglish[number]?.[1] || date}</td>
                                </tr>)}
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </PublicLayout>
    );
}
