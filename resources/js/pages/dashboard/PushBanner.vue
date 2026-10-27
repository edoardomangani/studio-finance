<script setup lang="ts">
/**
 * Invito una tantum ad attivare le notifiche push, solo nella PWA installata
 * con permesso ancora da chiedere. Chiuso o attivato → sparisce per sempre
 * su questo dispositivo.
 */
import { PhBell, PhX } from '@phosphor-icons/vue';
import { computed, onMounted, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { usePushSubscription } from '@/composables/usePushSubscription';
import { isStandalone } from '@/lib/pwa';

const DISMISS_KEY = 'sf:push-banner-dismissed';

const { state, busy, activate } = usePushSubscription();
const dismissed = ref(true);

onMounted(() => {
    try {
        dismissed.value = localStorage.getItem(DISMISS_KEY) === '1';
    } catch {
        dismissed.value = false;
    }
});

const visible = computed(
    () =>
        !dismissed.value &&
        state.value === 'inactive' &&
        isStandalone() &&
        Notification.permission === 'default',
);

function dismiss(): void {
    dismissed.value = true;

    try {
        localStorage.setItem(DISMISS_KEY, '1');
    } catch {
        // Storage bloccato: il banner resta chiuso solo per questa sessione.
    }
}

async function onActivate(): Promise<void> {
    if (await activate()) {
        dismiss();
    }
}
</script>

<template>
    <div
        v-if="visible"
        class="flex items-center gap-2.5 rounded-lg border border-border bg-card px-4 py-3"
    >
        <div
            class="flex size-7 shrink-0 items-center justify-center rounded-md bg-accent-vivid/10 text-accent-vivid"
        >
            <PhBell :size="16" weight="bold" />
        </div>
        <span class="flex-1 text-13 text-foreground">
            Ricevi un promemoria prima di ogni scadenza
        </span>
        <Button size="sm" :disabled="busy" @click="onActivate">
            <Spinner v-if="busy" />
            Attiva
        </Button>
        <Button
            variant="ghost"
            size="icon-sm"
            aria-label="Chiudi"
            class="shrink-0"
            @click="dismiss"
        >
            <PhX :size="14" />
        </Button>
    </div>
</template>
