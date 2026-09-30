import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            // Downloaded at build time and self-hosted: no third-party font request at runtime.
            fonts: [
                bunny('Inter', {
                    alias: 'inter',
                    weights: [400, 500, 600, 700],
                    subsets: ['latin', 'latin-ext'],
                    preload: [{ weight: 400 }],
                }),
                bunny('IBM Plex Sans Arabic', {
                    alias: 'arabic',
                    weights: [400, 500, 600, 700],
                    subsets: ['arabic'],
                    preload: [{ weight: 400 }],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
