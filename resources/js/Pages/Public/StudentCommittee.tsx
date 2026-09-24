import PublicLayout from '@/Layouts/PublicLayout';
import PublicStaleDataNotice from '@/components/PublicStaleDataNotice';
import SafeImage from '@/components/SafeImage';
import { useI18n } from '@/lib/i18n';
import { type PageProps } from '@/types';
import { usePage } from '@inertiajs/react';

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
    const { settings = {}, session_branding: sessionBranding = { logo_url: null, inverse_logo_url: null } } = usePage<PageProps & {
        settings?: { logo_url?: string | null; inverse_logo_url?: string | null };
        session_branding?: { logo_url?: string | null; inverse_logo_url?: string | null };
    }>().props;
    const isMalay = locale === 'ms';
    const title = isMalay ? 'JAWATANKUASA PELAKSANA' : 'EXECUTIVE COMMITTEE';
    const organizationLogo = settings.logo_url ?? settings.inverse_logo_url;
    const sessionLogo = sessionBranding.logo_url ?? sessionBranding.inverse_logo_url;

    return (
        <PublicLayout title={`${title} | ${competition?.name || app_name}`} appName={app_name} current="information" description={t('Student committee information for the faculty sports championship.')} canonical={route('public.student-committee')}>
            <main>
                <div className="mx-auto max-w-5xl px-4 pb-12 pt-32 sm:px-6 sm:pb-16 sm:pt-40">
                    <div className="mb-6 flex justify-end"><PublicStaleDataNotice updatedAt={updated_at} /></div>
                    <article className="public-card p-6 sm:p-10 lg:p-14">
                        <header className="text-center">
                            {(organizationLogo || sessionLogo) && <div className="flex items-center justify-center gap-4 sm:gap-6">
                                {organizationLogo && <SafeImage src={organizationLogo} alt="" className="h-14 w-auto max-w-[10rem] object-contain sm:h-16" />}
                                {organizationLogo && sessionLogo && <span aria-hidden="true" className="h-12 w-px bg-[var(--public-dark-border)]" />}
                                {sessionLogo && <SafeImage src={sessionLogo} alt="" className="h-14 w-auto max-w-[10rem] object-contain sm:h-16" />}
                            </div>}
                            <p className="mt-7 text-sm font-black uppercase tracking-[.12em] text-[var(--public-primary)]">{isMalay ? 'JAWATANKUASA PELAKSANA (PELAJAR)' : 'STUDENT EXECUTIVE COMMITTEE'}</p>
                            <h1 className="mx-auto mt-4 max-w-3xl text-2xl font-black uppercase leading-tight text-[var(--public-text)] sm:text-3xl">{title}</h1>
                            <p className="mt-3 text-sm font-semibold uppercase tracking-wide text-[var(--public-dark-faint)]">{competition?.name || app_name}</p>
                        </header>

                        <div className="mt-10 divide-y divide-[var(--public-dark-border)] border-y border-[var(--public-dark-border)]">
                            {committeeBlocks.map(block => <CommitteeBlockRow key={block.title} block={block} locale={locale} />)}
                        </div>
                    </article>
                </div>
            </main>
        </PublicLayout>
    );
}

function CommitteeBlockRow({ block, locale }: { block: CommitteeBlock; locale: string }) {
    return <div className="grid gap-3 py-5 md:grid-cols-[14rem_1.25rem_minmax(0,1fr)] md:gap-4">
        <h2 className="font-semibold leading-6 text-[var(--public-text)]">{localizeStudentCommitteeText(block.title, locale)}</h2>
        <span className="hidden font-semibold text-[var(--public-primary)] md:block">:</span>
        <ul className="space-y-1 leading-7 text-[var(--public-dark-faint)]">
            {block.entries.map(entry => <li key={entry}>{localizeStudentCommitteeText(entry, locale)}</li>)}
        </ul>
    </div>;
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
