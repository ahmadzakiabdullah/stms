import PublicLayout from '@/Layouts/PublicLayout';
import PublicPageHero from '@/components/PublicPageHero';
import { useI18n } from '@/lib/i18n';
import { Head } from '@inertiajs/react';
import { Clock, ExternalLink, Mail, MapPin, MessageCircle, Phone, Share2 } from 'lucide-react';

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
    const socialLinks = [
        { label: 'Facebook', href: contact.social.facebook },
        { label: 'Instagram', href: contact.social.instagram },
        { label: 'TikTok', href: contact.social.tiktok },
        { label: 'YouTube', href: contact.social.youtube },
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
                />

                <section className="mx-auto max-w-5xl px-4 py-16 sm:px-6 lg:py-20">
                    <div className="grid gap-6 lg:grid-cols-[1.2fr_.8fr]">
                        <div className="rounded-[2rem] border border-[var(--public-dark-border)] bg-white p-7 shadow-[0_24px_70px_-48px_rgba(7,27,51,.9)] sm:p-10">
                            <p className="text-[10px] font-black uppercase tracking-[.22em] text-[var(--public-primary)]">{t('Secretariat')}</p>
                            <h2 className="mt-2 text-2xl font-black tracking-[-.02em] text-[var(--public-text)]">{t('SAF UTeM Secretariat')}</h2>
                            <p className="mt-3 max-w-md text-sm leading-6 text-[var(--public-dark-faint)]">{t('Official channels for competition, schedule and contingent management enquiries.')}</p>

                            <div className="mt-8 space-y-4">
                                {contact.email && (
                                    <a href={`mailto:${contact.email}`} className="group flex items-center gap-4 rounded-2xl border border-[var(--public-dark-border)] bg-[var(--public-background)] p-4 transition hover:border-[var(--public-primary-border)] hover:shadow-md">
                                        <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-[var(--public-primary-soft)] text-[var(--public-primary)] transition group-hover:bg-[var(--public-primary)] group-hover:text-white"><Mail className="size-5" /></span>
                                        <span className="min-w-0">
                                            <span className="block text-[11px] font-black uppercase tracking-wider text-[var(--public-dark-faint)]">{t('Email')}</span>
                                            <span className="block truncate text-sm font-bold text-[var(--public-text)]">{contact.email}</span>
                                        </span>
                                    </a>
                                )}
                                {contact.phone && phoneHref && (
                                    <a href={phoneHref} className="group flex items-center gap-4 rounded-2xl border border-[var(--public-dark-border)] bg-[var(--public-background)] p-4 transition hover:border-[var(--public-primary-border)] hover:shadow-md">
                                        <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-[var(--public-primary-soft)] text-[var(--public-primary)] transition group-hover:bg-[var(--public-primary)] group-hover:text-white"><Phone className="size-5" /></span>
                                        <span className="min-w-0">
                                            <span className="block text-[11px] font-black uppercase tracking-wider text-[var(--public-dark-faint)]">{t('Phone')}</span>
                                            <span className="block text-sm font-bold text-[var(--public-text)]">{contact.phone}</span>
                                        </span>
                                    </a>
                                )}
                                {contact.address && (
                                    <div className="flex items-start gap-4 rounded-2xl border border-[var(--public-dark-border)] bg-[var(--public-background)] p-4">
                                        <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-[var(--public-primary-soft)] text-[var(--public-primary)]"><MapPin className="size-5" /></span>
                                        <span className="min-w-0">
                                            <span className="block text-[11px] font-black uppercase tracking-wider text-[var(--public-dark-faint)]">{t('Address')}</span>
                                            <span className="block whitespace-pre-line text-sm font-bold leading-6 text-[var(--public-text)]">{contact.address}</span>
                                        </span>
                                    </div>
                                )}
                                {!contact.email && !contact.phone && !contact.address && (
                                    <p className="rounded-2xl border border-dashed border-[var(--public-dark-border)] px-5 py-6 text-center text-sm font-semibold text-[var(--public-dark-faint)]">{t('Contact details will be updated by the secretariat.')}</p>
                                )}
                            </div>
                        </div>

                        <aside className="flex flex-col gap-6">
                            <div className="relative overflow-hidden rounded-[2rem] bg-[var(--public-dark)] p-7 text-white shadow-xl sm:p-8">
                                <div aria-hidden="true" className="absolute -right-16 -top-16 size-48 rounded-full bg-[var(--public-accent-soft)] blur-3xl" />
                                <p className="relative text-[10px] font-black uppercase tracking-[.22em] text-[var(--public-highlight)]">{t('UTeM Sports Centre')}</p>
                                <h2 className="relative mt-3 text-xl font-black leading-snug">{t('Universiti Teknikal Malaysia Melaka')}</h2>
                                <a href="https://www.utem.edu.my/" target="_blank" rel="noreferrer" className="relative mt-6 inline-flex min-h-11 items-center gap-2 rounded-xl bg-[var(--public-highlight)] px-4 text-sm font-black text-[var(--public-dark)] transition hover:-translate-y-0.5 hover:brightness-105">{t('Visit UTeM website')}<ExternalLink className="size-4" /></a>
                                {socialLinks.length > 0 && (
                                    <div className="relative mt-7 border-t border-white/10 pt-6">
                                        <p className="flex items-center gap-2 text-[11px] font-black uppercase tracking-wider text-white/75"><Share2 className="size-3.5 text-[var(--public-highlight)]" />{t('Official social media')}</p>
                                        <div className="mt-3 flex flex-wrap gap-2">
                                            {socialLinks.map(link => (
                                                <a key={link.label} href={link.href} target="_blank" rel="noreferrer" className="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-white/15 bg-white/5 px-3 text-xs font-bold text-white transition hover:bg-white/10">
                                                    {link.label}<ExternalLink className="size-3" />
                                                </a>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </div>

                            <div className="rounded-[2rem] border border-[var(--public-dark-border)] bg-white p-7 shadow-[0_24px_70px_-48px_rgba(7,27,51,.9)] sm:p-8">
                                <div className="flex items-center gap-3">
                                    <span className="flex size-10 items-center justify-center rounded-xl bg-[var(--public-primary-soft)] text-[var(--public-primary)]"><Clock className="size-5" /></span>
                                    <h3 className="text-base font-black text-[var(--public-text)]">{t('Office Hours')}</h3>
                                </div>
                                <dl className="mt-4 space-y-2 text-sm">
                                    <div className="flex items-center justify-between gap-4"><dt className="text-[var(--public-dark-faint)]">{t('Monday - Friday')}</dt><dd className="font-bold tabular-nums text-[var(--public-text)]">8:00 AM - 5:00 PM</dd></div>
                                    <div className="flex items-center justify-between gap-4"><dt className="text-[var(--public-dark-faint)]">{t('Saturday & Sunday')}</dt><dd className="font-bold text-[var(--public-text)]">{t('Closed')}</dd></div>
                                </dl>
                                <p className="mt-4 border-t border-[var(--public-dark-border)] pt-3 text-xs text-[var(--public-dark-faint)]">{t('Closed on public holidays')}</p>
                            </div>
                        </aside>
                    </div>
                </section>
            </main>
        </PublicLayout>
    );
}
