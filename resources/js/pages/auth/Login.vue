<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import FormField from '@/components/forms/FormField.vue';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-5"
    >
        <FormField label="Email" for="email" required>
            <Input
                id="email"
                type="email"
                name="email"
                required
                autofocus
                :tabindex="1"
                autocomplete="email"
                placeholder="nome@studio.it"
            />
            <template v-if="errors.email" #error>{{ errors.email }}</template>
        </FormField>

        <FormField label="Password" for="password" required>
            <PasswordInput
                id="password"
                name="password"
                required
                :tabindex="2"
                autocomplete="current-password"
            />
            <template v-if="errors.password" #error>{{ errors.password }}</template>
        </FormField>

        <div class="flex items-center justify-between">
            <Label for="remember" class="flex items-center gap-2.5 text-13">
                <Checkbox id="remember" name="remember" :tabindex="3" />
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
            :disabled="processing"
            data-test="login-button"
        >
            <Spinner v-if="processing" />
            Accedi
        </Button>

        <p class="text-center text-xs text-muted-foreground">
            Non hai ancora un account?
            <TextLink :href="register()" :tabindex="5">Registrati</TextLink>
        </p>
    </Form>
</template>
