import PublicLayout from '@/Layouts/PublicLayout';
import PublicPageHero from '@/components/PublicPageHero';
import PublicStaleDataNotice from '@/components/PublicStaleDataNotice';
import { useI18n } from '@/lib/i18n';
import { Trophy } from 'lucide-react';

type Props = {
    app_name: string;
    competition: { name: string; description: string | null; organization: string | null } | null;
    updated_at?: string;
};

const games = [
    { name: 'Sofbol (L)', chairperson: 'Presiden Kelab Softball UTeM', officials: 'Bilangan Teknikal dan Pengadil' },
    { name: 'Ragbi 10’s (L)', chairperson: 'Presiden Kelab Ragbi UTeM', officials: 'Bilangan Teknikal dan Pengadil' },
    { name: 'Bola Sepak (L)', chairperson: 'Presiden Kelab Bola Sepak UTeM', officials: 'Bilangan Teknikal dan Pengadil' },
    { name: 'Bola Keranjang (L & W)', chairperson: 'Presiden Kelab Bola Keranjang UTeM', officials: 'Bilangan Teknikal dan Pengadil' },
    { name: 'Sepak Takraw (L)', chairperson: 'Presiden Kelab Sepak Takraw UTeM', officials: 'Bilangan Teknikal dan Pengadil' },
    { name: 'Badminton Campuran', chairperson: 'Presiden Kelab Badminton UTeM', officials: 'Bilangan Teknikal dan Pengadil' },
    { name: 'Bola Tampar (L&W)', chairperson: 'Presiden Kelab Bola Tampar UTeM', officials: 'Bilangan Teknikal dan Pengadil' },
    { name: 'Hoki 9’s (L&W)', chairperson: 'Presiden Kelab Hoki UTeM', officials: 'Bilangan Teknikal dan Pengadil' },
    { name: 'Bola Baling (L&W)', chairperson: 'Presiden Kelab Bola Baling UTeM', officials: 'Bilangan Teknikal dan Pengadil' },
    { name: 'Catur Campuran', chairperson: 'Presiden Kelab Catur UTeM', officials: 'Bilangan Teknikal dan Pengadil' },
    { name: 'Futsal (L&W)', chairperson: 'Presiden Kelab Futsal UTeM', officials: 'Bilangan Teknikal dan Pengadil' },
    { name: 'Ping Pong Campuran', chairperson: 'Presiden Kelab Ping Pong UTeM', officials: 'Bilangan Teknikal dan Pengadil' },
    { name: 'Bola Jaring (W)', chairperson: 'Presiden Kelab Bola Jaring UTeM', officials: 'Bilangan Teknikal dan Pengadil' },
    { name: 'Tenis Campuran', chairperson: 'Presiden Kelab Tenis UTeM', officials: 'Bilangan Teknikal dan Pengadil' },
    { name: 'Petanque Campuran', chairperson: 'Presiden Kelab Petanque UTeM', officials: 'Bilangan Teknikal dan Pengadil' },
    { name: 'Memanah Campuran', chairperson: 'Presiden Kelab Memanah', officials: 'Bilangan Teknikal dan Pengadil' },
    { name: 'E-Sport Mobile Legend', chairperson: 'Presiden Kelab E-Sport', officials: 'Bilangan Teknikal dan Pengadil' },
    { name: 'E-Sport Valorant', chairperson: 'Presiden Kelab E-Sport', officials: 'Bilangan Teknikal dan Pengadil' },
    { name: 'Tenpin Boling Campuran', chairperson: 'Presiden Kelab Tenpin Boling', officials: 'Bilangan Teknikal dan Pengadil' },
    { name: 'Kayak Campuran', chairperson: 'Ketua Kayak Kelab Klaska', officials: 'Bilangan Teknikal & Pengadil' },
    { name: 'Basikal', chairperson: 'Presiden Kelab Basikal', officials: 'Bilangan Teknikal & Pengadil' },
    { name: 'Indoor Rowing', chairperson: 'Ketua Rowing Kelab Klaska', officials: 'Bilangan Teknikal & Pengadil' },
    { name: 'Lawn Bowls', chairperson: 'Presiden Kelab Lawn Bowls', officials: 'Bilangan Teknikal & Pengadil' },
];

export default function PublicGameChairpersons({ app_name, competition, updated_at }: Props) {
    const { t, locale } = useI18n();
    const isMalay = locale === 'ms';
    const title = isMalay ? 'PENGERUSI PERMAINAN' : 'GAME CHAIRPERSONS';

    return (
        <PublicLayout title={`${title} | ${competition?.name || app_name}`} appName={app_name} current="information" description={t('Game chairperson information for the faculty sports championship.')} canonical={route('public.game-chairpersons')}>
            <main>
                <PublicPageHero eyebrow={competition?.organization || t('Official competition')} title={title} intro={isMalay ? 'Pengerusi permainan serta keperluan teknikal dan pengadil bagi setiap acara.' : 'Game chairpersons and technical and referee requirements for each event.'} icon={<Trophy className="size-4" />} />
                <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16">
                    <div className="mb-6 flex justify-end"><PublicStaleDataNotice updatedAt={updated_at} /></div>
                    <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                        {games.map((game, index) => (
                            <article key={game.name} className="public-card p-6 sm:p-8">
                                <p className="relative z-10 text-sm font-black text-[var(--public-primary)]">{String(index + 1).padStart(2, '0')}</p>
                                <h2 className="relative z-10 mt-3 text-xl font-black leading-tight text-[var(--public-text)]">{game.name}</h2>
                                <div className="relative z-10 mt-5 space-y-3 border-t border-[var(--public-dark-border)] pt-4 text-sm leading-6 text-[var(--public-dark-faint)]">
                                    <p><span className="font-bold text-[var(--public-text)]">{isMalay ? 'Pengerusi:' : 'Chairperson:'}</span> {localizeGameChairperson(game.chairperson, locale)}</p>
                                    <p>{localizeGameOfficials(game.officials, locale)}</p>
                                </div>
                            </article>
                        ))}
                    </div>
                </div>
            </main>
        </PublicLayout>
    );
}

function localizeGameChairperson(value: string, locale: string): string {
    if (locale === 'ms') return value;
    if (value.startsWith('Presiden Kelab ')) return `President of the ${value.replace('Presiden Kelab ', '')} Club`;
    if (value.startsWith('Ketua ')) return `Head of the ${value.replace('Ketua ', '')}`;

    return value;
}

function localizeGameOfficials(value: string, locale: string): string {
    if (locale === 'ms') return value;

    return value
        .replace('Bilangan Teknikal dan Pengadil', 'Technical and Referee Personnel')
        .replace('Bilangan Teknikal & Pengadil', 'Technical & Referee Personnel');
}
