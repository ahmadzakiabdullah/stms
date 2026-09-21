import * as React from 'react';

import { cn } from '@/lib/utils';

function Alert({ className, variant = 'default', ...props }: any) {
    return (
        <div
            role="alert"
            data-slot="alert"
            data-variant={variant}
            className={cn(
                'relative w-full rounded-2xl border p-4 text-sm [&>svg~*]:pl-7 [&>svg+div]:translate-y-[-3px] [&>svg]:absolute [&>svg]:left-4 [&>svg]:top-4 [&>svg]:size-4',
                variant === 'destructive'
                    ? 'border-red-200 bg-red-50 text-red-900 [&>svg]:text-red-700'
                    : 'border-border bg-background text-foreground',
                className,
            )}
            {...props}
        />
    );
}

function AlertTitle({ className, ...props }: any) {
    return <h5 data-slot="alert-title" className={cn('mb-1 font-black leading-none tracking-tight', className)} {...props} />;
}

function AlertDescription({ className, ...props }: any) {
    return <div data-slot="alert-description" className={cn('text-sm [&_p]:leading-relaxed', className)} {...props} />;
}

export { Alert, AlertDescription, AlertTitle };
