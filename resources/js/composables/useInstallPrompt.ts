import { onMounted, onUnmounted, ref } from 'vue';

/**
 * Gestione installazione PWA.
 *
 * - Chrome/Android/desktop: intercetta `beforeinstallprompt`, espone
 *   `canInstall` + `install()` per un prompt nativo on-demand.
 * - iOS Safari: non emette `beforeinstallprompt`, quindi mostriamo un hint
 *   manuale ("Condividi → Aggiungi a Home") via `showIosHint`.
 * - Il dismiss è persistito in localStorage: niente banner martellante.
 */
type BeforeInstallPromptEvent = Event & {
    prompt: () => Promise<void>;
    userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
};

const DISMISS_KEY = 'pwa-install-dismissed';

export function useInstallPrompt() {
    const deferredPrompt = ref<BeforeInstallPromptEvent | null>(null);
    const canInstall = ref(false);
    const showIosHint = ref(false);
    const dismissed = ref(false);

    function onBeforeInstallPrompt(event: Event): void {
        event.preventDefault();
        deferredPrompt.value = event as BeforeInstallPromptEvent;
        canInstall.value = !dismissed.value;
    }

    function onInstalled(): void {
        canInstall.value = false;
        showIosHint.value = false;
        deferredPrompt.value = null;
    }

    async function install(): Promise<void> {
        if (!deferredPrompt.value) {
            return;
        }

        await deferredPrompt.value.prompt();
        await deferredPrompt.value.userChoice;
        deferredPrompt.value = null;
        canInstall.value = false;
    }

    function dismiss(): void {
        localStorage.setItem(DISMISS_KEY, '1');
        dismissed.value = true;
        canInstall.value = false;
        showIosHint.value = false;
    }

    onMounted(() => {
        // window/localStorage letti qui (non al setup) per non rompere un
        // eventuale render SSR del layout.
        dismissed.value = localStorage.getItem(DISMISS_KEY) === '1';

        const isStandalone =
            window.matchMedia('(display-mode: standalone)').matches ||
            (window.navigator as { standalone?: boolean }).standalone === true;
        const isIos = /iphone|ipad|ipod/i.test(window.navigator.userAgent);

        // iOS: nessun evento beforeinstallprompt, decidiamo subito se mostrare
        // l'hint manuale.
        showIosHint.value = isIos && !isStandalone && !dismissed.value;

        window.addEventListener('beforeinstallprompt', onBeforeInstallPrompt);
        window.addEventListener('appinstalled', onInstalled);
    });

    onUnmounted(() => {
        window.removeEventListener('beforeinstallprompt', onBeforeInstallPrompt);
        window.removeEventListener('appinstalled', onInstalled);
    });

    return { canInstall, showIosHint, install, dismiss };
}
