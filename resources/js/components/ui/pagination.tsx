import * as React from 'react';
import { Slot } from 'radix-ui';

import { cn } from '@/lib/utils';

function Pagination({ className, ...props }: any) {
    return <nav role="navigation" aria-label="pagination" data-slot="pagination" className={cn('mx-auto flex w-full justify-center', className)} {...props} />;
}

function PaginationContent({ className, ...props }: any) {
    return <ul data-slot="pagination-content" className={cn('flex flex-row items-center gap-1.5', className)} {...props} />;
}

function PaginationItem({ className, ...props }: any) {
    return <li data-slot="pagination-item" className={cn('', className)} {...props} />;
}

const PaginationLink = React.forwardRef(function PaginationLink({ className, isActive, asChild = false, ...props }: any, ref: any) {
    const Comp = asChild ? Slot.Root : 'a';

    return <Comp ref={ref} aria-current={isActive ? 'page' : undefined} data-slot="pagination-link" data-active={isActive} className={cn('inline-flex min-h-10 min-w-10 items-center justify-center rounded-lg border border-transparent px-2.5 text-sm font-black transition hover:border-[var(--public-primary-border)] hover:text-[var(--public-primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--public-primary)]/40 data-[active=true]:border-transparent data-[active=true]:bg-[var(--public-primary)] data-[active=true]:text-white', className)} {...props} />;
}) as any;

function PaginationPrevious({ className, ...props }: any) {
    return <PaginationLink aria-label="Previous" className={cn('gap-1 px-3', className)} {...props} />;
}

function PaginationNext({ className, ...props }: any) {
    return <PaginationLink aria-label="Next" className={cn('gap-1 px-3', className)} {...props} />;
}

function PaginationEllipsis({ className, ...props }: any) {
    return <span aria-hidden="true" data-slot="pagination-ellipsis" className={cn('px-1 text-sm text-[var(--public-dark-faint)]', className)} {...props}>…</span>;
}

export { Pagination, PaginationContent, PaginationEllipsis, PaginationItem, PaginationLink, PaginationNext, PaginationPrevious };
