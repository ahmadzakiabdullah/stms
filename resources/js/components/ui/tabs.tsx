import * as React from 'react';
import { Tabs as TabsPrimitive } from 'radix-ui';

import { cn } from '@/lib/utils';

function Tabs({ className, ...props }: any) {
    return <TabsPrimitive.Root data-slot="tabs" className={cn('flex flex-col gap-2', className)} {...props} />;
}

function TabsList({ className, ...props }: any) {
    return <TabsPrimitive.List data-slot="tabs-list" className={cn('inline-flex w-fit items-center justify-center rounded-2xl border border-[var(--public-dark-border)] bg-[var(--public-dark-soft)] p-1', className)} {...props} />;
}

function TabsTrigger({ className, ...props }: any) {
    return <TabsPrimitive.Trigger data-slot="tabs-trigger" className={cn('inline-flex min-h-11 items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-black transition hover:text-[var(--public-primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--public-primary)]/40 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-[var(--public-primary)] data-[state=active]:text-white data-[state=active]:shadow-sm', className)} {...props} />;
}

function TabsContent({ className, ...props }: any) {
    return <TabsPrimitive.Content data-slot="tabs-content" className={cn('outline-none', className)} {...props} />;
}

export { Tabs, TabsContent, TabsList, TabsTrigger };
