import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

/**
 * Tracks Inertia visit lifecycles so pages can render loading placeholders
 * (skeletons) while a request is in flight.
 */
export function usePageLoading(): boolean {
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        const removeStart = router.on('start', () => setLoading(true));
        const removeFinish = router.on('finish', () => setLoading(false));

        return () => {
            removeStart();
            removeFinish();
        };
    }, []);

    return loading;
}
