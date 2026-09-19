type Props = {
    eyebrow?: string;
    title: string;
    description?: string | null;
};

export default function PublicSectionHeading({ eyebrow, title, description }: Props) {
    return (
        <div className="max-w-2xl">
            {eyebrow ? <p className="text-xs font-black uppercase tracking-[.22em] text-[var(--public-primary)]">{eyebrow}</p> : null}
            <h2 className={`text-3xl font-black tracking-[-.04em] text-[var(--public-text)] sm:text-5xl ${eyebrow ? 'mt-3' : ''}`}>{title}</h2>
            {description ? <p className="mt-4 text-sm leading-6 text-[var(--public-dark-faint)] sm:text-base">{description}</p> : null}
        </div>
    );
}