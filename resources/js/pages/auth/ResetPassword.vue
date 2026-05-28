<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import FormField from '@/components/forms/FormField.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
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

const inputEmail = ref(props.email);
</script>

<template>
    <Head title="Nuova password" />

    <Form
        v-bind="update.form()"
        :transform="(data) => ({ ...data, token, email })"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-5"
    >
        <FormField label="Email" for="email">
            <Input
                id="email"
                type="email"
                name="email"
                autocomplete="email"
                v-model="inputEmail"
                readonly
            />
            <template v-if="errors.email" #error>{{ errors.email }}</template>
        </FormField>

        <FormField label="Nuova password" for="password" required>
            <PasswordInput
                id="password"
                name="password"
                autocomplete="new-password"
                autofocus
                :passwordrules="passwordRules"
            />
            <template v-if="errors.password" #error>{{ errors.password }}</template>
        </FormField>

        <FormField label="Conferma password" for="password_confirmation" required>
            <PasswordInput
                id="password_confirmation"
                name="password_confirmation"
                autocomplete="new-password"
                :passwordrules="passwordRules"
            />
            <template v-if="errors.password_confirmation" #error>{{ errors.password_confirmation }}</template>
        </FormField>

        <Button
            type="submit"
            class="w-full"
            :disabled="processing"
            data-test="reset-password-button"
        >
            <Spinner v-if="processing" />
            Imposta nuova password
        </Button>
    </Form>
</template>
