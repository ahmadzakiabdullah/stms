import PublicLayout from '@/Layouts/PublicLayout';
import PublicErrorState from '@/components/PublicErrorState';
import PublicPageHero from '@/components/PublicPageHero';
import PublicStaleDataNotice from '@/components/PublicStaleDataNotice';
import SafeImage from '@/components/SafeImage';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import { useI18n } from '@/lib/i18n';
import { Link, router } from '@inertiajs/react';
import { BookOpen, CheckCircle2, CircleHelp, Download, FileText, Newspaper, Trophy } from 'lucide-react';
import { type ComponentType } from 'react';

type Section = 'news' | 'downloads' | 'faq' | 'about' | 'general';
type Props = { section: Section; app_name: string; competition: { name: string; description: string | null; organization: string | null } | null; updated_at?: string; error?: string | null };
const studentDocuments = [
    { fileName: 'BORANG BANTAHAN_SAF 20, 2026.pdf', ms: 'Borang Bantahan SAF Ke-20, 2026', en: 'SAF 20, 2026 Appeal Form' },
    { fileName: 'BORANG DEKLERASI KESIHATAN_SAF 20, 2026.pdf', ms: 'Borang Deklarasi Kesihatan SAF Ke-20, 2026', en: 'SAF 20, 2026 Health Declaration Form' },
    { fileName: 'BORANG MAKLUMAT KONTINJEN_SAF 20, 2026.pdf', ms: 'Borang Maklumat Kontinjen SAF Ke-20, 2026', en: 'SAF 20, 2026 Contingent Information Form' },
    { fileName: 'JADUAL UMUM_SAF 20, 2026.pdf', ms: 'Jadual Umum SAF Ke-20, 2026', en: 'SAF 20, 2026 General Schedule' },
    { fileName: 'PERATURAN AM SAF KE - 20 2026.pdf', ms: 'Peraturan Am SAF Ke-20, 2026', en: 'SAF 20, 2026 General Regulations' },
    { fileName: 'PERATURAN PERBARISAN SAF 20, 2026.pdf', ms: 'Peraturan Perbarisan SAF 20, 2026', en: 'SAF 20, 2026 Parade Regulations' },
];
const meta: Record<Section, { title: string; intro: string; icon: ComponentType<{ className?: string }>; routeName: string }> = {
    news: { title: 'Announcements', intro: 'Official updates and competition notices.', icon: Newspaper, routeName: 'public.news' },
    downloads: { title: 'Downloads', intro: 'Useful competition documents and resources.', icon: Download, routeName: 'public.downloads' },
    faq: { title: 'Frequently Asked Questions', intro: 'Answers to common questions about the competition.', icon: CircleHelp, routeName: 'public.faq' },
    about: { title: 'About SAF', intro: 'Learn more about the Sports and Athletics Festival.', icon: Trophy, routeName: 'public.about' },
    general: { title: 'General Information', intro: 'Eligibility and registration information for the faculty sports championship.', icon: CheckCircle2, routeName: 'public.general-information' },
};

export default function PublicInfo({ section, app_name, competition, updated_at, error = null }: Props) {
    const { t, locale } = useI18n();
    const current = meta[section];
    const Icon = current.icon;

    return (
        <PublicLayout title={`${t(current.title)} | ${competition?.name || app_name}`} appName={app_name} current={section === 'general' ? 'general-information' : section} description={t(current.intro)} canonical={route(current.routeName)}>
            <main>
                {error && <div className="mx-auto max-w-7xl px-4 pt-8 sm:px-6"><PublicErrorState title={t('Information unavailable')} description={error} onRetry={() => router.reload()} /></div>}
                <PublicPageHero eyebrow={competition?.organization || t('Official competition')} title={t(current.title)} intro={t(current.intro)} icon={<Icon className="size-4" />} />
                <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16"><div className="mb-6 flex justify-end"><PublicStaleDataNotice updatedAt={updated_at} /></div><Content section={section} competitionName={competition?.name || app_name} locale={locale} t={t} /></div>
            </main>
        </PublicLayout>
    );
}

function Content({ section, competitionName, locale, t }: { section: Section; competitionName: string; locale: string; t: (key: string) => string }) {
    if (section === 'general') return <GeneralInformation locale={locale} />;
    if (section === 'about') return <article className="public-card p-6 sm:p-10"><div className="relative z-0 mx-auto max-w-5xl space-y-5 leading-7 text-[var(--public-dark-faint)]"><BookOpen className="relative z-10 size-8 text-[var(--public-primary)]" /><h2 className="relative z-10 text-2xl font-black text-[var(--public-text)]">{competitionName}</h2><p className="relative z-10">{t('The Sports and Athletics Festival brings together faculties and students in a celebration of competition, teamwork and university spirit.')}</p><p className="relative z-10">{t('This official portal provides schedules, match updates, results, medal standings and competition information in one place.')}</p></div></article>;
    if (section === 'faq') {
        const items = [
            { question: 'Where can I find the latest schedule?', answer: locale === 'ms' ? 'Jadual rasmi dan perubahan perlawanan diterbitkan pada halaman Jadual & Keputusan.' : 'The official schedule and fixture changes are published on the Schedule & Results page.', href: route('public.schedule'), action: locale === 'ms' ? 'Lihat jadual' : 'View schedule' },
            { question: 'Where are official results published?', answer: locale === 'ms' ? 'Keputusan rasmi dan kedudukan pingat dikemas kini pada halaman Jadual & Keputusan selepas disahkan.' : 'Official results and medal standings are updated on the Schedule & Results page after confirmation.', href: route('public.schedule'), action: locale === 'ms' ? 'Lihat keputusan' : 'View results' },
            { question: 'How can I contact the secretariat?', answer: locale === 'ms' ? 'Hubungi urus setia melalui e-mel atau telefon yang disenaraikan pada halaman Hubungi Kami.' : 'Contact the secretariat using the email or phone details listed on the Contact Us page.', href: route('public.contact'), action: locale === 'ms' ? 'Hubungi urus setia' : 'Contact the secretariat' },
        ];

        return <Accordion type="single" collapsible className="public-card px-5">{items.map((item, index) => <AccordionItem key={item.question} value={`question-${index}`}><AccordionTrigger>{t(item.question)}</AccordionTrigger><AccordionContent><p>{item.answer}</p><Link href={item.href} className="mt-3 inline-flex min-h-11 items-center font-bold text-[var(--public-primary)] underline decoration-[var(--public-primary-border)] underline-offset-4 hover:text-[var(--public-dark)]">{item.action}</Link></AccordionContent></AccordionItem>)}</Accordion>;
    }
    if (section === 'downloads') return <article className="public-card overflow-hidden p-6 sm:p-8"><div className="grid items-center gap-5 sm:grid-cols-[10rem_1fr]"><SafeImage src="/images/mascots/pose-tunjuk.webp" alt={locale === 'ms' ? 'Maskot SAF 20 menunjukkan sumber rasmi' : 'SAF 20 mascot pointing to official resources'} loading="lazy" decoding="async" className="mx-auto h-44 w-full object-contain drop-shadow-xl sm:h-52" /><div><Download className="relative z-10 size-8 text-[var(--public-primary)]" /><h2 className="relative z-10 mt-4 text-xl font-black text-[var(--public-text)]">{t('Official resources')}</h2><p className="relative z-10 mt-2 text-sm leading-6 text-[var(--public-dark-faint)]">{locale === 'ms' ? 'Borang dan dokumen rasmi untuk rujukan serta urusan penyertaan SAF 20.' : 'Official forms and documents for SAF 20 participation and reference.'}</p></div></div><div className="mt-6 grid gap-3 border-t border-[var(--public-dark-border)] pt-6 sm:grid-cols-2">{studentDocuments.map(document => <a key={document.fileName} href={`/public-files/documents/forms/${encodeURIComponent(document.fileName)}`} download={document.fileName} className="group flex min-h-16 items-center justify-between gap-4 rounded-2xl border border-[var(--public-dark-border)] bg-[var(--public-background)] px-4 py-3 transition hover:border-[var(--public-primary-border)] hover:bg-white hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--public-primary)]/50"><span className="flex min-w-0 items-center gap-3"><span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-[var(--public-primary-soft)] text-[var(--public-primary)]"><FileText className="size-4" aria-hidden="true" /></span><span className="min-w-0 text-sm font-bold leading-5 text-[var(--public-text)]">{locale === 'ms' ? document.ms : document.en}<span className="mt-0.5 block text-xs font-semibold uppercase tracking-wider text-[var(--public-dark-faint)]">PDF</span></span></span><Download className="size-4 shrink-0 text-[var(--public-primary)] transition group-hover:translate-y-0.5" aria-hidden="true" /></a>)}</div></article>;
    return <div className="public-card p-8"><Newspaper className="relative z-10 size-8 text-[var(--public-primary)]" /><h2 className="relative z-10 mt-4 text-xl font-black text-[var(--public-text)]">{t('No announcements yet')}</h2><p className="relative z-10 mt-2 text-sm text-[var(--public-dark-faint)]">{t('Official announcements will appear here when published by the secretariat.')}</p></div>;
}

function GeneralInformation({ locale }: { locale: string }) {
    const isMalay = locale === 'ms';
    const eligibility = isMalay ? [
        'Semua pelajar yang berdaftar di fakulti/sekolah masing-masing dan menghadiri kursus sepenuh masa di peringkat Diploma & Ijazah Pertama yang ditawarkan di UTeM sahaja.',
        'Pelajar yang tidak menghadiri kuliah sepenuh masa dan menunggu konvokesyen adalah tidak layak untuk bermain acara sukan dalam Sukan Antara Fakulti.',
        'Bagi pelajar Pasca Siswazah dibenarkan untuk mewakili fakulti/sekolah yang didaftarkan sahaja. Had bilangan penyertaan ditetapkan 10 orang maksimum bagi setiap fakulti/sekolah.',
        'Hanya seorang (1) pelajar “Mobility” dibenarkan bermain untuk satu (1) acara sukan sahaja.',
        'Seorang pelajar hanya dibenarkan bermain 1 acara sukan sahaja.',
        'Bagi peserta yang mempunyai masalah kesihatan adalah DIGALAKKAN menjalani pemeriksaan kesihatan sebelum kejohanan berlangsung dan menyerahkan borang kepada Sekretariat kejohanan (Borang Medical dan Borang Jamin Diri).',
    ] : [
        'All students registered with their respective faculty/school and attending full-time Diploma or Bachelor’s Degree programmes offered by UTeM are eligible.',
        'Students who are not attending full-time lectures and are awaiting convocation are not eligible to participate in events at the Inter-Faculty Sports Championship.',
        'Postgraduate students may represent only the faculty/school where they are registered. Participation is limited to a maximum of 10 students for each faculty/school.',
        'Only one (1) Mobility student is allowed to participate in one sport event only.',
        'Each student is allowed to participate in one sport event only.',
        'Participants with health conditions are encouraged to undergo a medical examination before the championship and submit the form to the secretariat (Medical Form and Self-Declaration Form).',
    ];

    return <article className="public-card p-6 sm:p-10">
        <div className="mx-auto max-w-5xl">
        <header className="grid items-center gap-5 border-b border-[var(--public-dark-border)] pb-6 sm:grid-cols-[1fr_auto]">
            <div>
                <p className="text-sm font-black uppercase tracking-[.14em] text-[var(--public-primary)]">{isMalay ? 'KELAYAKAN & PENDAFTARAN' : 'ELIGIBILITY & REGISTRATION'}</p>
                <h2 className="mt-3 text-2xl font-black leading-tight text-[var(--public-text)] sm:text-3xl">{isMalay ? 'KEJOHANAN SUKAN ANTARA FAKULTI 20, 2026' : 'INTER-FACULTY SPORTS CHAMPIONSHIP 20, 2026'}</h2>
            </div>
            <figure className="mx-auto w-full max-w-[12rem] sm:mx-0 sm:w-48">
                <SafeImage src="/images/mascots/pose-with-flag.webp" alt={isMalay ? 'Maskot SAF 20 mengibarkan bendera UTeM' : 'SAF 20 mascot holding the UTeM flag'} loading="lazy" decoding="async" className="max-h-64 w-full rounded-2xl object-contain drop-shadow-xl" />
            </figure>
        </header>
        <section className="mt-8">
            <h3 className="text-xl font-black text-[var(--public-text)]">{isMalay ? 'PESERTA' : 'PARTICIPANTS'}</h3>
            <ol className="mt-4 list-decimal space-y-4 pl-6 leading-7 text-[var(--public-dark-faint)]">
                {eligibility.map(item => <li key={item} className="pl-2">{item}</li>)}
            </ol>
            <div className="mt-6 grid items-center gap-3 rounded-2xl bg-[var(--public-primary-soft)] px-5 pt-4 sm:grid-cols-[1fr_auto] sm:pt-0">
                <p className="py-2 text-sm font-semibold leading-6 text-[var(--public-text)]">{isMalay ? 'Jaga kesihatan, berehat dan minum air secukupnya semasa menyertai pertandingan. Rujuk borang kesihatan rasmi di bawah.' : 'Look after your health, rest and drink enough water during the competition. See the official health forms below.'}</p>
                <SafeImage src="/images/mascots/pose-rehat.webp" alt={isMalay ? 'Maskot SAF 20 berehat dan minum air' : 'SAF 20 mascot resting and drinking water'} loading="lazy" decoding="async" className="mx-auto h-40 w-auto max-w-full object-contain drop-shadow-xl sm:h-48" />
            </div>
        </section>
        <section className="mt-10 border-t border-[var(--public-dark-border)] pt-8">
            <h3 className="text-xl font-black text-[var(--public-text)]">{isMalay ? 'PASUKAN' : 'TEAMS'}</h3>
            <ol start={7} className="mt-4 list-decimal pl-6 leading-7 text-[var(--public-dark-faint)]">
                <li className="pl-2">{isMalay ? 'Pertandingan sesuatu acara hanya akan dijalankan sekiranya penyertaan lebih 4 pasukan yang mengambil bahagian. (Merujuk kepada Peraturan Am SAF perkara 5.0.)' : 'An event will only be conducted if more than 4 teams take part. (Refer to SAF General Regulations, clause 5.0.)'}</li>
            </ol>
        </section>
        <section className="mt-10 border-t border-[var(--public-dark-border)] pt-8">
            <h3 className="text-xl font-black text-[var(--public-text)]">{isMalay ? 'HADIAH' : 'PRIZES'}</h3>
            <p className="mt-4 leading-7 text-[var(--public-dark-faint)]">{isMalay ? 'Hadiah: Pingat – pingat yang ditawarkan sebanyak 30 Emas, 30 Perak dan 30 Gangsa.' : 'Prizes: Medals – a total of 30 Gold, 30 Silver and 30 Bronze medals are offered.'}</p>
        </section>
        </div>
        <section className="mt-10 border-t border-[var(--public-dark-border)] pt-8" aria-labelledby="student-documents-title">
            <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <p className="text-sm font-black uppercase tracking-[.14em] text-[var(--public-primary)]">{isMalay ? 'DOKUMEN RASMI' : 'OFFICIAL DOCUMENTS'}</p>
                    <h3 id="student-documents-title" className="mt-2 text-xl font-black text-[var(--public-text)]">{isMalay ? 'Borang & Dokumen Pelajar' : 'Student Forms & Documents'}</h3>
                    <p className="mt-2 text-sm leading-6 text-[var(--public-dark-faint)]">{isMalay ? 'Muat turun borang dan dokumen rasmi untuk rujukan serta urusan penyertaan.' : 'Download official forms and documents for participation and reference.'}</p>
                </div>
                <FileText className="size-8 shrink-0 text-[var(--public-primary)]" aria-hidden="true" />
            </div>
            <div className="mt-6 grid gap-3 sm:grid-cols-2">
                {studentDocuments.map(document => (
                    <a key={document.fileName} href={`/public-files/documents/forms/${encodeURIComponent(document.fileName)}`} download={document.fileName} className="group flex min-h-16 items-center justify-between gap-4 rounded-2xl border border-[var(--public-dark-border)] bg-[var(--public-background)] px-4 py-3 transition hover:-translate-y-0.5 hover:border-[var(--public-primary-border)] hover:bg-white hover:shadow-[0_16px_35px_-28px_rgba(7,27,51,.8)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--public-primary)]/50">
                        <span className="flex min-w-0 items-center gap-3">
                            <span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-[var(--public-primary-soft)] text-[var(--public-primary)]"><FileText className="size-4" aria-hidden="true" /></span>
                            <span className="min-w-0 text-sm font-bold leading-5 text-[var(--public-text)]">{isMalay ? document.ms : document.en}<span className="mt-0.5 block text-xs font-semibold uppercase tracking-wider text-[var(--public-dark-faint)]">PDF</span></span>
                        </span>
                        <Download className="size-4 shrink-0 text-[var(--public-primary)] transition group-hover:translate-y-0.5" aria-hidden="true" />
                    </a>
                ))}
            </div>
        </section>
    </article>;
}
