<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import {
    index as confirmOptions,
    store as confirmStore,
} from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
import FormField from '@/components/forms/FormField.vue';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/password/confirm';

defineOptions({
    layout: {
        title: 'Conferma password',
        description:
            'Stai accedendo a un\'area sensibile. Conferma la password per continuare.',
    },
});
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

    <Form
        v-bind="store.form()"
        reset-on-success
        v-slot="{ errors, processing }"
        class="flex flex-col gap-5"
    >
        <FormField label="Password" for="password" required>
            <PasswordInput
                id="password"
                name="password"
                required
                autocomplete="current-password"
                autofocus
            />
            <template v-if="errors.password" #error>{{ errors.password }}</template>
        </FormField>

        <Button
            type="submit"
            class="w-full"
            :disabled="processing"
            data-test="confirm-password-button"
        >
            <Spinner v-if="processing" />
            Conferma password
        </Button>
    </Form>
</template>
