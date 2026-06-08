<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        title: 'Verifica email',
        description:
            "Apri il link che abbiamo inviato al tuo indirizzo email per confermare l'account.",
    },
});

defineProps<{
    status?: string;
}>();

// Case 3 (action-only): niente <form>, solo bottone che lancia il POST.
const form = useForm({});
</script>

<template>
    <Head title="Verifica email" />

    <div
        v-if="status === 'verification-link-sent'"
        class="text-center text-xs font-medium text-accent-strong"
    >
        Abbiamo inviato un nuovo link di verifica all'indirizzo email utilizzato
        per la registrazione.
    </div>

    <div class="flex flex-col items-center gap-4">
        <Button
            type="button"
            variant="outline"
            :disabled="form.processing"
            @click="form.post(send().url)"
        >
            <Spinner v-if="form.processing" />
            Reinvia email di verifica
        </Button>

        <TextLink :href="logout()" as="button" class="text-xs"> Esci </TextLink>
    </div>
</template>
