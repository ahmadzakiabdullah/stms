import PublicLayout from '@/Layouts/PublicLayout';
import PublicPageHero from '@/components/PublicPageHero';
import PublicStaleDataNotice from '@/components/PublicStaleDataNotice';
import SafeImage from '@/components/SafeImage';
import { useI18n } from '@/lib/i18n';
import { SportIcon } from '@/lib/sportIcons';
import { Phone, Search, Trophy, X } from 'lucide-react';
import { secretariatGames } from './Contact';
import { useState } from 'react';

type Props = {
    app_name: string;
    competition: { name: string; description: string | null; organization: string | null } | null;
    sports_catalog: Array<{ name: string | null; icon: string | null }>;
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

export default function PublicGameChairpersons({ app_name, competition, sports_catalog, updated_at }: Props) {
    const { t, locale } = useI18n();
    const [query, setQuery] = useState('');
    const isMalay = locale === 'ms';
    const title = isMalay ? 'Pengerusi Permainan' : 'Game Chairpersons';
    const gamesBySport = secretariatGames.filter((game, index, allGames) => index === allGames.findIndex((candidate) => sportDisplayName(candidate.event) === sportDisplayName(game.event)));
    const normalizedQuery = query.trim().toLowerCase();
    const visibleGames = normalizedQuery
        ? gamesBySport.filter((game) => `${sportDisplayName(game.event)} ${game.chairperson} ${game.chairRole}`.toLowerCase().includes(normalizedQuery))
        : gamesBySport;

    return (
        <PublicLayout title={`${title} | ${competition?.name || app_name}`} appName={app_name} current="game-chairpersons" description={t('Game chairperson information for the faculty sports championship.')} canonical={route('public.game-chairpersons')}>
            <main>
                <PublicPageHero eyebrow={competition?.organization || t('Official competition')} title={title} intro={isMalay ? 'Pengerusi permainan serta keperluan teknikal dan pengadil bagi setiap acara.' : 'Game chairpersons and technical and referee requirements for each event.'} icon={<Trophy className="size-4" />} />
                <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16">
                    <div className="mb-6 flex justify-end"><PublicStaleDataNotice updatedAt={updated_at} /></div>
                    <div className="mb-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-sm font-semibold text-[var(--public-dark-faint)]">{visibleGames.length} {t('sports event chairpersons')}</p>
                        <div className="relative w-full sm:max-w-sm">
                            <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-[var(--public-dark-faint)]" />
                            <input value={query} onChange={(event) => setQuery(event.target.value)} placeholder={t('Search sport or chairperson...')} aria-label={t('Search sport or chairperson...')} className="h-11 w-full rounded-xl border border-[var(--public-dark-border)] bg-white pl-10 pr-10 text-sm font-semibold text-[var(--public-text)] outline-none transition focus:border-[var(--public-primary)] focus:ring-2 focus:ring-[var(--public-primary)]/20" />
                            {query && <button type="button" onClick={() => setQuery('')} aria-label={t('Clear search')} className="absolute right-2 top-1/2 flex size-8 -translate-y-1/2 items-center justify-center rounded-lg text-[var(--public-dark-faint)] hover:bg-[var(--public-primary-soft)] hover:text-[var(--public-primary)]"><X className="size-4" /></button>}
                        </div>
                    </div>
                    <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                        {visibleGames.map((game) => (
                            <article key={game.bil} className="public-card p-6 sm:p-8">
                                <div className="relative z-10 flex items-center justify-between gap-4">
                                    <span className="relative flex size-14 items-center justify-center rounded-2xl bg-[var(--public-primary-soft)]" aria-label={sportDisplayName(game.event)}>
                                        <SafeImage
                                            src={sportMascot(game.event, sports_catalog)}
                                            alt={`${sportDisplayName(game.event)} mascot`}
                                            loading="lazy"
                                            decoding="async"
                                            className="size-12 object-contain drop-shadow-md"
                                            fallback={<SportIcon name={sportIconName(game.event)} className="size-10 text-[var(--public-primary)]" />}
                                        />
                                    </span>
                                    <span className="text-xs font-black tracking-[.18em] text-[var(--public-primary)] tabular-nums">{game.bil.padStart(2, '0')}</span>
                                </div>
                                <h2 className="relative z-10 mt-5 text-xl font-black leading-tight text-[var(--public-text)]">{sportDisplayName(game.event)}</h2>
                                <div className="relative z-10 mt-5 space-y-3 border-t border-[var(--public-dark-border)] pt-4 text-sm leading-6 text-[var(--public-dark-faint)]">
                                    <p><span className="font-bold text-[var(--public-text)]">{isMalay ? 'Pengerusi:' : 'Chairperson:'}</span> {game.chairperson}</p>
                                    <p className="text-xs font-semibold uppercase tracking-wide text-[var(--public-dark-faint)]">{game.chairRole}</p>
                                    <p className="flex items-center gap-2"><Phone className="size-4 shrink-0 text-[var(--public-primary)]" /><a href={`tel:${game.chairPhone.replace(/[^\d+]/g, '')}`} className="font-semibold text-[var(--public-primary)] underline underline-offset-2">{game.chairPhone}</a></p>
                                </div>
                            </article>
                        ))}
                    </div>
                    {visibleGames.length === 0 && <p className="rounded-2xl border border-dashed border-[var(--public-dark-border)] px-5 py-10 text-center text-sm font-semibold text-[var(--public-dark-faint)]">{t('No chairpersons match your search.')}</p>}
                </div>
            </main>
        </PublicLayout>
    );
}

function sportIconName(event: string): string {
    const normalized = event.toLowerCase();

    if (normalized.includes('mobile legends')) return 'e-sport mobile legend';
    if (normalized.includes('valorant')) return 'e-sport valorant';
    if (normalized.includes('hoki')) return 'hoki';
    if (normalized.includes('takraw')) return 'sepak takraw';
    if (normalized.includes('ragbi')) return 'ragbi';
    if (normalized.includes('basikal')) return 'berbasikal';
    if (normalized.includes('tenpin')) return 'tenpin bowling';
    if (normalized.includes('ping pong')) return 'ping pong';
    if (normalized.includes('bola tampar')) return 'bola tampar';
    if (normalized.includes('bola jaring')) return 'bola jaring';
    if (normalized.includes('bola keranjang')) return 'bola keranjang';
    if (normalized.includes('bola baling')) return 'bola baling';
    if (normalized.includes('bola sepak')) return 'bola sepak';
    if (normalized.includes('sofbol')) return 'sofbol';

    return sportDisplayName(event);
}

function sportMascot(event: string, sportsCatalog: Array<{ name: string | null; icon: string | null }>): string | null {
    const target = sportDisplayName(event).toLowerCase();
    const sport = sportsCatalog.find((candidate) => {
        const name = candidate.name?.toLowerCase() ?? '';

        return name === target || name.includes(target) || target.includes(name);
    });

    return sport?.icon ?? null;
}

function sportDisplayName(event: string): string {
    const normalized = event.toLowerCase();

    if (normalized.includes('e-sport')) return 'E-Sport';
    if (normalized.includes('hoki')) return 'Hoki';
    if (normalized.includes('takraw')) return 'Sepak Takraw';
    if (normalized.includes('ragbi')) return 'Ragbi';
    if (normalized.includes('basikal')) return 'Basikal';
    if (normalized.includes('tenpin')) return 'Tenpin Bowling';
    if (normalized.includes('ping pong')) return 'Ping Pong';
    if (normalized.includes('bola tampar')) return 'Bola Tampar';
    if (normalized.includes('bola jaring')) return 'Bola Jaring';
    if (normalized.includes('bola keranjang')) return 'Bola Keranjang';
    if (normalized.includes('bola baling')) return 'Bola Baling';
    if (normalized.includes('bola sepak')) return 'Bola Sepak';
    if (normalized.includes('sofbol')) return 'Sofbol';

    return event
        .replace(/\s+Campuran$/i, '')
        .replace(/\s+Berpasukan$/i, '')
        .replace(/\s+\([^)]*\)$/i, '')
        .replace(/\s+\d+\s+Sebelah$/i, '')
        .trim();
}
