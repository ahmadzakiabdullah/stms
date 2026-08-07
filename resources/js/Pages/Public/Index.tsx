import { Head, Link, router } from '@inertiajs/react';
import { CalendarDays, ChevronRight, Clock3, LogIn, MapPin, Medal, RefreshCw, Trophy, Users } from 'lucide-react';
import { useEffect, useState } from 'react';

type Team = { name: string; logo_url: string | null } | null;
type Match = { id: string; sport: string | null; event: string | null; stage: string | null; match_number: number; scheduled_at: string; venue: string | null; status: string; home: Team; away: Team; score_home: number | null; score_away: number | null };
type MedalRow = { rank: number; participant_name: string; team_name: string | null; logo_url: string | null; gold: number; silver: number; bronze: number; total_medals: number };
type Props = { app_name: string; competition: { name: string; description: string | null; start_date: string | null; end_date: string | null; organization: string | null } | null; stats: { sports: number; events: number; faculties: number; completed_matches: number; total_matches: number }; sports: string[]; upcoming: Match[]; results: Match[]; medals: MedalRow[]; updated_at: string };

const LIVE_PROPS = ['results', 'upcoming', 'medals', 'stats', 'updated_at'] as const;

const formatDate = (value: string, withTime = false) => {
    if (!value) return 'Akan ditentukan';
    return new Intl.DateTimeFormat('ms-MY', { day: 'numeric', month: 'short', year: 'numeric', ...(withTime ? { hour: '2-digit', minute: '2-digit' } : {}) }).format(new Date(value));
};
const teamName = (team: Team) => team?.name || 'Akan ditentukan';

function MatchCard({ match, result = false }: { match: Match; result?: boolean }) {
    return <article className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
        <div className="flex items-center justify-between gap-3 text-xs font-semibold uppercase tracking-wider text-emerald-700"><span>{match.sport || 'Acara'} · {match.event}</span><span className={match.status === 'in_progress' ? 'rounded-full bg-red-50 px-2 py-1 text-red-600' : 'text-slate-400'}>{match.status === 'in_progress' ? 'Sedang berlangsung' : result ? 'Tamat' : formatDate(match.scheduled_at, true)}</span></div>
        <div className="my-4 grid grid-cols-[1fr_auto_1fr] items-center gap-3">
            <div className="text-right font-semibold text-slate-800">{teamName(match.home)}</div>
            <div className="min-w-16 rounded-xl bg-slate-950 px-3 py-2 text-center font-mono text-lg font-bold text-white">{result ? `${match.score_home ?? 0} – ${match.score_away ?? 0}` : 'VS'}</div>
            <div className="font-semibold text-slate-800">{teamName(match.away)}</div>
        </div>
        <div className="flex flex-wrap items-center justify-center gap-x-4 gap-y-1 border-t border-slate-100 pt-3 text-xs text-slate-500"><span className="flex items-center gap-1"><MapPin className="size-3.5" />{match.venue || 'Lokasi akan diumumkan'}</span>{match.stage && <span>{match.stage.replace(/_/g, ' ')}</span>}</div>
    </article>;
}

export default function PublicIndex({ app_name, competition, stats, sports, upcoming, results, medals, updated_at }: Props) {
    const [refreshing, setRefreshing] = useState(false);

    const refresh = (silent = false) => {
        if (silent) {
            router.reload({ only: [...LIVE_PROPS], preserveScroll: true, preserveState: true });
        } else {
            setRefreshing(true);
            router.reload({ only: [...LIVE_PROPS], preserveScroll: true, preserveState: true, onFinish: () => setRefreshing(false) });
        }
    };

    useEffect(() => {
        const id = setInterval(() => {
            if (document.visibilityState === 'visible') refresh(true);
        }, 30000);
        const onVisible = () => {
            if (document.visibilityState === 'visible') refresh(true);
        };
        document.addEventListener('visibilitychange', onVisible);
        return () => {
            clearInterval(id);
            document.removeEventListener('visibilitychange', onVisible);
        };
    }, []);

    useEffect(() => {
        if (window.location.pathname.endsWith('/index.php')) {
            window.history.replaceState({}, '', `/portal/${window.location.search}${window.location.hash}`);
        }
    }, []);

    const progress = stats.total_matches ? Math.round((stats.completed_matches / stats.total_matches) * 100) : 0;
    return <><Head title={competition?.name || 'Sukan Antara Fakulti 2026'}><link rel="canonical" href="https://saf.utem.edu.my/portal/" /></Head>
        <div className="min-h-screen bg-[#f6f8f7] text-slate-950">
            <header className="sticky top-0 z-40 border-b border-slate-200 bg-white/95 text-slate-950 backdrop-blur">
                <div className="mx-auto flex h-20 max-w-7xl items-center justify-between px-4 sm:px-6"><a href="/portal/" className="flex items-center gap-3"><img src="/portal/images/utem-logo.svg" alt="UTeM" className="h-16 w-auto object-contain" /><span><small className="block text-xs tracking-[.2em] text-emerald-600">Portal Rasmi</small><b className="block text-base leading-tight">{app_name || 'SAF 2026'}</b></span></a>
                    <nav className="hidden gap-7 text-sm text-slate-600 md:flex"><a href="#pingat" className="hover:text-slate-950">Kedudukan Pingat</a><a href="#jadual" className="hover:text-slate-950">Jadual</a><a href="#keputusan" className="hover:text-slate-950">Keputusan</a></nav><Link href={route('login')} className="flex items-center gap-2 rounded-full border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-100"><LogIn className="size-4" /> Log masuk</Link></div>
            </header>
            <main id="utama">
                <section className="relative overflow-hidden bg-slate-950 text-white"><div className="absolute -right-24 -top-24 size-96 rounded-full bg-emerald-500/20 blur-3xl" /><div className="relative mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:px-6 md:grid-cols-5 md:py-24"><div className="md:col-span-3"><div className="mb-5 inline-flex items-center gap-2 rounded-full border border-emerald-400/30 bg-emerald-400/10 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-emerald-300"><span className="size-2 animate-pulse rounded-full bg-emerald-400" /> Hab informasi rasmi</div><p className="mb-2 text-sm font-medium uppercase tracking-[.25em] text-slate-400">{competition?.organization || 'Universiti Teknikal Malaysia Melaka'}</p><h1 className="max-w-4xl text-4xl font-black tracking-tight sm:text-6xl">{competition?.name || 'Sukan Antara Fakulti 2026'}</h1><p className="mt-5 max-w-2xl text-lg leading-8 text-slate-300">{competition?.description || 'Ikuti jadual pertandingan, keputusan terkini dan kedudukan pingat dalam satu paparan rasmi.'}</p>{competition?.start_date && <div className="mt-8 flex flex-wrap gap-5 text-sm text-slate-300"><span className="flex items-center gap-2"><CalendarDays className="size-5 text-emerald-400" />{formatDate(competition.start_date)}{competition.end_date && ` — ${formatDate(competition.end_date)}`}</span><span className="flex items-center gap-2"><Clock3 className="size-5 text-emerald-400" />Dikemas kini {formatDate(updated_at, true)}</span></div>}<div className="mt-8 max-w-2xl rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur"><div className="flex items-end justify-between"><span className="text-sm text-slate-400">Kemajuan pertandingan</span><b className="text-3xl text-emerald-400">{progress}%</b></div><div className="mt-4 h-2 overflow-hidden rounded-full bg-white/10"><div className="h-full rounded-full bg-emerald-400" style={{ width: `${progress}%` }} /></div><p className="mt-3 text-xs text-slate-400">{stats.completed_matches} daripada {stats.total_matches} perlawanan selesai</p></div></div>
                    <div id="pingat" className="self-end rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur md:col-span-2">{medals.length > 0 ? <><p className="text-xs font-bold uppercase tracking-widest text-emerald-300">Kedudukan pingat</p><div className="mt-3 overflow-hidden rounded-xl border border-white/10"><table className="w-full text-left text-xs"><thead className="bg-white/10 text-[10px] uppercase tracking-wider text-slate-300"><tr><th className="p-2.5 text-center">#</th><th className="p-2.5">Kontinjen</th><th className="p-2.5 text-center text-amber-300">Emas</th><th className="p-2.5 text-center text-slate-200">Perak</th><th className="p-2.5 text-center text-orange-300">Gangsa</th><th className="p-2.5 text-center">Jumlah</th></tr></thead><tbody>{medals.map(row=><tr key={row.participant_name} className="border-t border-white/10"><td className="p-2.5 text-center font-bold">{row.rank}</td><td className="p-2.5"><div className="flex items-center gap-2">{row.logo_url && <img src={row.logo_url} alt={row.participant_name} title={row.team_name || row.participant_name} className="h-7 w-auto max-w-[4.5rem] object-contain" />}<span className="font-semibold text-white">{row.participant_name}</span></div></td><td className="p-2.5 text-center font-bold text-amber-300">{row.gold}</td><td className="p-2.5 text-center text-slate-300">{row.silver}</td><td className="p-2.5 text-center text-orange-300">{row.bronze}</td><td className="p-2.5 text-center font-black text-white">{row.total_medals}</td></tr>)}</tbody></table></div><p className="mt-2 text-[10px] text-slate-400">Pingat dikira daripada perlawanan akhir dan penentuan tempat ketiga.</p></> : <p className="text-center text-sm text-slate-400">Kedudukan pingat akan dikemas kini selepas acara akhir selesai.</p>}</div></div></section>
                <section aria-label="Ringkasan pertandingan" className="mx-auto grid max-w-7xl grid-cols-2 gap-3 px-4 py-8 sm:px-6 md:grid-cols-4">{[[stats.sports,'Sukan',Trophy],[stats.events,'Acara',Medal],[stats.faculties,'Kontinjen',Users],[stats.completed_matches,'Keputusan',ChevronRight]].map(([value,label,Icon]: any)=><div key={label} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><Icon className="mb-3 size-5 text-emerald-600"/><b className="block text-3xl">{value}</b><span className="text-sm text-slate-500">{label}</span></div>)}</section>
                {sports.length > 0 && <div className="mx-auto flex max-w-7xl flex-wrap gap-2 px-4 pt-10 sm:px-6">{sports.map(sport=><span key={sport} className="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600">{sport}</span>)}</div>}
                <section id="jadual" className="mx-auto max-w-7xl px-4 py-16 sm:px-6"><div className="mb-7 flex items-end justify-between"><div><p className="text-sm font-bold uppercase tracking-widest text-emerald-600">Seterusnya</p><h2 className="mt-1 text-3xl font-black">Jadual perlawanan</h2></div></div>{upcoming.length ? <div className="grid gap-4 lg:grid-cols-2">{upcoming.map(match=><MatchCard key={match.id} match={match}/>)}</div> : <Empty text="Jadual perlawanan akan dipaparkan selepas diterbitkan oleh urus setia." />}</section>
                <section id="keputusan" className="bg-white py-16"><div className="mx-auto max-w-7xl px-4 sm:px-6"><div className="mb-7 flex flex-wrap items-end justify-between gap-4"><div><p className="text-sm font-bold uppercase tracking-widest text-emerald-600">Terkini</p><h2 className="mb-1 mt-1 text-3xl font-black">Keputusan perlawanan</h2><p className="text-xs text-slate-500">Dikemas kini {formatDate(updated_at, true)} · auto-segar setiap 30 saat</p></div><button type="button" onClick={() => refresh()} disabled={refreshing} className="flex items-center gap-2 rounded-full border border-emerald-600/30 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100 disabled:opacity-60"><RefreshCw className={`size-4 ${refreshing ? 'animate-spin' : ''}`} />{refreshing ? 'Menyegarkan…' : 'Muat semula'}</button></div>{results.length ? <div className="grid gap-4 lg:grid-cols-2">{results.map(match=><MatchCard key={match.id} match={match} result/>)}</div> : <Empty text="Belum ada keputusan rasmi direkodkan." />}</div></section>
            </main><footer className="border-t border-slate-800 bg-slate-950 text-slate-400"><div className="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-8 text-sm sm:flex-row sm:items-center sm:justify-between sm:px-6"><span>© 2026 Universiti Teknikal Malaysia Melaka (UTeM)</span><span>Maklumat rasmi Sukan Antara Fakulti</span></div></footer>
        </div></>;
}

function Empty({ text }: { text: string }) { return <div className="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">{text}</div>; }

