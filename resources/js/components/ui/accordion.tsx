import * as React from 'react';
import { Accordion as AccordionPrimitive } from 'radix-ui';

import { cn } from '@/lib/utils';

function Accordion({ className, ...props }: any) {
    return <AccordionPrimitive.Root data-slot="accordion" className={cn('w-full', className)} {...props} />;
}

function AccordionItem({ className, ...props }: any) {
    return <AccordionPrimitive.Item data-slot="accordion-item" className={cn('border-b border-[var(--public-dark-border)]', className)} {...props} />;
}

function AccordionTrigger({ className, children, ...props }: any) {
    return (
        <AccordionPrimitive.Header className="flex">
            <AccordionPrimitive.Trigger
                data-slot="accordion-trigger"
                className={cn('flex min-h-11 flex-1 items-center justify-between py-4 text-left text-sm font-black transition hover:text-[var(--public-primary)] [&[data-state=open]>span]:rotate-180', className)}
                {...props}
            >
                {children}
                <span aria-hidden="true" className="ml-4 text-lg leading-none transition-transform">⌄</span>
            </AccordionPrimitive.Trigger>
        </AccordionPrimitive.Header>
    );
}

function AccordionContent({ className, children, ...props }: any) {
    return (
        <AccordionPrimitive.Content
            data-slot="accordion-content"
            className={cn('overflow-hidden text-sm text-[var(--public-dark-faint)] data-[state=closed]:animate-accordion-up data-[state=open]:animate-accordion-down', className)}
            {...props}
        >
            <div className="pb-4 leading-6">{children}</div>
        </AccordionPrimitive.Content>
    );
}

export { Accordion, AccordionContent, AccordionItem, AccordionTrigger };
