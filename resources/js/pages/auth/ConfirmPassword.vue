<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    index as confirmOptions,
    store as confirmStore,
} from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
import FormField from '@/components/forms/FormField.vue';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/password/confirm';

defineOptions({
    layout: {
        title: 'Conferma password',
        description:
            "Stai accedendo a un'area sensibile. Conferma la password per continuare.",
    },
});

const form = useForm({ password: '' });

function submit(): void {
    form.post(store().url, {
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <Head title="Conferma password" />

    <PasskeyVerify
        :routes="{
            options: confirmOptions(),
            submit: confirmStore(),
        }"
        label="Conferma con passkey"
        loading-label="Sto confermando…"
        separator="oppure conferma con password"
    />

    <form @submit.prevent="submit">
        <FieldGroup>
            <FormField label="Password" for="password" required>
                <PasswordInput
                    id="password"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    autofocus
                />
                <template v-if="form.errors.password" #error>{{
                    form.errors.password
                }}</template>
            </FormField>

            <Button
                type="submit"
                class="w-full"
                :disabled="form.processing"
                data-test="confirm-password-button"
            >
                <Spinner v-if="form.processing" />
                Conferma password
            </Button>
        </FieldGroup>
    </form>
</template>
