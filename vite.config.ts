import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig } from 'vite';
import { VitePWA } from 'vite-plugin-pwa';

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
    // PWA: manifest + service worker Workbox. Il SW NON viene registrato in
    // dev (devOptions.enabled=false) per non interferire con l'HMR di Vite, e
    // viene servito a scope root via la rotta Laravel /sw.js (vedi routes).
    // Niente navigateFallback: l'app è server-rendered (Inertia), il SW fa solo
    // precache degli asset statici, nessun caching dei dati.
    VitePWA({
        registerType: 'autoUpdate',
        injectRegister: null,
        scope: '/',
        manifest: {
            name: 'StudioFinance',
            short_name: 'StudioFinance',
            description: 'Gestionale fiscale per studi professionali',
            lang: 'it',
            theme_color: '#18181b',
            background_color: '#18181b',
            display: 'standalone',
            orientation: 'portrait',
            scope: '/',
            start_url: '/',
            icons: [
                {
                    src: '/pwa-192x192.png',
                    sizes: '192x192',
                    type: 'image/png',
                    purpose: 'any',
                },
                {
                    src: '/pwa-512x512.png',
                    sizes: '512x512',
                    type: 'image/png',
                    purpose: 'any',
                },
                {
                    src: '/pwa-512x512.png',
                    sizes: '512x512',
                    type: 'image/png',
                    purpose: 'maskable',
                },
            ],
        },
        workbox: {
            navigateFallback: null,
            cleanupOutdatedCaches: true,
            clientsClaim: true,
            skipWaiting: true,
        },
        devOptions: {
            enabled: false,
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
