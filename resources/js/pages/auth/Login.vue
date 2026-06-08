<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import FormField from '@/components/forms/FormField.vue';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { FieldGroup } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Accedi',
        description: 'Inserisci email e password per entrare nel tuo account.',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false as boolean,
});

function submit(): void {
    form.post(store().url, {
        onSuccess: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Accedi" />

    <div
        v-if="status"
        class="text-center text-xs font-medium text-accent-strong"
    >
        {{ status }}
    </div>

    <PasskeyVerify />

    <form @submit.prevent="submit">
        <FieldGroup>
            <FormField label="Email" for="email" required>
                <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    autofocus
                    :tabindex="1"
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
                    :tabindex="2"
                    autocomplete="current-password"
                />
                <template v-if="form.errors.password" #error>{{
                    form.errors.password
                }}</template>
            </FormField>

            <div class="flex items-center justify-between">
                <Label for="remember" class="flex items-center gap-2.5 text-13">
                    <Checkbox
                        id="remember"
                        v-model="form.remember"
                        :tabindex="3"
                    />
                    <span>Ricordami su questo dispositivo</span>
                </Label>
                <TextLink
                    v-if="canResetPassword"
                    :href="request()"
                    class="text-xs"
                    :tabindex="5"
                >
                    Password dimenticata?
                </TextLink>
            </div>

            <Button
                type="submit"
                class="w-full"
                :tabindex="4"
                :disabled="form.processing"
                data-test="login-button"
            >
                <Spinner v-if="form.processing" />
                Accedi
            </Button>
        </FieldGroup>

        <p class="mt-5 text-center text-xs text-muted-foreground">
            Non hai ancora un account?
            <TextLink :href="register()" :tabindex="5">Registrati</TextLink>
        </p>
    </form>
</template>
