const IMAGES: Record<string, string> = {
    aerobics: '/icons8/dumbbell.png',
    archery: '/icons8/archer.png',
    badminton: '/icons8/badminton.png',
    basketball: '/icons8/basketball.png',
    'bola keranjang': '/icons8/basketball.png',
    bowling: '/icons8/bowling-ball.png',
    chess: '/icons8/knight.png',
    catur: '/icons8/knight.png',
    cycling: '/icons8/bicycle.png',
    berbasikal: '/icons8/bicycle.png',
    'e-sport (mobile legends)': '/icons8/controller.png',
    'e-sport mobile legend': '/icons8/controller.png',
    'e-sport (valorant)': '/icons8/target.png',
    'e-sport valorant': '/icons8/target.png',
    football: '/icons8/football.png',
    'bola sepak': '/icons8/football.png',
    futsal: '/icons8/football2.png',
    handball: '/icons8/handball.png',
    'bola baling': '/icons8/handball.png',
    hockey: '/icons8/hockey-2.png',
    hoki: '/icons8/hockey-2.png',
    'indoor rowing': '/icons8/rowing.png',
    kayak: '/icons8/rowing.png',
    'lawn bowls': '/icons8/bocce.png',
    netball: '/icons8/basketball-field.png',
    'bola jaring': '/icons8/basketball-field.png',
    petanque: '/icons8/petanque.png',
    rugby: '/icons8/rugby.png',
    ragbi: '/icons8/rugby.png',
    'sepak takraw': '/icons8/takraw-balls.png',
    softball: '/icons8/softball-mitt.png',
    sofbol: '/icons8/softball-mitt.png',
    'table tennis': '/icons8/ping-pong.png',
    'ping pong': '/icons8/ping-pong.png',
    tennis: '/icons8/tennis.png',
    'tenpin bowling': '/icons8/bowling-pins.png',
    'tenpin boling': '/icons8/bowling-pins.png',
    volleyball: '/icons8/volleyball.png',
    'bola tampar': '/icons8/volleyball.png',
    memanah: '/icons8/archer.png',
};

const FALLBACK = '/icons8/trophy.png';

export function SportIcon({ name, className = 'text-base leading-none' }: { name: string; className?: string }) {
    const src = IMAGES[name.trim().toLowerCase()] ?? FALLBACK;
    return (
        <span
            className={['sport-icon', className].join(' ')}
            style={{ WebkitMaskImage: `url("${src}")`, maskImage: `url("${src}")` }}
            aria-hidden="true"
        />
    );
}
