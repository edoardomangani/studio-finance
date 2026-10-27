<script setup lang="ts">
/**
 * Sezione Notifiche della pagina Impostazioni: stato push del dispositivo
 * corrente con attiva / disattiva / prova. Logica in [[usePushSubscription]].
 */
import { PhBell, PhBellSlash, PhPaperPlaneTilt } from '@phosphor-icons/vue';
import { computed } from 'vue';
import FormSection from '@/components/forms/FormSection.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { usePushSubscription } from '@/composables/usePushSubscription';

const { state, busy, activate, deactivate, sendTest } = usePushSubscription();

const statusText = computed<string | null>(() => {
    switch (state.value) {
        case 'unsupported':
            return 'Questo browser non supporta le notifiche push.';
        case 'ios-not-installed':
            return "Per ricevere le notifiche su iPhone aggiungi StudioFinance alla schermata Home: Condividi → Aggiungi alla schermata Home, poi apri l'app da lì.";
        case 'denied':
            return 'Le notifiche sono bloccate per questa app. Riattivale dalle impostazioni del dispositivo.';
        case 'inactive':
            return 'Notifiche non attive su questo dispositivo.';
        case 'active':
            return 'Notifiche attive su questo dispositivo.';
        default:
            return null;
    }
});
</script>

<template>
    <FormSection id="notifications" title="Notifiche">
        <div class="flex flex-col gap-1.5">
            <p class="text-13 text-muted-foreground">
                Ricevi un promemoria 14, 7, 3 e 1 giorno prima di ogni scadenza,
                il giorno stesso e ogni giorno finché resta aperta dopo la data.
            </p>
            <p
                v-if="statusText"
                class="text-13 text-foreground"
                role="status"
                aria-live="polite"
            >
                {{ statusText }}
            </p>
        </div>

        <template v-if="state === 'inactive' || state === 'active'" #actions>
            <template v-if="state === 'inactive'">
                <Button
                    type="button"
                    size="sm"
                    :disabled="busy !== null"
                    :aria-busy="busy === 'activate'"
                    @click="activate"
                >
                    <Spinner v-if="busy === 'activate'" />
                    <PhBell v-else :size="14" />
                    Attiva notifiche
                </Button>
            </template>
            <template v-else>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    :disabled="busy !== null"
                    :aria-busy="busy === 'deactivate'"
                    @click="deactivate"
                >
                    <Spinner v-if="busy === 'deactivate'" />
                    <PhBellSlash v-else :size="14" />
                    Disattiva
                </Button>
                <Button
                    type="button"
                    size="sm"
                    :disabled="busy !== null"
                    :aria-busy="busy === 'test'"
                    @click="sendTest"
                >
                    <Spinner v-if="busy === 'test'" />
                    <PhPaperPlaneTilt v-else :size="14" />
                    Invia notifica di prova
                </Button>
            </template>
        </template>
    </FormSection>
</template>
