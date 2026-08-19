import { Activity, Bike, Bird, Circle, CircleDot, Cone, Crosshair, Disc, Disc3, Flag, Footprints, Gamepad2, Goal, Hand, HeartPulse, Sailboat, Shield, Star, Table, Target, Trophy, Volleyball, Zap, ChessKnight, type LucideIcon } from 'lucide-react';

const ICONS: Record<string, LucideIcon> = {
    aerobics: HeartPulse,
    archery: Target,
    badminton: Bird,
    basketball: CircleDot,
    bowling: Disc,
    chess: ChessKnight,
    cycling: Bike,
    'e-sport (mobile legends)': Gamepad2,
    'e-sport (valorant)': Crosshair,
    football: Goal,
    futsal: Footprints,
    handball: Hand,
    hockey: Flag,
    'indoor rowing': Sailboat,
    'lawn bowls': Disc,
    netball: Star,
    petanque: Disc3,
    rugby: Shield,
    'sepak takraw': Zap,
    softball: Circle,
    'table tennis': Table,
    tennis: Activity,
    'tenpin bowling': Cone,
    volleyball: Volleyball,
};

export function SportIcon({ name, className }: { name: string; className?: string }) {
    const Icon = ICONS[name.trim().toLowerCase()] ?? Trophy;
    return <Icon className={className} aria-hidden="true" />;
}