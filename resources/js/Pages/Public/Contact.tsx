import PublicLayout from '@/Layouts/PublicLayout';
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

    return <PublicLayout title={t('Contact Us')} appName={app_name} withCanonical>
        <Head title={t('Contact Us')} />
        <main aria-label={t('Contact Us')}>
            <section className="relative isolate overflow-hidden bg-[#10251f] text-white">
                <div aria-hidden="true" className="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_78%_18%,rgba(84,214,178,.25),transparent_30%),radial-gradient(circle_at_12%_80%,rgba(215,239,89,.12),transparent_24%),linear-gradient(135deg,#10251f_0%,#123d33_55%,#0c211c_100%)]" />
                <div aria-hidden="true" className="public-cosmic-grid absolute inset-0 -z-10 opacity-25" />
                <div aria-hidden="true" className="public-cosmic-orbit absolute -right-28 top-24 -z-10 size-[28rem] rounded-full border border-white/10" />
                <div className="mx-auto max-w-7xl px-4 pb-16 pt-44 sm:px-6 sm:pt-48 lg:pb-20">
                    <Link href={route('public.index')} className="mb-8 inline-flex min-h-11 items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-4 text-sm font-bold text-white/75 backdrop-blur transition hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#d7ef59]">
                        <ArrowLeft aria-hidden="true" className="size-4" /> {t('Back to home')}
                    </Link>
                    <p className="text-xs font-black uppercase tracking-[.28em] text-[#d7ef59]">{t('Official sports information portal')}</p>
                    <h1 className="mt-4 max-w-3xl text-5xl font-black leading-[.94] tracking-[-.055em] sm:text-7xl">{t('Contact Us')}</h1>
                    <p className="mt-6 max-w-2xl text-base leading-7 text-white/65 sm:text-lg">{t('For competition, schedule and participation enquiries, please contact the secretariat through UTeM Sports Centre.')}</p>
                </div>
            </section>

            <section className="bg-[#f6f7f2] px-4 py-16 sm:px-6 lg:py-24">
                <div className="mx-auto grid max-w-7xl gap-6 lg:grid-cols-[1.15fr_.85fr]">
                    <div className="rounded-[2rem] border border-[#10251f]/10 bg-white p-7 shadow-[0_24px_70px_-48px_rgba(16,37,31,.9)] sm:p-10">
                        <div className="flex size-14 items-center justify-center rounded-2xl bg-[#edf5d5] text-[#23745f]"><MessageCircle aria-hidden="true" className="size-7" /></div>
                        <h2 className="mt-7 text-3xl font-black text-[#10251f]">{t('SAF UTeM Secretariat')}</h2>
                        <p className="mt-4 max-w-xl leading-7 text-[#52655e]">{t('Official channels for competition, schedule and contingent management enquiries.')}</p>
                        <div className="mt-8 grid gap-4 sm:grid-cols-2">
                            <div className="rounded-2xl border border-[#10251f]/5 bg-[#f6f8f2] p-5">
                                <Mail aria-hidden="true" className="size-5 text-[#23745f]" />
                                <p className="mt-3 text-xs font-black uppercase tracking-wider text-[#52655e]">{t('Email')}</p>
                                {contact.email
                                    ? <a href={`mailto:${contact.email}`} className="mt-1 block break-all font-bold text-[#10251f] underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#23745f]">{contact.email}</a>
                                    : <p className="mt-1 font-bold text-[#10251f]">{t('Contact the secretariat')}</p>}
                            </div>
                            <div className="rounded-2xl border border-[#10251f]/5 bg-[#f6f8f2] p-5">
                                <Phone aria-hidden="true" className="size-5 text-[#23745f]" />
                                <p className="mt-3 text-xs font-black uppercase tracking-wider text-[#52655e]">{t('Phone')}</p>
                                {contact.phone && phoneHref
                                    ? <a href={phoneHref} className="mt-1 block font-bold text-[#10251f] underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#23745f]">{contact.phone}</a>
                                    : <p className="mt-1 font-bold text-[#10251f]">{t('Contact the secretariat')}</p>}
                            </div>
                            <div className="rounded-2xl border border-[#10251f]/5 bg-[#f6f8f2] p-5 sm:col-span-2">
                                <MapPin aria-hidden="true" className="size-5 text-[#23745f]" />
                                <p className="mt-3 text-xs font-black uppercase tracking-wider text-[#52655e]">{t('Correspondence address')}</p>
                                <p className="mt-1 whitespace-pre-line leading-6 font-bold text-[#10251f]">{contact.address || t('The secretariat address will be updated.')}</p>
                            </div>
                        </div>
                    </div>
                    <aside className="relative overflow-hidden rounded-[2rem] bg-[#10251f] p-7 text-white shadow-xl sm:p-10">
                        <div aria-hidden="true" className="absolute -right-20 -top-20 size-56 rounded-full bg-[#54d6b2]/15 blur-3xl" />
                        <p className="relative text-xs font-black uppercase tracking-[.2em] text-[#d7ef59]">{t('UTeM Sports Centre')}</p>
                        <h2 className="mt-5 text-3xl font-black">{t('Universiti Teknikal Malaysia Melaka')}</h2>
                        <p className="mt-4 leading-7 text-white/60">{t('Visit the official UTeM website for institutional information and contact channels.')}</p>
                        <a href="https://www.utem.edu.my/" target="_blank" rel="noreferrer" className="public-cosmic-bezel mt-8 inline-flex min-h-12 items-center gap-2 rounded-xl bg-[#d7ef59] px-5 py-3 text-sm font-black text-[#10251f] transition hover:-translate-y-0.5 hover:bg-[#e4f77d] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">{t('Visit UTeM website')} <ExternalLink aria-hidden="true" className="size-4" /></a>
                        {socialLinks.length > 0 && (
                            <div className="relative mt-8 border-t border-white/10 pt-7">
                                <p className="flex items-center gap-2 text-sm font-bold text-white"><Share2 aria-hidden="true" className="size-4 text-[#d7ef59]" />{t('Official social media')}</p>
                                <div className="mt-4 flex flex-wrap gap-2">
                                    {socialLinks.map((link) => (
                                        <a key={link.label} href={link.href} target="_blank" rel="noreferrer" className="inline-flex min-h-11 items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-4 py-2 text-sm font-bold text-white transition hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#d7ef59]">
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
    </PublicLayout>;
}
