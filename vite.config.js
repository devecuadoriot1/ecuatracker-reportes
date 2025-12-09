import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

const devServerPort = Number(process.env.DEV_SERVER_PORT ?? '5175', 10) || 5175;

export default defineConfig({
    server: {
        host: '127.0.0.1',
        port: devServerPort,
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/filament/admin/theme.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
