import PublicLayout from '@/Layouts/PublicLayout';
import PublicPageHero from '@/components/PublicPageHero';
import { useI18n } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    ExternalLink,
    Mail,
    MapPin,
    MessageCircle,
    Phone,
    Share2,
    Clock,
    Users,
    CalendarDays,
    HelpCircle,
    ChevronRight,
    Trophy,
    Swords,
} from 'lucide-react';

type Props = {
    app_name: string;
    contact: {
        address: string | null;
        email: string | null;
        phone: string | null;
        social: {
            facebook: string | null;
            instagram: string | null;
            tiktok: string | null;
            youtube: string | null;
        };
    };
};

const quickLinks = [
    { label: 'Competition Schedule', href: '/schedule', icon: CalendarDays },
    { label: 'Latest Results', href: '/results', icon: Trophy },
    { label: 'Official Matches', href: '/matches', icon: Swords },
    { label: 'Participating Faculties', href: '/faculties', icon: Users },
];

const faqs = [
    {
        question: 'Where can I find the latest schedule?',
        answer: 'Please refer to the official Schedule section or contact the secretariat for the latest confirmed information.',
    },
    {
        question: 'Where are official results published?',
        answer: 'Official results are published on the Results page as soon as they are confirmed by the competition officials.',
    },
    {
        question: 'How can I contact the secretariat?',
        answer: 'You can reach the secretariat via email, phone, or by visiting the UTeM Sports Centre during office hours.',
    },
];

export default function PublicContact({ app_name, contact }: Props) {
    const { t } = useI18n();
    const phoneHref = contact.phone ? `tel:${contact.phone.replace(/[^\d+]/g, '')}` : null;
    const socialLinks: { label: string; href: string; icon: typeof ExternalLink }[] = [
        { label: 'Facebook', href: contact.social.facebook, icon: ExternalLink },
        { label: 'Instagram', href: contact.social.instagram, icon: ExternalLink },
        { label: 'TikTok', href: contact.social.tiktok, icon: ExternalLink },
        { label: 'YouTube', href: contact.social.youtube, icon: ExternalLink },
    ].filter(link => link.href);

    return (
        <PublicLayout title={t('Contact Us')} appName={app_name} current="contact">
            <Head><link rel="canonical" href={route('public.contact')} /></Head>
            <main aria-label={t('Contact Us')}>
                <PublicPageHero
                    eyebrow={t('Official sports information portal')}
                    title={t('Contact Us')}
                    intro={t('For competition, schedule and participation enquiries, please contact the secretariat through UTeM Sports Centre.')}
                    icon={<MessageCircle className="size-4" />}
                >
                    <Link href={route('public.index')} className="mt-8 inline-flex min-h-11 items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-4 text-sm font-bold text-white/75 backdrop-blur transition hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--public-highlight)]">
                        <ArrowLeft aria-hidden="true" className="size-4" /> {t('Back to home')}
                    </Link>
                </PublicPageHero>

                <section className="bg-[var(--public-background)] px-4 py-16 sm:px-6 lg:py-24">
                    <div className="mx-auto max-w-7xl">
                        <div className="grid gap-6 lg:grid-cols-[1.2fr_.8fr]">
                            <div className="rounded-[2rem] border border-[var(--public-dark-border)] bg-white p-7 shadow-[0_24px_70px_-48px_rgba(7,27,51,.9)] sm:p-10">
                                <div className="flex size-14 items-center justify-center rounded-2xl bg-[var(--public-primary-soft)] text-[var(--public-primary)]">
                                    <MessageCircle aria-hidden="true" className="size-7" />
                                </div>
                                <h2 className="mt-7 text-3xl font-black text-[var(--public-text)]">{t('SAF UTeM Secretariat')}</h2>
                                <p className="mt-4 max-w-xl leading-7 text-[var(--public-dark-faint)]">{t('Official channels for competition, schedule and contingent management enquiries.')}</p>

                                <div className="mt-8 grid gap-4 sm:grid-cols-2">
                                    <div className="group rounded-2xl border border-[var(--public-dark-border)] bg-[var(--public-background)] p-5 transition hover:border-[var(--public-primary-border)] hover:shadow-md">
                                        <div className="flex size-10 items-center justify-center rounded-xl bg-[var(--public-primary-soft)] text-[var(--public-primary)] transition group-hover:bg-[var(--public-primary)] group-hover:text-white">
                                            <Mail aria-hidden="true" className="size-5" />
                                        </div>
                                        <p className="mt-3 text-xs font-black uppercase tracking-wider text-[var(--public-dark-faint)]">{t('Email')}</p>
                                        {contact.email
                                            ? <a href={`mailto:${contact.email}`} className="mt-1 block break-all font-bold text-[var(--public-text)] underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--public-primary)]">{contact.email}</a>
                                            : <p className="mt-1 font-bold text-[var(--public-text)]">{t('Contact the secretariat')}</p>}
                                    </div>
                                    <div className="group rounded-2xl border border-[var(--public-dark-border)] bg-[var(--public-background)] p-5 transition hover:border-[var(--public-primary-border)] hover:shadow-md">
                                        <div className="flex size-10 items-center justify-center rounded-xl bg-[var(--public-primary-soft)] text-[var(--public-primary)] transition group-hover:bg-[var(--public-primary)] group-hover:text-white">
                                            <Phone aria-hidden="true" className="size-5" />
                                        </div>
                                        <p className="mt-3 text-xs font-black uppercase tracking-wider text-[var(--public-dark-faint)]">{t('Phone')}</p>
                                        {contact.phone && phoneHref
                                            ? <a href={phoneHref} className="mt-1 block font-bold text-[var(--public-text)] underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--public-primary)]">{contact.phone}</a>
                                            : <p className="mt-1 font-bold text-[var(--public-text)]">{t('Contact the secretariat')}</p>}
                                    </div>
                                    <div className="group rounded-2xl border border-[var(--public-dark-border)] bg-[var(--public-background)] p-5 transition hover:border-[var(--public-primary-border)] hover:shadow-md sm:col-span-2">
                                        <div className="flex size-10 items-center justify-center rounded-xl bg-[var(--public-primary-soft)] text-[var(--public-primary)] transition group-hover:bg-[var(--public-primary)] group-hover:text-white">
                                            <MapPin aria-hidden="true" className="size-5" />
                                        </div>
                                        <p className="mt-3 text-xs font-black uppercase tracking-wider text-[var(--public-dark-faint)]">{t('Correspondence address')}</p>
                                        <p className="mt-1 whitespace-pre-line leading-6 font-bold text-[var(--public-text)]">{contact.address || t('The secretariat address will be updated.')}</p>
                                    </div>
                                </div>
                            </div>

                            <aside className="flex flex-col gap-6">
                                <div className="relative overflow-hidden rounded-[2rem] bg-[var(--public-dark)] p-7 text-white shadow-xl sm:p-10">
                                    <div aria-hidden="true" className="absolute -right-20 -top-20 size-56 rounded-full bg-[var(--public-accent-soft)] blur-3xl" />
                                    <p className="relative text-xs font-black uppercase tracking-[.2em] text-[var(--public-highlight)]">{t('UTeM Sports Centre')}</p>
                                    <h2 className="mt-5 text-3xl font-black">{t('Universiti Teknikal Malaysia Melaka')}</h2>
                                    <p className="mt-4 leading-7 text-white/60">{t('Visit the official UTeM website for institutional information and contact channels.')}</p>
                                    <a href="https://www.utem.edu.my/" target="_blank" rel="noreferrer" className="public-cosmic-bezel mt-8 inline-flex min-h-12 items-center gap-2 rounded-xl bg-[var(--public-highlight)] px-5 py-3 text-sm font-black text-[var(--public-dark)] transition hover:-translate-y-0.5 hover:brightness-105 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">{t('Visit UTeM website')} <ExternalLink aria-hidden="true" className="size-4" /></a>
                                    {socialLinks.length > 0 && (
                                        <div className="relative mt-8 border-t border-white/10 pt-7">
                                            <p className="flex items-center gap-2 text-sm font-bold text-white"><Share2 aria-hidden="true" className="size-4 text-[var(--public-highlight)]" />{t('Official social media')}</p>
                                            <div className="mt-4 flex flex-wrap gap-2">
                                                {socialLinks.map((link) => (
                                                    <a key={link.label} href={link.href} target="_blank" rel="noreferrer" className="inline-flex min-h-11 items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-4 py-2 text-sm font-bold text-white transition hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--public-highlight)]">
                                                        {link.label}<ExternalLink aria-hidden="true" className="size-3.5" />
                                                    </a>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>

                                <div className="rounded-[2rem] border border-[var(--public-dark-border)] bg-white p-7 shadow-[0_24px_70px_-48px_rgba(7,27,51,.9)] sm:p-10">
                                    <div className="flex size-12 items-center justify-center rounded-2xl bg-[var(--public-primary-soft)] text-[var(--public-primary)]">
                                        <Clock aria-hidden="true" className="size-6" />
                                    </div>
                                    <h3 className="mt-5 text-lg font-black text-[var(--public-text)]">{t('Office Hours')}</h3>
                                    <p className="mt-2 text-sm text-[var(--public-dark-faint)]">{t('Monday - Friday')}</p>
                                    <p className="text-sm font-bold text-[var(--public-text)]">8:00 AM - 5:00 PM</p>
                                    <p className="mt-2 text-sm text-[var(--public-dark-faint)]">{t('Closed on public holidays')}</p>
                                </div>
                            </aside>
                        </div>

                        <div className="mt-16">
                            <div className="mb-8">
                                <h2 className="text-3xl font-black tracking-[-.04em] text-[var(--public-text)] sm:text-5xl">{t('Quick Links')}</h2>
                                <p className="mt-4 text-sm text-[var(--public-dark-faint)]">{t('Navigate to the most used sections of the official portal.')}</p>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                {quickLinks.map((link) => {
                                    const Icon = link.icon;
                                    return (
                                        <Link key={link.label} href={link.href} className="group flex items-center justify-between rounded-2xl border border-[var(--public-dark-border)] bg-white p-5 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-[var(--public-primary-border)] hover:shadow-[0_24px_70px_-48px_rgba(7,27,51,.9)]">
                                            <div className="flex items-center gap-3">
                                                <span className="flex size-10 items-center justify-center rounded-xl bg-[var(--public-primary-soft)] text-[var(--public-primary)] transition group-hover:bg-[var(--public-primary)] group-hover:text-white"><Icon className="size-5" /></span>
                                                <span className="text-sm font-black text-[var(--public-text)]">{link.label}</span>
                                            </div>
                                            <ChevronRight className="size-4 text-slate-400 transition group-hover:text-[var(--public-primary)]" />
                                        </Link>
                                    );
                                })}
                            </div>
                        </div>

                        <div className="mt-16">
                            <div className="mb-8 flex items-center gap-3">
                                <div className="flex size-10 items-center justify-center rounded-xl bg-[var(--public-primary-soft)] text-[var(--public-primary)]">
                                    <HelpCircle className="size-5" />
                                </div>
                                <div>
                                    <h2 className="text-3xl font-black tracking-[-.04em] text-[var(--public-text)] sm:text-5xl">{t('Frequently Asked Questions')}</h2>
                                </div>
                            </div>
                            <div className="grid gap-4 lg:grid-cols-3">
                                {faqs.map((faq) => (
                                    <div key={faq.question} className="rounded-2xl border border-[var(--public-dark-border)] bg-white p-6 shadow-sm transition hover:border-[var(--public-primary-border)] hover:shadow-md">
                                        <h3 className="text-sm font-black text-[var(--public-text)]">{t(faq.question)}</h3>
                                        <p className="mt-3 text-sm leading-6 text-[var(--public-dark-faint)]">{t(faq.answer)}</p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </PublicLayout>
    );
}
