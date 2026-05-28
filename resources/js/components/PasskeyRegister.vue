<script setup lang="ts">
import { usePasskeyRegister } from '@laravel/passkeys/vue';
import { ref } from 'vue';
import FormField from '@/components/forms/FormField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';

const emit = defineEmits<{
    success: [];
}>();

const getDefaultPasskeyName = () => {
    const ua = navigator.userAgent;

    const browser = ['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera'].find(
        (browser) => new RegExp(browser).test(ua),
    );

    const os = ['iPhone', 'iPad', 'Android', 'Mac', 'Windows'].find((os) =>
        new RegExp(os).test(ua),
    );

    return [browser, os].filter(Boolean).join(' · ') || '';
};

const name = ref(getDefaultPasskeyName());
const showForm = ref(false);

const { register, isLoading, error, isSupported } = usePasskeyRegister({
    onSuccess: () => {
        name.value = '';
        showForm.value = false;
        emit('success');
    },
});

const handleSubmit = async (event: Event) => {
    event.preventDefault();

    if (!name.value.trim()) {
        return;
    }

    await register(name.value);
};

const handleCancel = () => {
    showForm.value = false;
    name.value = '';
};
</script>

<template>
    <p v-if="!isSupported" class="text-xs text-muted-foreground">
        Le passkey non sono supportate da questo browser.
    </p>

    <Button
        v-else-if="!showForm"
        size="sm"
        variant="outline"
        @click="showForm = true"
    >
        Aggiungi passkey
    </Button>

    <form
        v-else
        @submit="handleSubmit"
        class="space-y-4 rounded-md border border-border bg-muted/30 p-4"
    >
        <FormField
            label="Nome passkey"
            for="passkey-name"
            required
            hint="Ti aiuta a riconoscerla in seguito."
            :invalid="!!error"
        >
            <Input
                id="passkey-name"
                type="text"
                v-model="name"
                placeholder="Es. MacBook Pro · iPhone"
                autofocus
            />
            <template v-if="error" #error>{{ error }}</template>
        </FormField>

        <div class="flex gap-2">
            <Button
                type="submit"
                size="sm"
                :disabled="isLoading || !name.trim()"
            >
                <Spinner v-if="isLoading" />
                {{ isLoading ? 'Sto registrando…' : 'Registra passkey' }}
            </Button>
            <Button
                type="button"
                size="sm"
                variant="ghost"
                @click="handleCancel"
            >
                Annulla
            </Button>
        </div>
    </form>
</template>
