import PublicLayout from '@/Layouts/PublicLayout';
import PublicPageHero from '@/components/PublicPageHero';
import { useI18n } from '@/lib/i18n';
import { Head } from '@inertiajs/react';
import { BookOpen, CircleHelp, Download, FileText, Newspaper, Trophy } from 'lucide-react';
import { type ComponentType } from 'react';

type Section = 'news' | 'downloads' | 'faq' | 'about';
type Props = { section: Section; app_name: string; competition: { name: string; description: string | null; organization: string | null } | null };
const meta: Record<Section, { title: string; intro: string; icon: ComponentType<{ className?: string }> }> = { news: { title: 'Announcements', intro: 'Official updates and competition notices.', icon: Newspaper }, downloads: { title: 'Downloads', intro: 'Useful competition documents and resources.', icon: Download }, faq: { title: 'Frequently Asked Questions', intro: 'Answers to common questions about the competition.', icon: CircleHelp }, about: { title: 'About SAF', intro: 'Learn more about the Sports and Athletics Festival.', icon: Trophy } };

export default function PublicInfo({ section, app_name, competition }: Props) {
    const { t } = useI18n();
    const current = meta[section];
    const Icon = current.icon;

    return (
        <PublicLayout title={`${t(current.title)} | ${competition?.name || app_name}`} appName={app_name}>
            <Head><link rel="canonical" href={route(`public.${section}`)} /></Head>
            <main>
                <PublicPageHero eyebrow={competition?.organization || t('Official competition')} title={t(current.title)} intro={t(current.intro)} icon={<Icon className="size-4" />} />
                <div className="mx-auto max-w-4xl px-4 py-12 sm:px-6 sm:py-16"><Content section={section} competitionName={competition?.name || app_name} t={t} /></div>
            </main>
        </PublicLayout>
    );
}

function Content({ section, competitionName, t }: { section: Section; competitionName: string; t: (key: string) => string }) {
    if (section === 'about') return <div className="space-y-5 rounded-2xl border border-[var(--public-dark-border)] bg-white p-6 leading-7 text-[var(--public-dark-faint)] shadow-sm sm:p-10"><BookOpen className="size-8 text-[var(--public-primary)]" /><h2 className="text-2xl font-black text-[var(--public-text)]">{competitionName}</h2><p>{t('The Sports and Athletics Festival brings together faculties and students in a celebration of competition, teamwork and university spirit.')}</p><p>{t('This official portal provides schedules, match updates, results, medal standings and competition information in one place.')}</p></div>;
    if (section === 'faq') return <div className="space-y-4">{['Where can I find the latest schedule?', 'Where are official results published?', 'How can I contact the secretariat?'].map(question => <details key={question} className="group rounded-2xl border border-[var(--public-dark-border)] bg-white p-5 shadow-sm"><summary className="cursor-pointer list-none font-black text-[var(--public-text)]">{t(question)}</summary><p className="mt-3 border-t border-[var(--public-dark-border)] pt-3 text-sm leading-6 text-[var(--public-dark-faint)]">{t('Please refer to the official portal sections or contact the secretariat for the latest confirmed information.')}</p></details>)}</div>;
    if (section === 'downloads') return <div className="rounded-2xl border border-[var(--public-dark-border)] bg-white p-8 shadow-sm"><FileText className="size-8 text-[var(--public-primary)]" /><h2 className="mt-4 text-xl font-black text-[var(--public-text)]">{t('Official resources')}</h2><p className="mt-2 text-sm text-[var(--public-dark-faint)]">{t('Downloads will be published here by the secretariat when official documents are approved.')}</p></div>;
    return <div className="rounded-2xl border border-[var(--public-dark-border)] bg-white p-8 shadow-sm"><Newspaper className="size-8 text-[var(--public-primary)]" /><h2 className="mt-4 text-xl font-black text-[var(--public-text)]">{t('No announcements yet')}</h2><p className="mt-2 text-sm text-[var(--public-dark-faint)]">{t('Official announcements will appear here when published by the secretariat.')}</p></div>;
}