<script setup lang="ts">
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import { computed, ref, watchEffect } from 'vue';
import FormField from '@/components/forms/FormField.vue';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldGroup } from '@/components/ui/field';
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

const form = useForm({
    code: '',
    recovery_code: '',
});

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

function toggleRecoveryMode(): void {
    showRecoveryInput.value = !showRecoveryInput.value;
    form.clearErrors();
    form.code = '';
    form.recovery_code = '';
}

function submit(): void {
    form.post(store().url, {
        onError: () => {
            form.code = '';
            form.recovery_code = '';
        },
    });
}
</script>

<template>
    <Head title="Autenticazione a due fattori" />

    <form @submit.prevent="submit">
        <FieldGroup>
            <Field v-if="!showRecoveryInput" class="items-center">
                <InputOTP
                    id="otp"
                    v-model="form.code"
                    :maxlength="6"
                    :disabled="form.processing"
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
                <FieldError
                    v-if="form.errors.code"
                    :errors="[form.errors.code]"
                />
            </Field>

            <FormField
                v-else
                label="Codice di recupero"
                for="recovery_code"
                required
            >
                <Input
                    id="recovery_code"
                    v-model="form.recovery_code"
                    type="text"
                    placeholder="xxxxxxxx-xxxxxxxx"
                    :autofocus="showRecoveryInput"
                    required
                />
                <template v-if="form.errors.recovery_code" #error>{{
                    form.errors.recovery_code
                }}</template>
            </FormField>

            <Button type="submit" class="w-full" :disabled="form.processing">
                <Spinner v-if="form.processing" />
                Continua
            </Button>
        </FieldGroup>

        <p class="mt-5 text-center text-xs text-muted-foreground">
            <button
                type="button"
                class="text-foreground underline decoration-border underline-offset-4 transition-colors hover:decoration-foreground"
                @click="toggleRecoveryMode"
            >
                {{ authConfigContent.buttonText }}
            </button>
        </p>
    </form>
</template>
