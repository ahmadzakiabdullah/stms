import { Inbox, type LucideIcon } from 'lucide-react';
import { type ReactNode } from 'react';

import { cn } from '@/lib/utils';

interface EmptyStateProps {
    title?: string;
    description?: string;
    icon?: LucideIcon;
    action?: ReactNode;
    className?: string;
}

export function EmptyState({ title, description, icon: Icon = Inbox, action, className }: EmptyStateProps) {
    return (
        <div
            className={cn(
                'flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-border p-8 text-center',
                className,
            )}
        >
            <Icon className="size-8 text-muted-foreground" aria-hidden="true" />
            {title && <p className="text-sm font-medium">{title}</p>}
            {description && <p className="text-sm text-muted-foreground">{description}</p>}
            {action}
        </div>
    );
}
