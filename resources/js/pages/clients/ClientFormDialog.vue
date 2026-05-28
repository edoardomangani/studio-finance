<script setup lang="ts">
/**
 * ClientFormDialog — dialog di create/edit cliente.
 *
 * Riutilizzato sia in Index (nuova creazione) sia in Show (modifica) sia da
 * ClientPicker dentro il form fattura (modalità inline). Il caller passa
 * `client` (null = nuovo) e gestisce open/close via v-model.
 *
 * Modalità inline: quando `inline=true` (uso da ClientPicker), invia
 * `_inline: true` al server; il controller, invece di redirect a
 * /clients/{id}, fa `back()` e flasha `new_client_id`. Il parent legge il
 * flash per auto-selezionare il cliente appena creato. `initialName`
 * precompila il nome (es. dal testo digitato nel picker).
 *
 * Validazione cross-field: almeno uno tra P.IVA e CF (lato server via
 * `required_without`; lato UI mostriamo l'errore sotto entrambi i campi).
 */
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import ClientController from '@/actions/App/Http/Controllers/ClientController';
import FormField from '@/components/forms/FormField.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogBody,
    DialogContent,
    DialogStandardFooter,
    DialogStandardHeader,
} from '@/components/ui/dialog';
import { Field, FieldGroup, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import type { Client } from '@/types';

const props = withDefaults(
    defineProps<{
        client?: Client | null;
        /** Quando true, dopo il submit resta sulla pagina corrente e flasha
         *  `new_client_id` (uso da ClientPicker). Default false: il
         *  controller redirige a /clients/{id}. */
        inline?: boolean;
        /** Precompila il campo "Denominazione" quando si crea da zero. */
        initialName?: string;
    }>(),
    { inline: false, initialName: '' },
);

const emit = defineEmits<{
    (e: 'created'): void;
}>();

const open = defineModel<boolean>('open', { default: false });

type FormPayload = {
    name: string;
    vat_number: string;
    tax_code: string;
    bank_withholding: boolean;
    notes: string;
};

const emptyForm = (): FormPayload => ({
    name: '',
    vat_number: '',
    tax_code: '',
    bank_withholding: false,
    notes: '',
});

const form = useForm<FormPayload>(emptyForm());

const isEditing = computed(() => !!props.client);
const dialogTitle = computed(() =>
    isEditing.value ? 'Modifica cliente' : 'Nuovo cliente',
);
const dialogDescription = computed(() =>
    isEditing.value
        ? 'Aggiorna i dati anagrafici. Le fatture esistenti restano invariate.'
        : 'Anagrafica cliente. Almeno uno tra P.IVA e Codice Fiscale è obbligatorio.',
);

watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    form.clearErrors();
    const next: FormPayload = props.client
        ? {
              name: props.client.name,
              vat_number: props.client.vat_number ?? '',
              tax_code: props.client.tax_code ?? '',
              bank_withholding: props.client.bank_withholding,
              notes: props.client.notes ?? '',
          }
        : { ...emptyForm(), name: props.initialName ?? '' };
    form.defaults(next);
    form.reset();
});

function submit(): void {
    // Le stringhe vuote di P.IVA/CF/note vanno mandate come null al server,
    // così il vincolo required_without scatta correttamente sul partner.
    // Quando inline=true aggiungiamo `_inline: true` per fare in modo che
    // il controller ritorni back() con flash new_client_id invece di
    // navigare a /clients/{id}.
    form.transform((data) => ({
        ...data,
        vat_number: data.vat_number.trim() || null,
        tax_code: data.tax_code.trim() || null,
        notes: data.notes.trim() || null,
        ...(props.inline ? { _inline: true } : {}),
    }));

    if (isEditing.value && props.client) {
        form.patch(
            ClientController.update.url({ client: props.client.id }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    open.value = false;
                },
            },
        );
    } else {
        form.post(ClientController.store.url(), {
            preserveScroll: props.inline,
            onSuccess: () => {
                open.value = false;

                if (props.inline) {
                    emit('created');
                }
            },
        });
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent size="default">
            <DialogStandardHeader
                :title="dialogTitle"
                :description="dialogDescription"
            />
            <DialogBody>
                <form id="client-form" @submit.prevent="submit">
                    <FieldGroup>
                        <FormField label="Denominazione" for="client-name" required>
                            <Input
                                id="client-name"
                                v-model="form.name"
                                placeholder="Es. Acme Architettura srl"
                                autofocus
                            />
                            <template v-if="form.errors.name" #error>{{ form.errors.name }}</template>
                        </FormField>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <FormField label="P.IVA" for="client-vat">
                                <Input
                                    id="client-vat"
                                    v-model="form.vat_number"
                                    placeholder="IT12345678901"
                                    autocomplete="off"
                                />
                                <template v-if="form.errors.vat_number" #error>{{ form.errors.vat_number }}</template>
                            </FormField>

                            <FormField label="Codice Fiscale" for="client-cf">
                                <Input
                                    id="client-cf"
                                    v-model="form.tax_code"
                                    placeholder="RSSMRA80A01H501Z"
                                    autocomplete="off"
                                />
                                <template v-if="form.errors.tax_code" #error>{{ form.errors.tax_code }}</template>
                            </FormField>
                        </div>

                        <Field orientation="horizontal">
                            <Switch
                                id="client-withholding"
                                v-model="form.bank_withholding"
                            />
                            <FieldLabel for="client-withholding" class="font-normal">
                                Ritenuta bancaria 8%
                            </FieldLabel>
                        </Field>

                        <FormField label="Note" for="client-notes">
                            <Textarea
                                id="client-notes"
                                v-model="form.notes"
                                rows="3"
                                placeholder="Note libere sul cliente."
                            />
                            <template v-if="form.errors.notes" #error>{{ form.errors.notes }}</template>
                        </FormField>
                    </FieldGroup>
                </form>
            </DialogBody>
            <DialogStandardFooter>
                <Button
                    type="submit"
                    form="client-form"
                    :disabled="form.processing"
                >
                    <Spinner v-if="form.processing" />
                    {{ isEditing ? 'Salva modifiche' : 'Aggiungi cliente' }}
                </Button>
            </DialogStandardFooter>
        </DialogContent>
    </Dialog>
</template>
