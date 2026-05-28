<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import FormField from '@/components/forms/FormField.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Password dimenticata',
        description: 'Inserisci l\'email del tuo account. Ti invieremo un link per reimpostare la password.',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Password dimenticata" />

    <div
        v-if="status"
        class="text-center text-xs font-medium text-accent-strong"
    >
        {{ status }}
    </div>

    <Form
        v-bind="email.form()"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-5"
    >
        <FormField label="Email" for="email" required>
            <Input
                id="email"
                type="email"
                name="email"
                autocomplete="email"
                autofocus
                placeholder="nome@studio.it"
            />
            <template v-if="errors.email" #error>{{ errors.email }}</template>
        </FormField>

        <Button
            type="submit"
            class="w-full"
            :disabled="processing"
            data-test="email-password-reset-link-button"
        >
            <Spinner v-if="processing" />
            Invia link di reset
        </Button>

        <p class="text-center text-xs text-muted-foreground">
            <TextLink :href="login()">Torna al login</TextLink>
        </p>
    </Form>
</template>
