import ParticipantLogo, { type ParticipantLogoSize, type ParticipantLogoSource } from '@/components/ParticipantLogo';
import { cn } from '@/lib/utils';

type Props = {
    team: ParticipantLogoSource | null;
    right?: boolean;
    reverse?: boolean;
    center?: boolean;
    surface?: 'light' | 'dark';
    size?: ParticipantLogoSize;
    fallback?: string;
};

export default function PublicTeamRow({ team, right = false, reverse = false, center = false, surface = 'light', size = 'sm', fallback = '—' }: Props) {
    const label = (
        <span className={cn('min-w-0 truncate text-sm font-bold', surface === 'dark' ? 'text-white' : 'text-[var(--public-text)]')}>
            {team?.name || fallback}
        </span>
    );
    const logo = <ParticipantLogo participant={team} surface={surface} size={size} alt="" />;

    return (
        <div className={cn('flex min-w-0 items-center gap-2', right && 'justify-end text-right', center && 'justify-center text-center')}>
            {reverse ? <>{label}{logo}</> : <>{logo}{label}</>}
        </div>
    );
}