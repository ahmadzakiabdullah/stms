import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    // Keep split chunks relative to the entry asset.
    base: './',
    build: {
        // React's production runtime is currently ~531 kB; keep the warning
        // threshold above that stable vendor chunk without hiding larger bundles.
        chunkSizeWarningLimit: 600,
    },
    plugins: [
        laravel({
            input: 'resources/js/app.tsx',
            refresh: true,
        }),
        react(),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
});
