import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { type Paginated } from '@/types';

interface PaginationProps<T> {
    paginator: Paginated<T>;
}

export default function Pagination<T>({ paginator }: PaginationProps<T>) {
    if (!paginator || !paginator.links || paginator.links.length < 3) {
        return null;
    }

    const { current_page, last_page, total, links } = paginator;

    const prevLink = links.find((l) => l.label.includes('Previous') || l.label.includes('‹'));
    const nextLink = links.find((l) => l.label.includes('Next') || l.label.includes('›'));

    const pages: number[] = [];
    for (let i = Math.max(1, current_page - 1); i <= Math.min(last_page, current_page + 1); i++) {
        pages.push(i);
    }

    const from = (current_page - 1) * paginator.per_page + 1;
    const to = Math.min(current_page * paginator.per_page, total);

    return (
        <div className="mt-4 flex flex-col items-center justify-between gap-3 px-2 text-sm text-muted-foreground sm:flex-row">
            <div>
                Showing <span className="font-medium text-foreground">{from}</span> to{' '}
                <span className="font-medium text-foreground">{to}</span> of{' '}
                <span className="font-medium text-foreground">{total}</span> results
            </div>

            <div className="flex items-center gap-1">
                {prevLink && (
                    <Link
                        href={prevLink.url || '#'}
                        preserveScroll
                        className={!prevLink.url ? 'pointer-events-none opacity-40' : ''}
                    >
                        <Button variant="outline" size="sm" disabled={!prevLink.url}>
                            <ChevronLeft className="mr-1 size-4" />
                            Prev
                        </Button>
                    </Link>
                )}

                <div className="flex items-center gap-1 px-2 text-xs">
                    {current_page > 2 && <span className="px-1">...</span>}
                    {pages.map((page) => {
                        const pageLink = links.find((l) => parseInt(l.label) === page);
                        const isActive = page === current_page;

                        if (pageLink && pageLink.url) {
                            return (
                                <Link key={page} href={pageLink.url} preserveScroll>
                                    <Button
                                        variant={isActive ? 'default' : 'ghost'}
                                        size="sm"
                                        className="h-8 w-8 p-0 text-xs"
                                    >
                                        {page}
                                    </Button>
                                </Link>
                            );
                        }
                        return (
                            <span key={page} className="px-2 text-muted-foreground">
                                {page}
                            </span>
                        );
                    })}
                    {current_page < last_page - 1 && <span className="px-1">...</span>}
                </div>

                {nextLink && (
                    <Link
                        href={nextLink.url || '#'}
                        preserveScroll
                        className={!nextLink.url ? 'pointer-events-none opacity-40' : ''}
                    >
                        <Button variant="outline" size="sm" disabled={!nextLink.url}>
                            Next
                            <ChevronRight className="ml-1 size-4" />
                        </Button>
                    </Link>
                )}
            </div>
        </div>
    );
}
