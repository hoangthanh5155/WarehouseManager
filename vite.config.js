import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/products/lookup.js',
                'resources/js/products/pricing.js',
                'resources/js/dashboard/overview.js',
                'resources/js/warehouse/export.js',
                'resources/js/delivery-batches.js',
                'resources/js/pages/print_invoice.js',
            ],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0',
        hmr: {
            host: '103.77.214.206',
        },
    },
});
