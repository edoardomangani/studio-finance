<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import FormField from '@/components/forms/FormField.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'Crea il tuo account',
        description:
            'Pochi dati per iniziare. Compili il profilo professionale al primo accesso.',
    },
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function submit(): void {
    form.post(store().url, {
        onSuccess: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head title="Registrati" />

    <form @submit.prevent="submit">
        <FieldGroup>
            <FormField label="Nome" for="name" required>
                <Input
                    id="name"
                    v-model="form.name"
                    type="text"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="name"
                    placeholder="Es. Mario Rossi"
                />
                <template v-if="form.errors.name" #error>{{
                    form.errors.name
                }}</template>
            </FormField>

            <FormField label="Email" for="email" required>
                <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    :tabindex="2"
                    autocomplete="email"
                    placeholder="nome@studio.it"
                />
                <template v-if="form.errors.email" #error>{{
                    form.errors.email
                }}</template>
            </FormField>

            <FormField label="Password" for="password" required>
                <PasswordInput
                    id="password"
                    v-model="form.password"
                    required
                    :tabindex="3"
                    autocomplete="new-password"
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
                    required
                    :tabindex="4"
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
                tabindex="5"
                :disabled="form.processing"
                data-test="register-user-button"
            >
                <Spinner v-if="form.processing" />
                Crea account
            </Button>
        </FieldGroup>

        <p class="mt-5 text-xs text-muted-foreground">
            Hai già un account?
            <TextLink :href="login()" :tabindex="6">Accedi</TextLink>
        </p>
    </form>
</template>
