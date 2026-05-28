<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { PhKey } from '@phosphor-icons/vue';
import { destroy } from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyRegistrationController';
import PasskeyItem from '@/components/PasskeyItem.vue';
import PasskeyRegister from '@/components/PasskeyRegister.vue';
import type { Passkey } from '@/types/auth';

export type Props = {
    canManagePasskeys?: boolean;
    passkeys?: Passkey[];
};

withDefaults(defineProps<Props>(), {
    canManagePasskeys: false,
    passkeys: () => [],
});

const handleDelete = (id: number, onError: () => void) => {
    router.delete(destroy.url(id), {
        preserveScroll: true,
        onError,
    });
};

const handleRegisterSuccess = () => {
    router.reload();
};
</script>

<template>
    <div v-if="canManagePasskeys" class="space-y-3">
        <header>
            <h3 class="section-title">Passkey</h3>
            <p class="mt-1.5 text-xs leading-relaxed text-muted-foreground">
                Accesso senza password tramite dispositivo (Face ID, Touch ID,
                YubiKey, Windows Hello).
            </p>
        </header>

        <div
            v-if="passkeys.length"
            class="overflow-hidden rounded-md border border-border"
        >
            <PasskeyItem
                v-for="passkey in passkeys"
                :key="passkey.id"
                :passkey="passkey"
                @remove="handleDelete"
            />
        </div>

        <div
            v-else
            class="flex items-center gap-3 rounded-md border border-dashed border-border bg-muted/30 px-4 py-3"
        >
            <PhKey class="size-4 shrink-0 text-muted-foreground" />
            <p class="text-xs text-muted-foreground">
                Nessuna passkey registrata.
            </p>
        </div>

        <PasskeyRegister @success="handleRegisterSuccess" />
    </div>
</template>
