import PublicFixtureCard from '@/components/PublicFixtureCard';

export type PublicMatchTeam = { name: string; logo_url: string | null; inverse_logo_url: string | null } | null;

export type PublicMatch = {
    id: string;
    sport: string | null;
    event: string | null;
    stage: string | null;
    round: string | null;
    group: string | null;
    scheduled_at: string | null;
    venue: string | null;
    status: string;
    home: PublicMatchTeam;
    away: PublicMatchTeam;
    score_home: number | null;
    score_away: number | null;
};

export type PublicMatchVariant = 'upcoming' | 'result' | 'live';

export default function PublicMatchCard({ match, variant = 'upcoming' }: { match: PublicMatch; variant?: PublicMatchVariant }) {
    return <PublicFixtureCard match={match} mode={variant} />;
}
