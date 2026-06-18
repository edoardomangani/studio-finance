import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name === 'Onboarding':
                // Pagina bloccante pre-shell: stesso guscio split delle auth,
                // niente sidebar/topbar.
                return AuthLayout;
            case name.startsWith('auth/'):
                return AuthLayout;
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();

// PWA: registra il service worker (servito a scope root da /sw.js) solo in
// build di produzione — in dev non esiste, così l'HMR di Vite resta intatto.
// Fallimento silenzioso: senza SW l'app funziona, manca solo l'installabilità.
if (import.meta.env.PROD && 'serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker
            .register('/build/serviceworker.js', { scope: '/' })
            .catch(() => {});
    });
}
