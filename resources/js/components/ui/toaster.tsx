import { CheckCircle2, Info, X, XCircle } from 'lucide-react';
import { useSyncExternalStore } from 'react';

import { cn } from '@/lib/utils';
import { dismiss, getSnapshot, subscribe, type ToastVariant } from '@/lib/toast';

const variants: Record<ToastVariant, { icon: typeof Info; className: string }> = {
    success: { icon: CheckCircle2, className: 'text-emerald-600 dark:text-emerald-400' },
    error: { icon: XCircle, className: 'text-destructive' },
    info: { icon: Info, className: 'text-sky-600 dark:text-sky-400' },
};

export function Toaster() {
    const items = useSyncExternalStore(subscribe, getSnapshot, getSnapshot);

    if (items.length === 0) {
        return null;
    }

    return (
        <div
            role="region"
            aria-label="Notifications"
            className="pointer-events-none fixed inset-x-0 top-0 z-[100] flex flex-col items-center gap-2 p-4 sm:items-end"
        >
            {items.map((item) => {
                const variant = variants[item.variant];
                const Icon = variant.icon;

                return (
                    <div
                        key={item.id}
                        role="status"
                        aria-live="polite"
                        className="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg border border-border bg-card p-4 text-card-foreground shadow-lg"
                    >
                        <Icon className={cn('mt-0.5 size-5 shrink-0', variant.className)} />
                        <div className="min-w-0 flex-1">
                            <p className="text-sm font-medium">{item.title}</p>
                            {item.description && (
                                <p className="mt-1 text-sm text-muted-foreground">{item.description}</p>
                            )}
                        </div>
                        <button
                            type="button"
                            onClick={() => dismiss(item.id)}
                            aria-label="Dismiss notification"
                            className="rounded-md p-1 text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        >
                            <X className="size-4" />
                        </button>
                    </div>
                );
            })}
        </div>
    );
}
