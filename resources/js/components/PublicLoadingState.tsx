import { Loader2 } from 'lucide-react';

export default function PublicLoadingState({ label = 'Loading' }: { label?: string }) {
    return (
        <div role="status" aria-live="polite" className="flex min-h-32 items-center justify-center gap-3 rounded-2xl border border-[var(--public-dark-border)] bg-white p-8 text-sm font-semibold text-[var(--public-dark-faint)]">
            <Loader2 className="size-5 animate-spin text-[var(--public-primary)]" aria-hidden="true" />
            <span>{label}…</span>
        </div>
    );
}
