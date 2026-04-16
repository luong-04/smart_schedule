import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', 
                'resources/js/app.js',
                'resources/js/admin/admin-common.js',
                'resources/js/admin/schedule-matrix.js'
            ],
            refresh: true,
        }),
    ],
});
