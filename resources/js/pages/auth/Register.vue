<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import FormField from '@/components/forms/FormField.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
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
        description: 'Pochi dati per iniziare. Compili il profilo professionale al primo accesso.',
    },
});
</script>

<template>
    <Head title="Registrati" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-5"
    >
        <FormField label="Nome" for="name" required>
            <Input
                id="name"
                type="text"
                required
                autofocus
                :tabindex="1"
                autocomplete="name"
                name="name"
                placeholder="Es. Mario Rossi"
            />
            <template v-if="errors.name" #error>{{ errors.name }}</template>
        </FormField>

        <FormField label="Email" for="email" required>
            <Input
                id="email"
                type="email"
                required
                :tabindex="2"
                autocomplete="email"
                name="email"
                placeholder="nome@studio.it"
            />
            <template v-if="errors.email" #error>{{ errors.email }}</template>
        </FormField>

        <FormField label="Password" for="password" required>
            <PasswordInput
                id="password"
                required
                :tabindex="3"
                autocomplete="new-password"
                name="password"
                :passwordrules="passwordRules"
            />
            <template v-if="errors.password" #error>{{ errors.password }}</template>
        </FormField>

        <FormField label="Conferma password" for="password_confirmation" required>
            <PasswordInput
                id="password_confirmation"
                required
                :tabindex="4"
                autocomplete="new-password"
                name="password_confirmation"
                :passwordrules="passwordRules"
            />
            <template v-if="errors.password_confirmation" #error>{{ errors.password_confirmation }}</template>
        </FormField>

        <Button
            type="submit"
            class="w-full"
            tabindex="5"
            :disabled="processing"
            data-test="register-user-button"
        >
            <Spinner v-if="processing" />
            Crea account
        </Button>

        <p class="text-center text-xs text-muted-foreground">
            Hai già un account?
            <TextLink :href="login()" :tabindex="6">Accedi</TextLink>
        </p>
    </Form>
</template>
