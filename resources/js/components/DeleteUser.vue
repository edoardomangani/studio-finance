<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, useTemplateRef, watch } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import FormField from '@/components/forms/FormField.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogBody,
    DialogContent,
    DialogStandardFooter,
    DialogStandardHeader,
    DialogTrigger,
} from '@/components/ui/dialog';
import { FieldGroup } from '@/components/ui/field';

const passwordInput = useTemplateRef('passwordInput');
const open = ref(false);

const form = useForm({ password: '' });

function submit(): void {
    form.delete(ProfileController.destroy.url(), {
        preserveScroll: true,
        onError: () => passwordInput.value?.focus(),
    });
}

// Reset al chiudere il dialog (anche via ESC / click esterno).
watch(open, (isOpen) => {
    if (!isOpen) {
        form.reset();
        form.clearErrors();
    }
});
</script>

<template>
    <div class="space-y-3">
        <header>
            <h3 class="section-title">Elimina account</h3>
            <p class="mt-1.5 text-xs leading-relaxed text-muted-foreground">
                L'azione è irreversibile: account, profilo professionale,
                fatture, clienti, scadenze e tutti i dati associati vengono
                eliminati definitivamente.
            </p>
        </header>

        <Dialog v-model:open="open">
            <DialogTrigger as-child>
                <Button
                    variant="destructive"
                    size="sm"
                    data-test="delete-user-button"
                >
                    Elimina account
                </Button>
            </DialogTrigger>
            <DialogContent size="mini">
                <DialogStandardHeader
                    title="Eliminare l'account?"
                    description="Una volta eliminato l'account, tutti i dati associati vengono rimossi in modo definitivo. Inserisci la password per confermare."
                    :closable="false"
                />
                <DialogBody>
                    <form id="delete-user-form" @submit.prevent="submit">
                        <FieldGroup>
                            <FormField label="Password attuale" for="delete-account-password" required>
                                <PasswordInput
                                    id="delete-account-password"
                                    ref="passwordInput"
                                    v-model="form.password"
                                    autocomplete="current-password"
                                />
                                <template v-if="form.errors.password" #error>{{ form.errors.password }}</template>
                            </FormField>
                        </FieldGroup>
                    </form>
                </DialogBody>
                <DialogStandardFooter>
                    <Button
                        type="submit"
                        form="delete-user-form"
                        variant="destructive"
                        :disabled="form.processing"
                        data-test="confirm-delete-user-button"
                    >
                        Elimina definitivamente
                    </Button>
                </DialogStandardFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
