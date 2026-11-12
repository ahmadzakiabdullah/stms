import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    // Keep split chunks relative to the entry asset.
    base: './',
    build: {
        rolldownOptions: {
            output: {
                // Split the shared runtime so each chunk stays under the bundle
                // budget and the frequently edited i18n dictionary does not
                // invalidate the cached React/Inertia vendor code.
                codeSplitting: {
                    groups: [
                        { name: 'react-vendor', test: /[\\/]node_modules[\\/](react|react-dom|scheduler)[\\/]/, priority: 30 },
                        { name: 'inertia-vendor', test: /[\\/]node_modules[\\/](@inertiajs|axios|laravel-precognition|qs)[\\/]/, priority: 20 },
                        { name: 'i18n', test: /[\\/]resources[\\/]js[\\/]lib[\\/]i18n\.ts$/, priority: 10 },
                    ],
                },
            },
        },
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
