import { router } from '@inertiajs/react';
import { useEffect } from 'react';

import { toast } from '@/lib/toast';

interface FlashShape {
    success?: string;
    error?: string;
    info?: string;
    warning?: string;
}

function handleFlash(flash: unknown): void {
    if (!flash || typeof flash !== 'object') {
        return;
    }

    const value = flash as FlashShape;

    if (value.success) {
        toast.success(value.success);
    }

    if (value.error) {
        toast.error(value.error);
    }

    if (value.warning) {
        toast.info(value.warning);
    }

    if (value.info) {
        toast.info(value.info);
    }
}

/**
 * Bridges Laravel Inertia flash messages into the global toast store so pages
 * do not need to render their own flash banners.
 */
export function FlashListener() {
    useEffect(() => {
        const removeSuccess = router.on('success', (event) => {
            const page = (event as unknown as { detail?: { page?: { props?: { flash?: unknown } } } }).detail?.page;
            handleFlash(page?.props?.flash);
        });

        const removeError = router.on('error', (event) => {
            const page = (event as unknown as { detail?: { page?: { props?: { flash?: unknown } } } }).detail?.page;
            handleFlash(page?.props?.flash);
        });

        return () => {
            removeSuccess();
            removeError();
        };
    }, []);

    return null;
}
