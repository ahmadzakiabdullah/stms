import PublicLayout from '@/Layouts/PublicLayout';
import PublicPageHero from '@/components/PublicPageHero';
import { useI18n } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ExternalLink, Mail, MapPin, MessageCircle, Phone, Share2 } from 'lucide-react';

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

export default function PublicContact({ app_name, contact }: Props) {
    const { t } = useI18n();
    const phoneHref = contact.phone ? `tel:${contact.phone.replace(/[^\d+]/g, '')}` : null;
    const socialLinks: { label: string; href: string }[] = [
        { label: 'Facebook', href: contact.social.facebook },
        { label: 'Instagram', href: contact.social.instagram },
        { label: 'TikTok', href: contact.social.tiktok },
        { label: 'YouTube', href: contact.social.youtube },
    ].flatMap((link) => link.href ? [{ label: link.label, href: link.href }] : []);

    return (
        <PublicLayout title={t('Contact Us')} appName={app_name} current="contact">
            <Head><link rel="canonical" href={route('public.contact')} /></Head>
            <main aria-label={t('Contact Us')}>
                <PublicPageHero eyebrow={t('Official sports information portal')} title={t('Contact Us')} intro={t('For competition, schedule and participation enquiries, please contact the secretariat through UTeM Sports Centre.')} icon={<MessageCircle className="size-4" />}>
                    <Link href={route('public.index')} className="mt-8 inline-flex min-h-11 items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-4 text-sm font-bold text-white/75 backdrop-blur transition hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--public-highlight)]">
                        <ArrowLeft aria-hidden="true" className="size-4" /> {t('Back to home')}
                    </Link>
                </PublicPageHero>

                <section className="bg-[var(--public-background)] px-4 py-16 sm:px-6 lg:py-24">
                    <div className="mx-auto grid max-w-7xl gap-6 lg:grid-cols-[1.15fr_.85fr]">
                        <div className="rounded-[2rem] border border-[var(--public-dark-border)] bg-white p-7 shadow-[0_24px_70px_-48px_rgba(7,27,51,.9)] sm:p-10">
                            <div className="flex size-14 items-center justify-center rounded-2xl bg-[var(--public-primary-soft)] text-[var(--public-primary)]"><MessageCircle aria-hidden="true" className="size-7" /></div>
                            <h2 className="mt-7 text-3xl font-black text-[var(--public-text)]">{t('SAF UTeM Secretariat')}</h2>
                            <p className="mt-4 max-w-xl leading-7 text-[var(--public-dark-faint)]">{t('Official channels for competition, schedule and contingent management enquiries.')}</p>
                            <div className="mt-8 grid gap-4 sm:grid-cols-2">
                                <div className="rounded-2xl border border-[var(--public-dark-border)] bg-[var(--public-background)] p-5">
                                    <Mail aria-hidden="true" className="size-5 text-[var(--public-primary)]" />
                                    <p className="mt-3 text-xs font-black uppercase tracking-wider text-[var(--public-dark-faint)]">{t('Email')}</p>
                                    {contact.email
                                        ? <a href={`mailto:${contact.email}`} className="mt-1 block break-all font-bold text-[var(--public-text)] underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--public-primary)]">{contact.email}</a>
                                        : <p className="mt-1 font-bold text-[var(--public-text)]">{t('Contact the secretariat')}</p>}
                                </div>
                                <div className="rounded-2xl border border-[var(--public-dark-border)] bg-[var(--public-background)] p-5">
                                    <Phone aria-hidden="true" className="size-5 text-[var(--public-primary)]" />
                                    <p className="mt-3 text-xs font-black uppercase tracking-wider text-[var(--public-dark-faint)]">{t('Phone')}</p>
                                    {contact.phone && phoneHref
                                        ? <a href={phoneHref} className="mt-1 block font-bold text-[var(--public-text)] underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--public-primary)]">{contact.phone}</a>
                                        : <p className="mt-1 font-bold text-[var(--public-text)]">{t('Contact the secretariat')}</p>}
                                </div>
                                <div className="rounded-2xl border border-[var(--public-dark-border)] bg-[var(--public-background)] p-5 sm:col-span-2">
                                    <MapPin aria-hidden="true" className="size-5 text-[var(--public-primary)]" />
                                    <p className="mt-3 text-xs font-black uppercase tracking-wider text-[var(--public-dark-faint)]">{t('Correspondence address')}</p>
                                    <p className="mt-1 whitespace-pre-line leading-6 font-bold text-[var(--public-text)]">{contact.address || t('The secretariat address will be updated.')}</p>
                                </div>
                            </div>
                        </div>
                        <aside className="relative overflow-hidden rounded-[2rem] bg-[var(--public-dark)] p-7 text-white shadow-xl sm:p-10">
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
                        </aside>
                    </div>
                </section>
            </main>
        </PublicLayout>
    );
}