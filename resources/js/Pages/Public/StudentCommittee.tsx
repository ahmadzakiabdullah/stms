import PublicLayout from '@/Layouts/PublicLayout';
import PublicPageHero from '@/components/PublicPageHero';
import PublicStaleDataNotice from '@/components/PublicStaleDataNotice';
import { useI18n } from '@/lib/i18n';
import { UsersRound } from 'lucide-react';

type Props = {
    app_name: string;
    competition: { name: string; description: string | null; organization: string | null } | null;
    updated_at?: string;
};

type CommitteeBlock = { title: string; entries: string[] };

const committeeBlocks: CommitteeBlock[] = [
    { title: 'Pengarah Program/Pengerusi', entries: ['Sdr. Muhammad Airul Aiman bin Rosli'] },
    { title: 'Timbalan Pengarah Program 1', entries: ['Sdri. Izni Izzaty binti Mizham'] },
    { title: 'Timbalan Pengarah Program 2', entries: ['Sdr. Abdul Rashid bin Zulkefli'] },
    { title: 'Setiausaha', entries: ['Sdri. Mazziatul Najwa binti Mazlan'] },
    { title: 'Urusetia & Tugas Khas & Penajaan', entries: ['Sdri. Nurul Syawalna Nasuha binti Azli'] },
    { title: 'Jawatankuasa Khas MPP', entries: ['Sdr. Muhammad Iqbal bin Azri', 'Sdri. Nurul Nabihah Najwa binti Norazwari'] },
    { title: 'Wakil Fakulti FTKE', entries: ['Pengerusi Kelab Pelajar Fakulti FTKE', 'Exco Sukan Kelab Pelajar Fakulti FTKE'] },
    { title: 'Wakil Fakulti FTKEK', entries: ['Pengerusi Kelab Pelajar Fakulti FTKEK', 'Exco Sukan Kelab Pelajar Fakulti FTKEK'] },
    { title: 'Wakil Fakulti FTMK', entries: ['Pengerusi Kelab Pelajar Fakulti FTMK', 'Exco Sukan Kelab Pelajar Fakulti FTMK'] },
    { title: 'Wakil Fakulti FTKM', entries: ['Pengerusi Kelab Pelajar Fakulti FTKM', 'Exco Sukan Kelab Pelajar Fakulti FTKM'] },
    { title: 'Wakil Fakulti FTKIP', entries: ['Pengerusi Kelab Pelajar Fakulti FTKIP', 'Exco Sukan Kelab Pelajar Fakulti FTKIP'] },
    { title: 'Wakil Fakulti FPTT', entries: ['Pengerusi Kelab Pelajar Fakulti FPTT', 'Exco Sukan Kelab Pelajar Fakulti FPTT'] },
    { title: 'Wakil Fakulti FAIX', entries: ['Pengerusi Kelab Pelajar Fakulti FAIX', 'Exco Sukan Kelab Pelajar Fakulti FAIX'] },
    { title: 'Wakil STEP', entries: ['Pengerusi Kelab Pelajar STEP', 'Exco Sukan Kelab Pelajar STEP'] },
    { title: 'Teknikal & Pertandingan', entries: ['Sdr. Nurul Shakirah binti Zainuddin', 'Bilangan Sukarelawan'] },
    { title: 'Siaraya', entries: ['Sdr. Muhammad Aidil Iskandar bin Osman', 'Rakan Siaraya HEPA', 'Bilangan Sukarelawan'] },
    { title: 'Tapak Karnival', entries: ['Sdri. Nurein Afiefah bt Mohd Zaki', 'MPP Exco Keusahawanan', 'Bilangan Sukarelawan'] },
    { title: 'Majlis Rasmi dan Penyampaian Hadiah', entries: ['Sdr. Muhammad Azim bin Adenan', 'Bilangan Sukarelawan'] },
    { title: 'Publisiti dan Promosi', entries: ['UTeM TV & Media SAF', 'Bilangan Sukarelawan'] },
    { title: 'Jamuan dan Makanan', entries: ['Sdr. Aina Afrina bt Sarudin', 'Bilangan Sukarelawan'] },
    { title: 'Peralatan & Venue Pertandingan', entries: ['Sdr. Amirul Shafiq bin Aspian', 'Bilangan Sukarelawan'] },
    { title: 'Team Go Green', entries: ['Muhammad Syakir bin Marzuki', 'Bilangan Sukarelawan'] },
    { title: 'Jawatankuasa Keselamatan', entries: ['Sdr. Muhammad Haris Izzat bin Affandi', 'Bilangan Sukarelawan'] },
    { title: 'Perubatan dan Kecemasan', entries: ['Kelab Rakan Kesihatan (KRK) & Kor SISPA', 'Bilangan Sukarelawan'] },
    { title: 'Persembahan Majlis Perasmian & Penutup', entries: ['Muhammad Nasyrihan bin Mohamad Shawal', 'Bilangan Sukarelawan'] },
];

export default function PublicStudentCommittee({ app_name, competition, updated_at }: Props) {
    const { t, locale } = useI18n();
    const isMalay = locale === 'ms';
    const title = isMalay ? 'JAWATANKUASA PELAKSANA' : 'EXECUTIVE COMMITTEE';

    return (
        <PublicLayout title={`${title} | ${competition?.name || app_name}`} appName={app_name} current="information" description={t('Student committee information for the faculty sports championship.')} canonical={route('public.student-committee')}>
            <main>
                <PublicPageHero eyebrow={competition?.organization || t('Official competition')} title={title} intro={isMalay ? 'JAWATANKUASA PELAKSANA (PELAJAR) · KEJOHANAN SUKAN ANTARA FAKULTI KALI KE-19, 2025' : 'STUDENT EXECUTIVE COMMITTEE · 19TH INTER-FACULTY SPORTS CHAMPIONSHIP, 2025'} icon={<UsersRound className="size-4" />} />
                <div className="mx-auto max-w-6xl px-4 py-12 sm:px-6 sm:py-16">
                    <div className="mb-6 flex justify-end"><PublicStaleDataNotice updatedAt={updated_at} /></div>
                    <div className="grid gap-6 lg:grid-cols-2">
                        {committeeBlocks.map(block => <CommitteeBlockCard key={block.title} block={block} locale={locale} />)}
                    </div>
                </div>
            </main>
        </PublicLayout>
    );
}

function CommitteeBlockCard({ block, locale }: { block: CommitteeBlock; locale: string }) {
    return <section className="public-card p-6 sm:p-8">
        <h2 className="text-lg font-black text-[var(--public-text)]">{localizeStudentCommitteeText(block.title, locale)}</h2>
        <ul className="mt-4 space-y-2 border-t border-[var(--public-dark-border)] pt-4 leading-7 text-[var(--public-dark-faint)]">
            {block.entries.map(entry => <li key={entry} className="pl-4 before:mr-2 before:text-[var(--public-primary)] before:content-['•']">{localizeStudentCommitteeText(entry, locale)}</li>)}
        </ul>
    </section>;
}

function localizeStudentCommitteeText(value: string, locale: string): string {
    if (locale === 'ms') return value;
    return value
        .replace('Pengarah Program/Pengerusi', 'Programme Director/Chairperson')
        .replace('Timbalan Pengarah Program', 'Deputy Programme Director')
        .replace('Setiausaha', 'Secretary')
        .replace('Urusetia & Tugas Khas & Penajaan', 'Secretariat, Special Duties & Sponsorship')
        .replace('Jawatankuasa Khas MPP', 'Special MPP Committee')
        .replace(/Pengerusi Kelab Pelajar Fakulti (\S+)/g, 'President, Faculty $1 Student Club')
        .replace(/Exco Sukan Kelab Pelajar Fakulti (\S+)/g, 'Sports Exco, Faculty $1 Student Club')
        .replace('Wakil Fakulti ', 'Faculty Representative ')
        .replace('Wakil STEP', 'STEP Representative')
        .replace('Teknikal & Pertandingan', 'Technical & Competition')
        .replace('Siaraya', 'Public Address')
        .replace('Rakan Public Address HEPA', 'HEPA Public Address Partner')
        .replace('Rakan Siaraya HEPA', 'HEPA Public Address Partner')
        .replace('Tapak Karnival', 'Carnival Site')
        .replace('Majlis Rasmi dan Penyampaian Hadiah', 'Official Ceremony and Prize Presentation')
        .replace('Publisiti dan Promosi', 'Publicity and Promotion')
        .replace('Jamuan dan Makanan', 'Catering and Food')
        .replace('Peralatan & Venue Pertandingan', 'Competition Equipment & Venues')
        .replace('Jawatankuasa Keselamatan', 'Safety Committee')
        .replace('Perubatan dan Kecemasan', 'Medical and Emergency')
        .replace('Persembahan Majlis Perasmian & Penutup', 'Opening & Closing Ceremony Performance')
        .replace('MPP Exco Keusahawanan', 'MPP Entrepreneurship Exco')
        .replace('Bilangan Sukarelawan', 'Number of Volunteers')
        .replace('En. ', 'Mr. ')
        .replace('Pn. ', 'Ms. ')
        .replace('Sdr. ', 'Mr. ')
        .replace('Sdri. ', 'Ms. ');
}
