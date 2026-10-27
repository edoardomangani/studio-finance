import { useHttp, usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';
import PushSubscriptionController from '@/actions/App/Http/Controllers/Settings/PushSubscriptionController';
import { isIos, isStandalone } from '@/lib/pwa';

/**
 * Stato Web Push del dispositivo corrente + attiva/disattiva/prova.
 *
 * - `ios-not-installed`: iOS espone le push solo alla PWA aperta da Home.
 * - `unsupported`: niente API push o service worker non registrato (dev).
 */
export type PushState =
    | 'checking'
    | 'unsupported'
    | 'ios-not-installed'
    | 'denied'
    | 'inactive'
    | 'active';

const ERROR_MESSAGE = "Non è stato possibile completare l'operazione. Riprova.";

// La chiave VAPID arriva in base64url, pushManager vuole i byte.
function decodeVapidKey(key: string): Uint8Array<ArrayBuffer> {
    const padded = (key + '='.repeat((4 - (key.length % 4)) % 4))
        .replace(/-/g, '+')
        .replace(/_/g, '/');

    return Uint8Array.from(atob(padded), (char) => char.charCodeAt(0));
}

function supportsPush(): boolean {
    return (
        'serviceWorker' in navigator &&
        'PushManager' in window &&
        'Notification' in window
    );
}

async function currentSubscription(): Promise<PushSubscription | null> {
    const registration = await navigator.serviceWorker.getRegistration();

    return registration ? registration.pushManager.getSubscription() : null;
}

export function usePushSubscription() {
    const page = usePage();
    const state = ref<PushState>('checking');
    const busy = ref(false);

    const http = useHttp({
        endpoint: '',
        keys: { p256dh: '', auth: '' },
        contentEncoding: 'aes128gcm',
    });

    async function saveSubscription(
        subscription: PushSubscription,
    ): Promise<void> {
        const json = subscription.toJSON();

        http.endpoint = json.endpoint ?? '';
        http.keys = {
            p256dh: json.keys?.p256dh ?? '',
            auth: json.keys?.auth ?? '',
        };
        await http.post(PushSubscriptionController.store.url());
    }

    async function detect(): Promise<void> {
        if (isIos() && !isStandalone()) {
            state.value = 'ios-not-installed';

            return;
        }

        if (
            !supportsPush() ||
            !(await navigator.serviceWorker.getRegistration())
        ) {
            state.value = 'unsupported';

            return;
        }

        if (Notification.permission === 'denied') {
            state.value = 'denied';

            return;
        }

        const subscription = await currentSubscription();
        state.value = subscription ? 'active' : 'inactive';

        // Riallinea il server (es. DB ripulito): upsert idempotente, silenzioso.
        if (subscription) {
            saveSubscription(subscription).catch(() => {});
        }
    }

    async function activate(): Promise<boolean> {
        busy.value = true;

        try {
            // iOS: requestPermission va chiamato subito dentro il gesto.
            const permission = await Notification.requestPermission();

            if (permission !== 'granted') {
                state.value = permission === 'denied' ? 'denied' : 'inactive';

                return false;
            }

            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: decodeVapidKey(
                    page.props.webPush?.publicKey ?? '',
                ),
            });

            await saveSubscription(subscription);
            state.value = 'active';
            toast.success('Notifiche attivate su questo dispositivo');

            return true;
        } catch {
            toast.error(ERROR_MESSAGE);

            return false;
        } finally {
            busy.value = false;
        }
    }

    async function deactivate(): Promise<void> {
        busy.value = true;

        try {
            const subscription = await currentSubscription();

            if (subscription) {
                await subscription.unsubscribe();
                http.endpoint = subscription.endpoint;
                await http.delete(PushSubscriptionController.destroy.url());
            }

            state.value = 'inactive';
            toast.success('Notifiche disattivate su questo dispositivo');
        } catch {
            toast.error(ERROR_MESSAGE);
        } finally {
            busy.value = false;
        }
    }

    async function sendTest(): Promise<void> {
        busy.value = true;

        try {
            await http.post(PushSubscriptionController.test.url());
            toast.success('Notifica di prova inviata');
        } catch {
            toast.error(ERROR_MESSAGE);
        } finally {
            busy.value = false;
        }
    }

    onMounted(() => {
        detect().catch(() => {
            state.value = 'unsupported';
        });
    });

    return { state, busy, activate, deactivate, sendTest };
}
