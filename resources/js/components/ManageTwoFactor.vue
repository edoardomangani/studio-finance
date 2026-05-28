<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { PhShieldCheck } from '@phosphor-icons/vue';
import { onUnmounted, ref } from 'vue';
import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.vue';
import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.vue';
import { Button } from '@/components/ui/button';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { disable, enable } from '@/routes/two-factor';

export type Props = {
    canManageTwoFactor?: boolean;
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
};

withDefaults(defineProps<Props>(), {
    canManageTwoFactor: false,
    requiresConfirmation: false,
    twoFactorEnabled: false,
});

const { hasSetupData, clearTwoFactorAuthData } = useTwoFactorAuth();
const showSetupModal = ref<boolean>(false);

// Case 3 (action-only): un useForm vuoto per ogni endpoint, niente <form>.
const enableForm = useForm({});
const disableForm = useForm({});

function onEnable(): void {
    enableForm.post(enable().url, {
        onSuccess: () => {
            showSetupModal.value = true;
        },
    });
}

function onDisable(): void {
    disableForm.post(disable().url);
}

onUnmounted(() => clearTwoFactorAuthData());
</script>

<template>
    <div v-if="canManageTwoFactor" class="space-y-3">
        <header>
            <h3 class="section-title">Autenticazione a due fattori</h3>
            <p class="mt-1.5 text-xs leading-relaxed text-muted-foreground">
                Aggiunge un codice temporaneo (TOTP) al login per proteggere
                l'account anche se la password viene compromessa.
            </p>
        </header>

        <div v-if="!twoFactorEnabled">
            <Button v-if="hasSetupData" size="sm" @click="showSetupModal = true">
                <PhShieldCheck />
                Continua configurazione
            </Button>
            <Button
                v-else
                size="sm"
                :disabled="enableForm.processing"
                @click="onEnable"
            >
                Attiva 2FA
            </Button>
        </div>

        <div v-else class="space-y-4">
            <Button
                variant="destructive"
                :disabled="disableForm.processing"
                @click="onDisable"
            >
                Disattiva 2FA
            </Button>

            <TwoFactorRecoveryCodes />
        </div>

        <TwoFactorSetupModal
            v-model:isOpen="showSetupModal"
            :requiresConfirmation="requiresConfirmation"
            :twoFactorEnabled="twoFactorEnabled"
        />
    </div>
</template>
