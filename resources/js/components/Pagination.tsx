import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { type Paginated } from '@/types';

interface PaginationProps<T> {
    paginator?: Partial<Paginated<T>> | T[] | null;
    links?: Paginated<T>['links'];
}

export default function Pagination<T>({ paginator, links: legacyLinks }: PaginationProps<T>) {
    if (Array.isArray(paginator)) return null;

    const links = paginator?.links ?? legacyLinks ?? [];
    if (links.length < 3) return null;

    const current_page = paginator?.current_page ?? 1;
    const last_page = paginator?.last_page ?? 1;
    const per_page = paginator?.per_page ?? 0;
    const total = paginator?.total ?? 0;

    const prevLink = links.find((l) => l.label.includes('Previous') || l.label.includes('‹'));
    const nextLink = links.find((l) => l.label.includes('Next') || l.label.includes('›'));

    const pages: number[] = [];
    for (let i = Math.max(1, current_page - 1); i <= Math.min(last_page, current_page + 1); i++) {
        pages.push(i);
    }

    const from = total === 0 ? 0 : (current_page - 1) * per_page + 1;
    const to = Math.min(current_page * per_page, total);

    return (
        <div className="mt-4 flex flex-col items-center justify-between gap-3 px-2 text-sm text-muted-foreground sm:flex-row">
            <div>
                Showing <span className="font-medium text-foreground">{from}</span> to{' '}
                <span className="font-medium text-foreground">{to}</span> of{' '}
                <span className="font-medium text-foreground">{total}</span> results
            </div>

            <div className="flex items-center gap-1">
                {prevLink && (
                    <Button asChild variant="outline" size="sm" disabled={!prevLink.url}>
                        <Link
                            href={prevLink.url || '#'}
                            className={!prevLink.url ? 'pointer-events-none opacity-40' : ''}
                        >
                            <ChevronLeft className="mr-1 size-4" />
                            Prev
                        </Link>
                    </Button>
                )}

                <div className="flex items-center gap-1 px-2 text-xs">
                    {current_page > 2 && <span className="px-1">...</span>}
                    {pages.map((page) => {
                        const pageLink = links.find((l) => parseInt(l.label) === page);
                        const isActive = page === current_page;

                        if (pageLink && pageLink.url) {
                            return (
                                <Button
                                    key={page}
                                    asChild
                                    variant={isActive ? 'default' : 'ghost'}
                                    size="sm"
                                    className="h-8 w-8 p-0 text-xs"
                                >
                                    <Link href={pageLink.url}>
                                        {page}
                                    </Link>
                                </Button>
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
                    <Button asChild variant="outline" size="sm" disabled={!nextLink.url}>
                        <Link
                            href={nextLink.url || '#'}
                            className={!nextLink.url ? 'pointer-events-none opacity-40' : ''}
                        >
                            Next
                            <ChevronRight className="ml-1 size-4" />
                        </Link>
                    </Button>
                )}
            </div>
        </div>
    );
}
