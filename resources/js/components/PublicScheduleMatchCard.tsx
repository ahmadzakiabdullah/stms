import PublicFixtureCard from '@/components/PublicFixtureCard';

export type ScheduleMatchTeam = { name: string; logo_url: string | null; inverse_logo_url: string | null } | null;

export type ScheduleMatch = {
    id: string;
    sport: string | null;
    event: string | null;
    category: string | null;
    stage: string | null;
    round: number | null;
    group: string | null;
    match_number: number;
    scheduled_at: string | null;
    venue: string | null;
    status: 'scheduled' | 'in_progress' | 'completed' | 'cancelled' | 'postponed';
    home: ScheduleMatchTeam;
    away: ScheduleMatchTeam;
    score_home: number | null;
    score_away: number | null;
};

type Props = { match: ScheduleMatch };

export default function ScheduleMatchCard({ match }: Props) {
    const mode = match.status === 'in_progress' ? 'live' : match.status === 'completed' ? 'result' : 'upcoming';
    return <PublicFixtureCard match={match} mode={mode} />;
}
