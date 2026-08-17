import { cn } from '@/lib/utils';

export interface ParticipantLogoSource {
    name?: string | null;
    logo_url?: string | null;
    inverse_logo_url?: string | null;
}

export type ParticipantLogoSize = 'xs' | 'sm' | 'md' | 'lg' | 'xl';

interface ParticipantLogoProps {
    participant?: ParticipantLogoSource | null;
    surface?: 'light' | 'dark';
    size?: ParticipantLogoSize;
    className?: string;
    alt?: string;
}

const sizeClasses: Record<ParticipantLogoSize, string> = {
    xs: 'size-6',
    sm: 'size-9',
    md: 'size-10',
    lg: 'size-12',
    xl: 'size-14',
};

const initials = (name?: string | null) => (name || '?')
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() || '')
    .join('') || '?';

export default function ParticipantLogo({
    participant,
    surface = 'light',
    size = 'md',
    className,
    alt,
}: ParticipantLogoProps) {
    const standardUrl = participant?.logo_url ?? null;
    const inverseUrl = participant?.inverse_logo_url ?? null;
    const imageUrl = surface === 'dark' ? inverseUrl || standardUrl : standardUrl || inverseUrl;
    const usesContrastTile = Boolean(
        imageUrl && (
            (surface === 'dark' && ! inverseUrl)
            || (surface === 'light' && ! standardUrl)
        )
    );

    return (
        <span
            className={cn(
                'flex shrink-0 items-center justify-center overflow-hidden rounded-lg',
                sizeClasses[size],
                imageUrl
                    ? usesContrastTile && (surface === 'dark' ? 'bg-white/95 p-1' : 'bg-slate-950 p-1')
                    : surface === 'dark'
                        ? 'border border-white/15 bg-white/10 text-white'
                        : 'border bg-muted text-muted-foreground',
                className,
            )}
        >
            {imageUrl ? (
                <img
                    src={imageUrl}
                    alt={alt ?? `${participant?.name || 'Participant'} logo`}
                    className="size-full object-contain"
                />
            ) : (
                <span aria-hidden="true" className="text-[10px] font-semibold uppercase">
                    {initials(participant?.name)}
                </span>
            )}
        </span>
    );
}
