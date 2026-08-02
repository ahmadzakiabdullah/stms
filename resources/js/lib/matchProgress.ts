export interface ProgressStyle {
    label: string;
    badge: string;
    bar: string;
    pct: number;
}

export function matchProgress(total: number, done: number): ProgressStyle {
    const pct = total > 0 ? Math.round((done / total) * 100) : 0;
    if (total === 0) return { label: 'Not Drawn', badge: 'bg-gray-100 text-gray-600', bar: 'bg-gray-400', pct };
    if (done === 0) return { label: 'Not Started', badge: 'bg-yellow-100 text-yellow-700', bar: 'bg-yellow-500', pct };
    if (done >= total) return { label: 'Completed', badge: 'bg-emerald-100 text-emerald-700', bar: 'bg-emerald-500', pct };
    return { label: 'In Progress', badge: 'bg-blue-100 text-blue-700', bar: 'bg-blue-500', pct };
}
