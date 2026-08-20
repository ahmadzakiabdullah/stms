type Props = {
    eyebrow: string;
    title: string;
    description?: string | null;
};

export default function PublicSectionHeading({ eyebrow, title, description }: Props) {
    return (
        <div className="max-w-2xl">
            <p className="text-[11px] font-black uppercase tracking-[.22em] text-[var(--public-primary)]">{eyebrow}</p>
            <h2 className="mt-3 text-3xl font-black tracking-[-.04em] text-[var(--public-text)] sm:text-5xl">{title}</h2>
            {description ? <p className="mt-4 text-sm leading-6 text-[var(--public-dark-faint)] sm:text-base">{description}</p> : null}
        </div>
    );
}