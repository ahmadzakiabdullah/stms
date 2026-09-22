import PublicLayout from '@/Layouts/PublicLayout';
import PublicPageHero from '@/components/PublicPageHero';
import PublicStaleDataNotice from '@/components/PublicStaleDataNotice';
import { useI18n } from '@/lib/i18n';
import { Users } from 'lucide-react';

type Props = {
    app_name: string;
    competition: { name: string; description: string | null; organization: string | null } | null;
    updated_at?: string;
};

type Person = { name: string; detail?: string };
type CommitteeGroup = { title: string; people: Person[] };

const committeeGroups: CommitteeGroup[] = [
    { title: 'Penaung', people: [{ name: 'YBhg. Profesor Datuk Ts. Dr. Massila bt Kamalrudin', detail: 'Naib Canselor' }] },
    { title: 'Penasihat I', people: [{ name: 'YBrs. Prof. Madya Datuk Dr. Sabri bin Mohamad Sharif', detail: 'Timbalan Naib Canselor (Hal Ehwal Pelajar & Alumni)' }] },
    { title: 'Penasihat II', people: [{ name: 'Pn. Noorazilah bt Mohamed', detail: 'Timbalan Pendaftar Kanan, HEPA' }] },
    { title: 'Pengerusi', people: [{ name: 'Pn. Siti Sarah binti Bujang', detail: 'Pengarah Pusat Sukan' }] },
    { title: 'Timbalan Pengerusi', people: [{ name: 'En. Azrin Bin Sariman', detail: 'Pusat Sukan' }] },
    { title: 'Setiausaha', people: [{ name: 'En. Razali bin Yaakob', detail: 'Pusat Sukan' }] },
    { title: 'Bendahari', people: [{ name: 'En. Mohd Asri Bin Mohd Mokhtar', detail: 'Pusat Sukan' }] },
    {
        title: 'Urusetia',
        people: [
            { name: 'En. Hadzren Redza bin Norhidzam Amin Akbar' },
            { name: 'Pn. Norashikin bt Hashim' },
            { name: 'En. Mohd Yusri bin Misron', detail: 'Pusat Sukan' },
        ],
    },
    {
        title: 'Teknikal & Pertandingan',
        people: [
            { name: 'En. Faizol Ikmal bin Shariff' },
            { name: 'En. Ahmad Rhafaee bin Samsudin' },
            { name: 'En. Ahmad Affenday bin Mohamed Sani' },
            { name: 'En. Naim Nukman bin Noorhisham', detail: 'Pusat Sukan' },
        ],
    },
    {
        title: 'Majlis Penutup & Penyampaian Hadiah',
        people: [
            { name: 'En. Mohd Farid bin Mohd Khalid' },
            { name: 'Pn. Norasikin bt Md. Isa', detail: 'Pusat Sukan' },
        ],
    },
    {
        title: 'Logistik, Venue & Peralatan Sukan',
        people: [
            { name: 'Pn. Zuraidah binti Abdullah' },
            { name: 'En Muhammad Ashraff bin Nor Rizan' },
            { name: 'En. Muhammad Faizal bin Mazli', detail: 'Pusat Sukan' },
        ],
    },
    {
        title: 'Jamuan',
        people: [
            { name: 'En. Mohd Asri Bin Mohd Mokhtar' },
            { name: 'En. Mohd Yazid bin Abu', detail: 'Pusat Sukan' },
        ],
    },
];

const universityRepresentatives: Person[] = [
    { name: 'Pegawai kanan Universiti', detail: '9 Orang' },
    { name: 'Fakulti FTKE', detail: 'Dekan dan Timbalan Dekan Pembangunan Pelajar' },
    { name: 'Fakulti FTKEK', detail: 'Dekan dan Timbalan Dekan Pembangunan Pelajar' },
    { name: 'Fakulti FTMK', detail: 'Dekan dan Timbalan Dekan Pembangunan Pelajar' },
    { name: 'Fakulti FTKM', detail: 'Dekan dan Timbalan Dekan Pembangunan Pelajar' },
    { name: 'Fakulti FTKIP', detail: 'Dekan dan Timbalan Dekan Pembangunan Pelajar' },
    { name: 'Fakulti FPTT', detail: 'Dekan dan Timbalan Dekan Pembangunan Pelajar' },
    { name: 'Fakulti FAIX', detail: 'Dekan dan Timbalan Dekan Pembangunan Pelajar' },
    { name: 'Sekolah Asas Teknikal & Pengajian Diploma', detail: 'Dekan dan Timbalan Dekan Pembangunan Pelajar' },
];

const committeeMembers = [
    'YBrs. Dr. Mohamad Fuzi bin Saidin (Pusat Kesihatan UTeM)',
    'DSP P/B Ahmad Shakir bin Yahaya (Polis Bantuan)',
    'En. Ainuddin bin Abu Kasim (PPF)',
    'YBrs. Ir. Dr. Mohd Rayme bin Anang Masuri (PPP)',
    'En. Shuharezuan bin Ramli (Bahagian Muzik)',
    'En. Nor Mohammad Nazirul bin Zahari (HEPA)',
    'En. Mohd Hanapiah bin Md Lip (U-Globe) Protokol',
    'En. Nurhafidz bin Abdul Sahak (U-Globe) MultiMedia',
    'Pn. Hanisah binti Hamdzah (U-Globe) Rekabentuk',
    'Dr. Johanna bt Abdullah Jaafar (CREATE)',
    'Pn. Siti Rohana bt Omar (CENSEI)',
    'Pn. Azilina binti Md Buang (PPPK)',
    'Pn. Fatonah binti Salehuddin (Penerbit Universiti)',
    'En. Hafizi bin Mohamad (HEPA)',
    'Sdr. Muhammad Airul Aiman bin Rosli (Pengarah Program)',
    'Sdri. Izni Izzaty binti Mizham (Timb. Pengarah Program 1)',
    'Sdr. Abdul Rashid bin Zulkefli (Timb. Pengarah Program 2)',
];

export default function PublicCommittee({ app_name, competition, updated_at }: Props) {
    const { t, locale } = useI18n();
    const isMalay = locale === 'ms';
    const title = isMalay ? 'JAWATANKUASA INDUK & JAWATANKUASA STAF (PENYELARAS)' : 'MAIN COMMITTEE & STAFF COMMITTEE (COORDINATORS)';

    return (
        <PublicLayout title={`${title} | ${competition?.name || app_name}`} appName={app_name} current="information" description={t('Committee information for the faculty sports championship.')} canonical={route('public.committee')}>
            <main>
                <PublicPageHero eyebrow={competition?.organization || t('Official competition')} title={title} intro={isMalay ? 'KEJOHANAN SUKAN ANTARA FAKULTI KALI KE-19, 2025' : '19TH INTER-FACULTY SPORTS CHAMPIONSHIP, 2025'} icon={<Users className="size-4" />} />
                <div className="mx-auto max-w-6xl px-4 py-12 sm:px-6 sm:py-16">
                    <div className="mb-6 flex justify-end"><PublicStaleDataNotice updatedAt={updated_at} /></div>
                    <div className="grid gap-6 lg:grid-cols-2">
                        {committeeGroups.map(group => <CommitteeCard key={group.title} group={group} locale={locale} />)}
                    </div>
                    <section className="public-card mt-8 p-6 sm:p-8">
                        <h2 className="text-2xl font-black text-[var(--public-text)]">{isMalay ? 'PEGAWAI KANAN UNIVERSITI DAN WAKIL FAKULTI' : 'SENIOR UNIVERSITY OFFICERS AND FACULTY REPRESENTATIVES'}</h2>
                        <div className="mt-6 divide-y divide-[var(--public-dark-border)]">
                            {universityRepresentatives.map(person => <PersonRow key={person.name} person={person} locale={locale} />)}
                        </div>
                    </section>
                    <section className="public-card mt-8 p-6 sm:p-8">
                        <h2 className="text-2xl font-black text-[var(--public-text)]">{isMalay ? 'AHLI JAWATANKUASA' : 'COMMITTEE MEMBERS'}</h2>
                        <ol className="mt-6 grid gap-x-8 gap-y-3 pl-6 leading-7 text-[var(--public-dark-faint)] marker:font-black marker:text-[var(--public-primary)] lg:grid-cols-2">
                            {committeeMembers.map(member => <li key={member} className="pl-2">{localizeCommitteeMember(member, locale)}</li>)}
                        </ol>
                    </section>
                </div>
            </main>
        </PublicLayout>
    );
}

function CommitteeCard({ group, locale }: { group: CommitteeGroup; locale: string }) {
    return <section className="public-card p-6 sm:p-8">
        <h2 className="text-lg font-black text-[var(--public-text)]">{localizeCommitteeTitle(group.title, locale)}</h2>
        <div className="mt-4 divide-y divide-[var(--public-dark-border)]">
            {group.people.map(person => <PersonRow key={person.name} person={person} locale={locale} />)}
        </div>
    </section>;
}

function PersonRow({ person, locale }: { person: Person; locale: string }) {
    return <div className="py-3 first:pt-0 last:pb-0">
        <p className="font-semibold leading-6 text-[var(--public-text)]">{localizeCommitteeMember(person.name, locale)}</p>
        {person.detail && <p className="mt-1 text-sm leading-6 text-[var(--public-dark-faint)]">{localizeCommitteeDetail(person.detail, locale)}</p>}
    </div>;
}

function localizeCommitteeTitle(value: string, locale: string): string {
    if (locale === 'ms') return value;
    return ({
        'Penaung': 'Patron',
        'Penasihat I': 'Advisor I',
        'Penasihat II': 'Advisor II',
        'Pengerusi': 'Chairperson',
        'Timbalan Pengerusi': 'Deputy Chairperson',
        'Setiausaha': 'Secretary',
        'Bendahari': 'Treasurer',
        'Urusetia': 'Secretariat',
        'Teknikal & Pertandingan': 'Technical & Competition',
        'Majlis Penutup & Penyampaian Hadiah': 'Closing Ceremony & Prize Presentation',
        'Logistik, Venue & Peralatan Sukan': 'Logistics, Venues & Sports Equipment',
        'Jamuan': 'Hospitality',
    } as Record<string, string>)[value] || value;
}

function localizeCommitteeDetail(value: string, locale: string): string {
    if (locale === 'ms') return value;
    return value
        .replace('Timbalan Naib Canselor', 'Deputy Vice-Chancellor')
        .replace('Naib Canselor', 'Vice-Chancellor')
        .replace('Timbalan Pendaftar Kanan', 'Senior Deputy Registrar')
        .replace('Hal Ehwal Pelajar & Alumni', 'Student Affairs & Alumni')
        .replace('Pengarah Pusat Sukan', 'Director, Sports Centre')
        .replace('Pusat Sukan', 'Sports Centre');
}

function localizeCommitteeMember(value: string, locale: string): string {
    if (locale === 'ms') return value;
    return value
        .replace('Pusat Kesihatan UTeM', 'UTeM Health Centre')
        .replace('Polis Bantuan', 'Auxiliary Police')
        .replace('Bahagian Muzik', 'Music Division')
        .replace('Penerbit Universiti', 'University Publisher')
        .replace('Pengarah Program', 'Programme Director')
        .replace('Timb. Programme Director', 'Deputy Programme Director')
        .replace('Timb. Pengarah Program', 'Deputy Programme Director')
        .replace('Rekabentuk', 'Design')
        .replace('En. ', 'Mr. ')
        .replace('Pn. ', 'Ms. ')
        .replace('Sdr. ', 'Mr. ')
        .replace('Sdri. ', 'Ms. ');
}
