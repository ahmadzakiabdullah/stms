import { type ReactNode } from 'react';

export default function PublicEmptyState({ text, children }: { text: string; children?: ReactNode }) {
    return (
        <div role="status" aria-live="polite" className="rounded-2xl border border-dashed border-[var(--public-dark-border)] bg-[var(--public-dark-soft)] p-8 text-center text-sm text-[var(--public-dark-faint)]">
            <p>{text}</p>
            {children && <div className="mt-4 flex justify-center">{children}</div>}
        </div>
    );
}
