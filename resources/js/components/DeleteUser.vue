<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref, useTemplateRef, watch } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import FormField from '@/components/forms/FormField.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

const passwordInput = useTemplateRef('passwordInput');
const open = ref(false);
const resetSignal = ref(0);

// Quando il dialog si chiude (anche via ESC / click esterno) resettiamo il
// form via cambio di key sul componente Form. Più solido di un @click su
// DialogClose, che parte prima della chiusura.
watch(open, (isOpen) => {
    if (!isOpen) {
        resetSignal.value++;
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
            <DialogContent>
                <Form
                    :key="resetSignal"
                    v-bind="ProfileController.destroy.form()"
                    reset-on-success
                    @error="() => passwordInput?.focus()"
                    :options="{
                        preserveScroll: true,
                    }"
                    class="space-y-5"
                    v-slot="{ errors, processing }"
                >
                    <DialogHeader>
                        <DialogTitle>Eliminare l'account?</DialogTitle>
                        <DialogDescription>
                            Una volta eliminato l'account, tutti i dati
                            associati vengono rimossi in modo definitivo.
                            Inserisci la password per confermare.
                        </DialogDescription>
                    </DialogHeader>

                    <FormField label="Password attuale" for="delete-account-password" required>
                        <PasswordInput
                            id="delete-account-password"
                            name="password"
                            ref="passwordInput"
                            autocomplete="current-password"
                        />
                        <template v-if="errors.password" #error>{{
                            errors.password
                        }}</template>
                    </FormField>

                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button type="button" variant="ghost">
                                Annulla
                            </Button>
                        </DialogClose>

                        <Button
                            type="submit"
                            variant="destructive"
                            :disabled="processing"
                            data-test="confirm-delete-user-button"
                        >
                            Elimina definitivamente
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>
