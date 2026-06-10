<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import FormField from '@/components/forms/FormField.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { FieldGroup } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Password dimenticata',
        description:
            "Inserisci l'email del tuo account. Ti invieremo un link per reimpostare la password.",
    },
});

defineProps<{
    status?: string;
}>();

const form = useForm({ email: '' });

function submit(): void {
    form.post(email().url);
}
</script>

<template>
    <Head title="Password dimenticata" />

    <div v-if="status" class="text-xs font-medium text-accent-strong">
        {{ status }}
    </div>

    <form @submit.prevent="submit">
        <FieldGroup>
            <FormField label="Email" for="email" required>
                <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    autofocus
                    placeholder="nome@studio.it"
                />
                <template v-if="form.errors.email" #error>{{
                    form.errors.email
                }}</template>
            </FormField>

            <Button
                type="submit"
                class="w-full"
                :disabled="form.processing"
                data-test="email-password-reset-link-button"
            >
                <Spinner v-if="form.processing" />
                Invia link di reset
            </Button>
        </FieldGroup>

        <p class="mt-5 text-xs text-muted-foreground">
            <TextLink :href="login()">Torna al login</TextLink>
        </p>
    </form>
</template>
