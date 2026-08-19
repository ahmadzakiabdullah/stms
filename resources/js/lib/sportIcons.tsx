const CLASSES: Record<string, string> = {
    aerobics: 'fi fi-rr-ballet-dance',
    archery: 'fi fi-rr-bow-arrow',
    badminton: 'fi fi-rr-badminton',
    basketball: 'fi fi-rr-basketball',
    bowling: 'fi fi-rr-bowling-ball',
    chess: 'fi fi-rr-chess-knight',
    cycling: 'fi fi-rr-cycling',
    'e-sport (mobile legends)': 'fi fi-rr-gamepad',
    'e-sport (valorant)': 'fi fi-rr-target',
    football: 'fi fi-rr-football',
    futsal: 'fi fi-rr-goal-net',
    handball: 'fi fi-rr-hand-fist',
    hockey: 'fi fi-field-hockey',
    'indoor rowing': 'fi fi-rr-kayak',
    'lawn bowls': 'fi fi-rr-target',
    netball: 'fi fi-rr-basketball',
    petanque: 'fi fi-rr-bowling-ball',
    rugby: 'fi fi-rr-rugby',
    'sepak takraw': 'fi fi-rr-volleyball-player',
    softball: 'fi fi-rr-baseball-alt',
    'table tennis': 'fi fi-rr-ping-pong',
    tennis: 'fi fi-rr-tennis',
    'tenpin bowling': 'fi fi-rr-bowling-pins',
    volleyball: 'fi fi-rr-volleyball',
};

export function SportIcon({ name, className = 'text-base leading-none' }: { name: string; className?: string }) {
    const cls = CLASSES[name.trim().toLowerCase()] ?? 'fi fi-rr-trophy';
    return <i className={[cls, className].join(' ')} aria-hidden="true" />;
}