import { type ReactNode } from 'react';

type Props = {
    eyebrow: string;
    title: string;
    intro?: string | null;
    icon?: ReactNode;
    children?: ReactNode;
};

export default function PublicPageHero({ eyebrow, title, intro, icon, children }: Props) {
    return (
        <section className="relative isolate overflow-hidden bg-[var(--public-dark)] text-white">
            <div aria-hidden="true" className="absolute inset-0 -z-10 bg-gradient-to-br from-[var(--public-dark)] via-[var(--public-primary)] to-[var(--public-dark)]" />
            <div aria-hidden="true" className="public-cosmic-grid absolute inset-0 -z-10 opacity-20" />
            <div aria-hidden="true" className="public-cosmic-orbit absolute -right-28 top-24 -z-10 size-[28rem] rounded-full border border-white/10" />
            <div className="mx-auto max-w-7xl px-4 pb-16 pt-16 sm:px-6 sm:pt-20 lg:pb-20">
                {icon ? (
                    <p className="flex items-center gap-2 text-[10px] font-black uppercase tracking-[.24em] text-[var(--public-accent)]">
                        {icon}{eyebrow}
                    </p>
                ) : null}
                <h1 className="mt-4 max-w-3xl text-4xl font-black leading-[.98] tracking-[-.05em] sm:text-6xl">{title}</h1>
                {intro ? <p className="mt-5 max-w-2xl text-sm leading-7 text-white/65 sm:text-base">{intro}</p> : null}
                {children}
            </div>
        </section>
    );
}