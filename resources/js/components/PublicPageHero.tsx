import { type ReactNode } from 'react';
import PublicBreadcrumb from '@/components/PublicBreadcrumb';

type Props = {
    eyebrow: string;
    title: string;
    breadcrumbParent?: { href: string; label: string };
    intro?: string | null;
    icon?: ReactNode;
    children?: ReactNode;
    media?: ReactNode;
};

export default function PublicPageHero({ eyebrow, title, breadcrumbParent, intro, icon, children, media }: Props) {
    const heading = (
        <>
            {icon ? (
                <p className="flex items-center gap-2 text-xs font-black uppercase tracking-[.24em] text-[var(--public-accent)]">
                    {icon}{eyebrow}
                </p>
            ) : null}
            <h1 className="mt-4 max-w-3xl text-3xl font-bold leading-tight tracking-[-.035em] sm:text-5xl">{title}</h1>
            {intro ? <p className="mt-4 max-w-2xl text-sm leading-6 text-white/75 sm:text-base sm:leading-7">{intro}</p> : null}
        </>
    );

    return (
        <section className="relative isolate overflow-hidden border-b border-white/10 bg-[var(--public-dark)] text-white">
            <div aria-hidden="true" className="absolute inset-y-0 right-0 -z-10 w-1/3 bg-[var(--public-primary)] opacity-20 [clip-path:polygon(35%_0,100%_0,100%_100%,0_100%)]" />
            <div className="mx-auto max-w-7xl px-4 pb-12 pt-8 sm:px-6 sm:pb-14 sm:pt-10 lg:pb-16">
                <PublicBreadcrumb current={title} parent={breadcrumbParent} />
                {media ? (
                    <div className="lg:grid lg:grid-cols-[minmax(0,1fr)_minmax(22rem,36rem)] lg:items-stretch lg:gap-8">
                        <div className="lg:flex lg:min-h-[45rem] lg:flex-col lg:justify-center">{heading}{children}</div>
                        <div className="lg:flex lg:items-end lg:justify-center">{media}</div>
                    </div>
                ) : (
                    <>{heading}{children}</>
                )}
            </div>
        </section>
    );
}
