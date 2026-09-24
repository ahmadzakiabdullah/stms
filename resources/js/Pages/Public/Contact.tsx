import PublicLayout from '@/Layouts/PublicLayout';
import PublicErrorState from '@/components/PublicErrorState';
import PublicPageHero from '@/components/PublicPageHero';
import PublicStaleDataNotice from '@/components/PublicStaleDataNotice';
import { useI18n } from '@/lib/i18n';
import { router } from '@inertiajs/react';
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
    updated_at?: string;
    error?: string | null;
};

type SecretariatGame = {
    bil: string;
    event: string;
    staffName: string;
    staffPhone?: string;
    chairperson: string;
    chairRole: string;
    chairPhone: string;
};

const secretariatGames: SecretariatGame[] = [
    { bil: '1', event: 'Catur Campuran', staffName: 'En. Ahmad Rhafee bin Samsudin', staffPhone: '012 – 2646902', chairperson: 'Muhammad Zamzamin Bin Zamzuri', chairRole: 'Pengerusi Kelab Catur', chairPhone: '013-9367803' },
    { bil: '2', event: 'Tenis Campuran', staffName: 'Pn. Norasikin binti Md Isa', staffPhone: '017-3068061', chairperson: 'Ahmad Rayyan Bin Nor Anuar', chairRole: 'Pengerusi Kelab Tenis', chairPhone: '016-2218914' },
    { bil: '3', event: 'Hoki 9 Sebelah (L&W)', staffName: '—', chairperson: 'Nabil Ilham Bin Abdul Jamal', chairRole: 'Pengerusi Kelab Hoki', chairPhone: '013-2244845' },
    { bil: '4', event: 'E-Sport Terbuka (Mobile Legends)', staffName: 'En. Ahmad Affenday bin Mohamed Sani', staffPhone: '012-6376317', chairperson: 'Nurul Alieya Maisara Nor Hazlan', chairRole: 'Pengerusi Kelab Esport', chairPhone: '011-39121149' },
    { bil: '5', event: 'E-Sport Terbuka Valorant', staffName: '—', chairperson: 'Nurul Alieya Maisara Nor Hazlan', chairRole: 'Pengerusi Kelab Esport', chairPhone: '011-39121149' },
    { bil: '6', event: 'Bola Tampar (L&W)', staffName: '—', chairperson: 'Afnan Muslim Bin Ab Rahman', chairRole: 'Pengerusi Kelab Bola Tampar', chairPhone: '010-3576537' },
    { bil: '7', event: 'Sepak Takraw Berpasukan', staffName: '—', chairperson: 'Aliff Hakimi Bin Syamsul Ariffin', chairRole: 'Pengerusi Kelab Takraw', chairPhone: '011-40563167' },
    { bil: '8', event: 'Ragbi 10 Sebelah', staffName: 'En. Razali bin Yaakob', staffPhone: '019-6515423', chairperson: 'Farhad Bin Mohd Fairuz', chairRole: 'Pengerusi Kelab Ragbi', chairPhone: '011-15381901' },
    { bil: '9', event: 'Futsal (L&W)', staffName: '—', chairperson: 'Muhammad Iqbal Daniel Bin Halim', chairRole: 'Pengerusi Kelab Futsal', chairPhone: '017-9420611' },
    { bil: '10', event: 'Basikal (L&W)', staffName: '—', chairperson: 'Muhammad Safwan Bin Mohd Lazim', chairRole: 'Pengerusi Kelab Berbasikal', chairPhone: '01133381938' },
    { bil: '11', event: 'Petanque Campuran', staffName: 'En. Mohd Hadzren Redza bin Norhidzam Amin Akbar', staffPhone: '019-6067801', chairperson: 'Siti Nur Farhana Dania Binti Mohd Najib', chairRole: 'Pengerusi Kelab Petanque', chairPhone: '017-3176700' },
    { bil: '12', event: 'Memanah Campuran', staffName: '—', chairperson: 'Muhammad Haziq Danial Bin Hasan', chairRole: 'Pengerusi Kelab Memanah', chairPhone: '011-23358129' },
    { bil: '13', event: 'Indoor Rowing (L&W)', staffName: 'Pn. Norashikin binti Hashim', staffPhone: '017-3499118', chairperson: 'Ahmad Hulaif Bin Ramzi', chairRole: 'Pengerusi Kelab Sukan Rowing', chairPhone: '012-8162160' },
    { bil: '14', event: 'Kayak Campuran', staffName: '—', chairperson: 'Mohammad Hikmal Bin Suwadi', chairRole: 'Pengerusi Kelab Kayak', chairPhone: '014-5630821' },
    { bil: '15', event: 'Ping Pong Campuran', staffName: 'En. Mohd Yusri bin Misron', staffPhone: '019 – 6846772', chairperson: 'Burhanuddin Al-Hilmi Bin Mohd Salleh', chairRole: 'Pengerusi Kelab Sukan Ping Pong', chairPhone: '011-55039867' },
    { bil: '16', event: 'Lawn Bowls', staffName: '—', chairperson: 'Nur Najwa Natasya binti Mohd Zaiham', chairRole: 'Pengerusi Kelab Lawn Bowls', chairPhone: '011-61606078' },
    { bil: '17', event: 'Bola Sepak', staffName: '—', chairperson: 'Wan Amirul Ariff bin Wan Mohd Nordin', chairRole: 'Pengerusi Kelab Bola Sepak', chairPhone: '012-3122619' },
    { bil: '18', event: 'Tenpin Bowling Campuran', staffName: 'Pn. Zuraidah binti Abdullah', staffPhone: '019-6207058', chairperson: 'Ilhan Hezly bin Harriman', chairRole: 'Pengerusi Kelab Tenpin Bowling', chairPhone: '012-4144653' },
    { bil: '19', event: 'Bola Baling (L&W)', staffName: '—', chairperson: 'Muhammad Arman bin Houd', chairRole: 'Pengerusi Kelab Bola Baling', chairPhone: '018-2941270' },
    { bil: '20', event: 'Bola Jaring', staffName: '—', chairperson: 'Nur Zulaikha binti Mohd Afendi', chairRole: 'Pengerusi Kelab Bola Jaring', chairPhone: '013-5854220' },
    { bil: '21', event: 'Bola Keranjang (L&W)', staffName: 'En. Muhammad Ashraff bin Nor Rizan', staffPhone: '012-6423165', chairperson: 'Soo Teng Xiang', chairRole: 'Pengerusi Kelab Bola Keranjang', chairPhone: '011-20686147' },
    { bil: '22', event: 'Badminton Campuran', staffName: '—', chairperson: 'Muhammad Haziq Irfan bin Muhammad Hisham', chairRole: 'Pengerusi Kelab Badminton', chairPhone: '013-4430625' },
    { bil: '23', event: 'Sofbol (L)', staffName: '—', chairperson: 'Muhammad Rifqi Danish bin Mohd Shafiee', chairRole: 'Pengerusi Kelab Sofbol', chairPhone: '018-3552545' },
];

export default function PublicContact({ app_name, contact, updated_at, error = null }: Props) {
    const { t, locale } = useI18n();
    const phoneHref = contact.phone ? `tel:${contact.phone.replace(/[^\d+]/g, '')}` : null;
    const socialLinks = [
        { label: 'Facebook', href: contact.social.facebook },
        { label: 'Instagram', href: contact.social.instagram },
        { label: 'TikTok', href: contact.social.tiktok },
        { label: 'YouTube', href: contact.social.youtube },
    ].filter(link => link.href);

    return (
        <PublicLayout title={t('Contact Us')} appName={app_name} current="contact" description={t('Contact the official sports competition secretariat for schedules, participation and venue enquiries.')} canonical={route('public.contact')}>
            <main aria-label={t('Contact Us')}>
                {error && <div className="mx-auto max-w-7xl px-4 pt-8 sm:px-6"><PublicErrorState title={t('Contact information unavailable')} description={error} onRetry={() => router.reload()} /></div>}
                <PublicPageHero
                    eyebrow={t('Official sports information portal')}
                    title={t('Contact Us')}
                    intro={t('For competition, schedule and participation enquiries, please contact the secretariat through UTeM Sports Centre.')}
                    icon={<MessageCircle className="size-4" />}
                />
                <div className="mx-auto flex max-w-5xl justify-end px-4 pt-8 sm:px-6"><PublicStaleDataNotice updatedAt={updated_at} /></div>

                <section className="mx-auto max-w-5xl px-4 py-16 sm:px-6 lg:py-20">
                    <div className="grid gap-6 lg:grid-cols-[1.2fr_.8fr]">
                        <div className="public-card p-7 sm:p-10">
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
                                <a href="https://www.utem.edu.my/" target="_blank" rel="noopener noreferrer" className="relative mt-6 inline-flex min-h-11 items-center gap-2 rounded-xl bg-[var(--public-highlight)] px-4 text-sm font-black text-[var(--public-dark)] transition hover:-translate-y-0.5 hover:brightness-105">{t('Visit UTeM website')}<ExternalLink className="size-4" /></a>
                                {socialLinks.length > 0 && (
                                    <div className="relative mt-7 border-t border-white/10 pt-6">
                                        <p className="flex items-center gap-2 text-[11px] font-black uppercase tracking-wider text-white/75"><Share2 className="size-3.5 text-[var(--public-highlight)]" />{t('Official social media')}</p>
                                        <div className="mt-3 flex flex-wrap gap-2">
                                            {socialLinks.map(link => (
                                                <a key={link.label} href={link.href} target="_blank" rel="noopener noreferrer" className="inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-white/15 bg-white/5 px-3 text-xs font-bold text-white transition hover:bg-white/10">
                                                    {link.label}<ExternalLink className="size-3" />
                                                </a>
                                            ))}
                                        </div>
                                    </div>
                                )}
                            </div>

                            <div className="public-card p-7 sm:p-8">
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

                <section aria-labelledby="secretariat-games" className="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:pb-20">
                    <div className="mb-6">
                        <p className="text-[10px] font-black uppercase tracking-[.22em] text-[var(--public-primary)]">{t('Secretariat')}</p>
                        <h2 id="secretariat-games" className="mt-2 text-2xl font-black tracking-[-.02em] text-[var(--public-text)]">{t('Coordinators & Game Chairpersons')}</h2>
                    </div>
                    <div className="public-card overflow-x-auto">
                        <table className="w-full min-w-[900px] border-collapse text-left text-sm">
                            <caption className="sr-only">{t('List of event coordinators and sports event chairpersons')}</caption>
                            <thead className="bg-[var(--public-dark)] text-white">
                                <tr>
                                    <th scope="col" className="w-16 px-4 py-4 text-center font-black">{t('No.')}</th>
                                    <th scope="col" className="w-56 px-4 py-4 font-black">{t('Event')}</th>
                                    <th scope="col" className="w-72 px-4 py-4 font-black">{t('Staff Event Coordinator')}</th>
                                    <th scope="col" className="px-4 py-4 font-black">{t('Sports Event Chairperson')}</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-[var(--public-dark-border)] text-[var(--public-dark-faint)]">
                                {secretariatGames.map(game => <tr key={game.bil} className="align-top even:bg-[var(--public-background)]">
                                    <td className="px-4 py-5 text-center font-black text-[var(--public-primary)]">{game.bil}</td>
                                    <td className="px-4 py-5 font-semibold leading-6 text-[var(--public-text)]">{game.event}</td>
                                    <td className="px-4 py-5 leading-6">
                                        <p className="font-semibold text-[var(--public-text)]">{localizeSecretariatName(game.staffName, locale)}</p>
                                        {game.staffPhone && <a href={`tel:${game.staffPhone.replace(/[^\d+]/g, '')}`} className="font-medium underline decoration-[var(--public-primary)] underline-offset-2">{game.staffPhone}</a>}
                                    </td>
                                    <td className="px-4 py-5 leading-6">
                                        <p className="font-semibold text-[var(--public-text)]">{game.chairperson}</p>
                                        <p>{localizeSecretariatRole(game.chairRole, locale)}</p>
                                        <a href={`tel:${game.chairPhone.replace(/[^\d+]/g, '')}`} className="font-medium underline decoration-[var(--public-primary)] underline-offset-2">{game.chairPhone}</a>
                                    </td>
                                </tr>)}
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </PublicLayout>
    );
}

function localizeSecretariatRole(value: string, locale: string): string {
    if (locale === 'ms') return value;
    if (value.startsWith('Pengerusi Kelab Sukan ')) return `President of the ${value.replace('Pengerusi Kelab Sukan ', '')} Sports Club`;
    if (value.startsWith('Pengerusi Kelab ')) return `President of the ${value.replace('Pengerusi Kelab ', '')} Club`;

    return value;
}

function localizeSecretariatName(value: string, locale: string): string {
    if (locale === 'ms') return value;

    return value.replace('En. ', 'Mr. ').replace('Pn. ', 'Ms. ');
}
