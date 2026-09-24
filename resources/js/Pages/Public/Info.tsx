import PublicLayout from '@/Layouts/PublicLayout';
import PublicErrorState from '@/components/PublicErrorState';
import PublicPageHero from '@/components/PublicPageHero';
import PublicStaleDataNotice from '@/components/PublicStaleDataNotice';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import { useI18n } from '@/lib/i18n';
import { router } from '@inertiajs/react';
import { BookOpen, CheckCircle2, CircleHelp, Download, FileText, Newspaper, Trophy } from 'lucide-react';
import { type ComponentType } from 'react';

type Section = 'news' | 'downloads' | 'faq' | 'about' | 'general';
type Props = { section: Section; app_name: string; competition: { name: string; description: string | null; organization: string | null } | null; updated_at?: string; error?: string | null };
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
        <PublicLayout title={`${t(current.title)} | ${competition?.name || app_name}`} appName={app_name} current="information" description={t(current.intro)} canonical={route(current.routeName)}>
            <main>
                {error && <div className="mx-auto max-w-7xl px-4 pt-8 sm:px-6"><PublicErrorState title={t('Information unavailable')} description={error} onRetry={() => router.reload()} /></div>}
                <PublicPageHero eyebrow={competition?.organization || t('Official competition')} title={t(current.title)} intro={t(current.intro)} icon={<Icon className="size-4" />} />
                <div className="mx-auto max-w-4xl px-4 py-12 sm:px-6 sm:py-16"><div className="mb-6 flex justify-end"><PublicStaleDataNotice updatedAt={updated_at} /></div><Content section={section} competitionName={competition?.name || app_name} locale={locale} t={t} /></div>
            </main>
        </PublicLayout>
    );
}

function Content({ section, competitionName, locale, t }: { section: Section; competitionName: string; locale: string; t: (key: string) => string }) {
    if (section === 'general') return <GeneralInformation locale={locale} />;
    if (section === 'about') return <div className="public-card relative z-0 space-y-5 p-6 leading-7 text-[var(--public-dark-faint)] sm:p-10"><BookOpen className="relative z-10 size-8 text-[var(--public-primary)]" /><h2 className="relative z-10 text-2xl font-black text-[var(--public-text)]">{competitionName}</h2><p className="relative z-10">{t('The Sports and Athletics Festival brings together faculties and students in a celebration of competition, teamwork and university spirit.')}</p><p className="relative z-10">{t('This official portal provides schedules, match updates, results, medal standings and competition information in one place.')}</p></div>;
    if (section === 'faq') return <Accordion type="single" collapsible className="public-card px-5">{['Where can I find the latest schedule?', 'Where are official results published?', 'How can I contact the secretariat?'].map((question, index) => <AccordionItem key={question} value={`question-${index}`}><AccordionTrigger>{t(question)}</AccordionTrigger><AccordionContent>{t('Please refer to the official portal sections or contact the secretariat for the latest confirmed information.')}</AccordionContent></AccordionItem>)}</Accordion>;
    if (section === 'downloads') return <div className="public-card p-8"><FileText className="relative z-10 size-8 text-[var(--public-primary)]" /><h2 className="relative z-10 mt-4 text-xl font-black text-[var(--public-text)]">{t('Official resources')}</h2><p className="relative z-10 mt-2 text-sm text-[var(--public-dark-faint)]">{t('Downloads will be published here by the secretariat when official documents are approved.')}</p></div>;
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
        <header className="border-b border-[var(--public-dark-border)] pb-6">
            <p className="text-sm font-black uppercase tracking-[.14em] text-[var(--public-primary)]">{isMalay ? 'KELAYAKAN & PENDAFTARAN' : 'ELIGIBILITY & REGISTRATION'}</p>
            <h2 className="mt-3 text-2xl font-black leading-tight text-[var(--public-text)] sm:text-3xl">{isMalay ? 'KEJOHANAN SUKAN ANTARA FAKULTI 20, 2026' : 'INTER-FACULTY SPORTS CHAMPIONSHIP 20, 2026'}</h2>
        </header>
        <section className="mt-8">
            <h3 className="text-xl font-black text-[var(--public-text)]">{isMalay ? 'PESERTA' : 'PARTICIPANTS'}</h3>
            <ol className="mt-4 list-decimal space-y-4 pl-6 leading-7 text-[var(--public-dark-faint)]">
                {eligibility.map(item => <li key={item} className="pl-2">{item}</li>)}
            </ol>
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
    </article>;
}
