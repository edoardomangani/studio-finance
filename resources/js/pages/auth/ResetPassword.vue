<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import FormField from '@/components/forms/FormField.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { update } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Nuova password',
        description: 'Scegli una nuova password per il tuo account.',
    },
});

const props = defineProps<{
    token: string;
    email: string;
    passwordRules: string;
}>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

function submit(): void {
    form.post(update().url, {
        onSuccess: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head title="Nuova password" />

    <form @submit.prevent="submit">
        <FieldGroup>
            <FormField label="Email" for="email">
                <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    readonly
                />
                <template v-if="form.errors.email" #error>{{
                    form.errors.email
                }}</template>
            </FormField>

            <FormField label="Nuova password" for="password" required>
                <PasswordInput
                    id="password"
                    v-model="form.password"
                    autocomplete="new-password"
                    autofocus
                    :passwordrules="passwordRules"
                />
                <template v-if="form.errors.password" #error>{{
                    form.errors.password
                }}</template>
            </FormField>

            <FormField
                label="Conferma password"
                for="password_confirmation"
                required
            >
                <PasswordInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    autocomplete="new-password"
                    :passwordrules="passwordRules"
                />
                <template v-if="form.errors.password_confirmation" #error>{{
                    form.errors.password_confirmation
                }}</template>
            </FormField>

            <Button
                type="submit"
                class="w-full"
                :disabled="form.processing"
                data-test="reset-password-button"
            >
                <Spinner v-if="form.processing" />
                Imposta nuova password
            </Button>
        </FieldGroup>
    </form>
</template>
