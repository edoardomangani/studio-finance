<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { computed, ref, watchEffect } from 'vue';
import FormField from '@/components/forms/FormField.vue';
import { Button } from '@/components/ui/button';
import { FieldError } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/two-factor/login';
import type { TwoFactorConfigContent } from '@/types';

const showRecoveryInput = ref<boolean>(false);
const code = ref<string>('');

const authConfigContent = computed<TwoFactorConfigContent>(() => {
    if (showRecoveryInput.value) {
        return {
            title: 'Codice di recupero',
            description:
                "Inserisci uno dei codici di recupero salvati per accedere all'account.",
            buttonText: "Usa il codice dell'app autenticatore",
        };
    }

    return {
        title: 'Codice a due fattori',
        description:
            'Inserisci il codice mostrato dalla tua app di autenticazione.',
        buttonText: 'Usa un codice di recupero',
    };
});

watchEffect(() => {
    setLayoutProps({
        title: authConfigContent.value.title,
        description: authConfigContent.value.description,
    });
});

const toggleRecoveryMode = (clearErrors: () => void): void => {
    showRecoveryInput.value = !showRecoveryInput.value;
    clearErrors();
    code.value = '';
};
</script>

<template>
    <Head title="Autenticazione a due fattori" />

    <Form
        v-bind="store.form()"
        class="flex flex-col gap-5"
        reset-on-error
        @error="code = ''"
        #default="{ errors, processing, clearErrors }"
    >
        <template v-if="!showRecoveryInput">
            <input type="hidden" name="code" :value="code" />

            <div class="flex flex-col items-center gap-3">
                <InputOTP
                    id="otp"
                    v-model="code"
                    :maxlength="6"
                    :disabled="processing"
                    autofocus
                >
                    <InputOTPGroup>
                        <InputOTPSlot
                            v-for="index in 6"
                            :key="index"
                            :index="index - 1"
                        />
                    </InputOTPGroup>
                </InputOTP>
                <FieldError v-if="errors.code" :errors="[errors.code]" />
            </div>
        </template>

        <template v-else>
            <FormField label="Codice di recupero" for="recovery_code" required>
                <Input
                    id="recovery_code"
                    name="recovery_code"
                    type="text"
                    placeholder="xxxxxxxx-xxxxxxxx"
                    :autofocus="showRecoveryInput"
                    required
                />
                <template v-if="errors.recovery_code" #error>{{
                    errors.recovery_code
                }}</template>
            </FormField>
        </template>

        <Button type="submit" class="w-full" :disabled="processing">
            <Spinner v-if="processing" />
            Continua
        </Button>

        <p class="text-center text-xs text-muted-foreground">
            <button
                type="button"
                class="text-foreground underline decoration-border underline-offset-4 transition-colors hover:decoration-foreground"
                @click="() => toggleRecoveryMode(clearErrors)"
            >
                {{ authConfigContent.buttonText }}
            </button>
        </p>
    </Form>
</template>
