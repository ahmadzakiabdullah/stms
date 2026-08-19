export default function PublicEmptyState({ text }: { text: string }) {
    return (
        <div className="rounded-2xl border border-dashed border-[var(--public-dark-border)] bg-[var(--public-dark-soft)] p-8 text-center text-sm text-[var(--public-dark-faint)]">
            {text}
        </div>
    );
}