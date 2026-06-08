import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig } from 'vite';

// In build Docker gli asset si compilano in uno stage Node senza PHP: i file
// Wayfinder vengono pre-generati nello stage PHP e copiati, quindi il plugin
// (che invocherebbe `php artisan`) va saltato con WAYFINDER_SKIP=1.
const plugins = [
    laravel({
        input: ['resources/css/app.css', 'resources/js/app.ts'],
        refresh: true,
        fonts: [
            bunny('Instrument Sans', {
                weights: [400, 500, 600],
            }),
        ],
    }),
    inertia(),
    tailwindcss(),
    vue({
        template: {
            transformAssetUrls: {
                base: null,
                includeAbsolute: false,
            },
        },
    }),
];

if (!process.env.WAYFINDER_SKIP) {
    plugins.push(
        wayfinder({
            formVariants: true,
        }),
    );
}

export default defineConfig({ plugins });
