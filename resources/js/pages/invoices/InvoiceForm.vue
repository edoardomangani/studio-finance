<script setup lang="ts">
/**
 * InvoiceForm — partial condiviso tra Create.vue e Edit.vue.
 *
 * 3 sezioni (FormSection): Anagrafica (numero + data), Cliente (ClientPicker
 * con crea-inline), Importi (4 campi + switch ritenuta + summary bar).
 *
 * Reattività:
 * - useInvoiceTotals: totale + ritenuta + netto computed + auto-fill di
 *   cassa/bollo finché l'utente non li tocca esplicitamente. Cassa = (imponibile
 *   + bollo) × 4%, in linea con la prassi Inarcassa più conservativa (vedi RB3).
 * - ClientPicker.select propaga `bank_withholding` del cliente come default
 *   sovrascrivibile.
 * - DateField: nativo su mobile, Popover + Calendar shadcn su desktop.
 *
 * Submit programmatico: la pagina parent (Create/Edit) renderizza il
 * bottone Salva nel topbar via Teleport con `form="invoice-form"`. Il form
 * stesso usa `@submit.prevent="submit"` come fallback (es. Enter dentro un
 * input).
 */
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController';
import ClientPicker from '@/components/ClientPicker.vue';
import DateField from '@/components/forms/DateField.vue';
import FormField from '@/components/forms/FormField.vue';
import FormSection from '@/components/forms/FormSection.vue';
import { Field, FieldLabel } from '@/components/ui/field';
import { DecimalInput, Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { useInvoiceTotals } from '@/composables/useInvoiceTotals';
import { formatEUR, todayISO } from '@/lib/format';
import type { ClientForPicker, Invoice } from '@/types';

const props = withDefaults(
    defineProps<{
        clients: ClientForPicker[];
        invoice?: Invoice | null;
        preselectedClientId?: number | null;
        // Duplicazione: fattura sorgente da cui precompilare gli importi/flag
        // in creazione. Numero e data NON vengono ereditati (vedi initialPayload).
        sourceInvoice?: Invoice | null;
    }>(),
    { invoice: null, preselectedClientId: null, sourceInvoice: null },
);

const isEditing = computed(() => !!props.invoice);
const isDuplicating = computed(() => !props.invoice && !!props.sourceInvoice);
// In edit e in duplicazione gli importi/flag arrivano già fissati da una
// fattura esistente: niente auto-fill di cassa/bollo né eredità del flag
// ritenuta dal cliente, che sovrascriverebbero valori voluti.
const prefilledFromExisting = computed(
    () => isEditing.value || isDuplicating.value,
);

type FormPayload = {
    number: string;
    issued_at: string;
    client_id: number | null;
    amount: string;
    inarcassa_amount: string;
    stamp_amount: string;
    stamp_charged_to_client: boolean;
    art_15_amount: string;
    bank_withholding: boolean;
};

function initialPayload(): FormPayload {
    if (props.invoice) {
        return {
            number: props.invoice.number,
            issued_at: props.invoice.issued_at ?? todayISO(),
            client_id: props.invoice.client.id,
            amount: props.invoice.amount.toFixed(2),
            inarcassa_amount: props.invoice.inarcassa_amount.toFixed(2),
            stamp_amount: props.invoice.stamp_amount.toFixed(2),
            stamp_charged_to_client:
                props.invoice.stamp_charged_to_client ?? true,
            art_15_amount: props.invoice.art_15_amount.toFixed(2),
            bank_withholding: props.invoice.bank_withholding,
        };
    }

    // Duplicazione: eredita importi/flag dalla sorgente, ma con numero vuoto
    // (è unique per (user, anno): copiarlo collide sempre) e data = oggi.
    if (props.sourceInvoice) {
        return {
            number: '',
            issued_at: todayISO(),
            client_id: props.sourceInvoice.client.id,
            amount: props.sourceInvoice.amount.toFixed(2),
            inarcassa_amount: props.sourceInvoice.inarcassa_amount.toFixed(2),
            stamp_amount: props.sourceInvoice.stamp_amount.toFixed(2),
            stamp_charged_to_client:
                props.sourceInvoice.stamp_charged_to_client ?? true,
            art_15_amount: props.sourceInvoice.art_15_amount.toFixed(2),
            bank_withholding: props.sourceInvoice.bank_withholding,
        };
    }

    return {
        number: '',
        issued_at: todayISO(),
        client_id: props.preselectedClientId ?? null,
        amount: '',
        inarcassa_amount: '0.00',
        stamp_amount: '0.00',
        stamp_charged_to_client: true,
        art_15_amount: '0.00',
        bank_withholding: false,
    };
}

const form = useForm<FormPayload>(initialPayload());

const {
    total,
    withholdingAmount,
    netAmount,
    markInarcassaDirty,
    markStampDirty,
} = useInvoiceTotals(form);

// Valori storati "fissati": dirty=true ab origine per evitare che un cambio
// di imponibile sovrascriva valori reali (edit) o copiati (duplicazione).
if (prefilledFromExisting.value) {
    markInarcassaDirty();
    markStampDirty();
}

const selectedClient = computed<ClientForPicker | null>(() => {
    if (form.client_id === null) {
        return null;
    }

    return props.clients.find((c) => c.id === form.client_id) ?? null;
});

function onClientSelect(client: ClientForPicker): void {
    // In creazione pulita, eredita il flag ritenuta dal cliente. In edit e
    // duplicazione non sovrascriviamo: il flag è già voluto/copiato.
    if (!prefilledFromExisting.value) {
        form.bank_withholding = client.bank_withholding;
    }
}

// Watch su client_id per coprire il caso del deep link `?client=X`:
// `selectedClient` è già un computed sul prop, ma sul primo render con
// preselectedClientId valido vogliamo anche ereditare il flag ritenuta.
watch(
    () => form.client_id,
    (newId) => {
        if (newId === null || !selectedClient.value) {
            return;
        }

        if (!prefilledFromExisting.value) {
            form.bank_withholding = selectedClient.value.bank_withholding;
        }
    },
    { immediate: true },
);

function submit(): void {
    if (isEditing.value && props.invoice) {
        form.patch(
            InvoiceController.update.url({ invoice: props.invoice.id }),
            {
                preserveScroll: true,
            },
        );

        return;
    }

    form.post(InvoiceController.store.url());
}

defineExpose({ processing: computed(() => form.processing) });
</script>

<template>
    <form id="invoice-form" @submit.prevent="submit">
        <FormSection first title="Anagrafica">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <FormField label="Numero fattura" for="invoice-number" required>
                    <Input
                        id="invoice-number"
                        v-model="form.number"
                        placeholder="Es. 2026/001"
                        autocomplete="off"
                        autofocus
                    />
                    <template v-if="form.errors.number" #error>
                        {{ form.errors.number }}
                    </template>
                </FormField>

                <FormField
                    label="Data emissione"
                    for="invoice-issued-at"
                    required
                >
                    <DateField
                        id="invoice-issued-at"
                        v-model="form.issued_at"
                    />
                    <template v-if="form.errors.issued_at" #error>
                        {{ form.errors.issued_at }}
                    </template>
                </FormField>
            </div>
        </FormSection>

        <FormSection title="Cliente">
            <FormField label="Cliente" for="invoice-client" required>
                <ClientPicker
                    id="invoice-client"
                    v-model="form.client_id"
                    :clients="clients"
                    :invalid="!!form.errors.client_id"
                    @select="onClientSelect"
                />
                <template v-if="form.errors.client_id" #error>
                    {{ form.errors.client_id }}
                </template>
            </FormField>

            <!-- Hint: solo informazioni NON già visibili nel trigger. P.IVA è
                 nel trigger del picker, qui aggiungiamo solo lo stato della
                 ritenuta quando applicabile. -->
            <p
                v-if="selectedClient?.bank_withholding"
                class="-mt-2 text-xs text-muted-foreground"
            >
                Cliente soggetto a ritenuta bancaria di default.
            </p>
        </FormSection>

        <FormSection title="Importi">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
                <FormField label="Imponibile (€)" for="invoice-amount" required>
                    <DecimalInput
                        id="invoice-amount"
                        v-model="form.amount"
                        :min="-9999999.99"
                        :max="9999999.99"
                        class="tabular text-right"
                        placeholder="0,00"
                    />
                    <template v-if="form.errors.amount" #error>
                        {{ form.errors.amount }}
                    </template>
                </FormField>

                <!-- Bollo PRIMA della cassa: ordine di calcolo. Cassa è
                     derivata da (imponibile + bollo). -->
                <FormField label="Bollo" for="invoice-stamp">
                    <DecimalInput
                        id="invoice-stamp"
                        v-model="form.stamp_amount"
                        :min="-9999999.99"
                        :max="9999999.99"
                        class="tabular text-right"
                        @input="markStampDirty"
                    />
                    <template v-if="form.errors.stamp_amount" #error>
                        {{ form.errors.stamp_amount }}
                    </template>
                </FormField>

                <FormField label="Cassa Inarcassa 4%" for="invoice-inarcassa">
                    <DecimalInput
                        id="invoice-inarcassa"
                        v-model="form.inarcassa_amount"
                        :min="-9999999.99"
                        :max="9999999.99"
                        class="tabular text-right"
                        @input="markInarcassaDirty"
                    />
                    <template v-if="form.errors.inarcassa_amount" #error>
                        {{ form.errors.inarcassa_amount }}
                    </template>
                </FormField>

                <FormField label="Art.15 (anticipate)" for="invoice-art15">
                    <DecimalInput
                        id="invoice-art15"
                        v-model="form.art_15_amount"
                        :min="-9999999.99"
                        :max="9999999.99"
                        class="tabular text-right"
                    />
                    <template v-if="form.errors.art_15_amount" #error>
                        {{ form.errors.art_15_amount }}
                    </template>
                </FormField>
            </div>

            <Field orientation="horizontal" class="pt-2">
                <Switch
                    id="invoice-stamp-charged"
                    v-model="form.stamp_charged_to_client"
                />
                <FieldLabel for="invoice-stamp-charged" class="font-normal">
                    Bollo a carico del cliente
                </FieldLabel>
            </Field>

            <Field orientation="horizontal">
                <Switch
                    id="invoice-withholding"
                    v-model="form.bank_withholding"
                />
                <FieldLabel for="invoice-withholding" class="font-normal">
                    Soggetta a ritenuta bancaria
                </FieldLabel>
            </Field>

            <!-- Summary bar: prominente, separata visivamente dagli input.
                 Niente card box ridondante: bordo top sottile + label
                 kicker + numeri tabular grandi. -->
            <div class="mt-4 border-t border-border pt-4" aria-live="polite">
                <dl class="space-y-1.5">
                    <div class="flex items-baseline justify-between">
                        <dt class="kicker text-muted-foreground">
                            Totale fattura
                        </dt>
                        <dd class="tabular text-lg font-medium text-foreground">
                            {{ formatEUR(total) }}
                        </dd>
                    </div>

                    <!-- Bollo a carico studio: fuori dal totale ma sempre
                         dovuto (resta nei bolli da pagare). -->
                    <div
                        v-if="
                            !form.stamp_charged_to_client &&
                            Number(form.stamp_amount) > 0
                        "
                        class="text-13 text-muted-foreground"
                    >
                        Bollo {{ formatEUR(Number(form.stamp_amount)) }} a tuo
                        carico — escluso dal totale, ma resta tra i bolli da
                        pagare.
                    </div>

                    <template v-if="form.bank_withholding">
                        <div
                            class="flex items-baseline justify-between text-13"
                        >
                            <dt class="text-muted-foreground">
                                Ritenuta bancaria
                            </dt>
                            <dd class="tabular text-muted-foreground">
                                − {{ formatEUR(withholdingAmount) }}
                            </dd>
                        </div>

                        <div
                            class="flex items-baseline justify-between border-t border-border-soft pt-1.5"
                        >
                            <dt class="kicker text-foreground">
                                Netto a incassare
                            </dt>
                            <dd
                                class="tabular text-13 font-medium text-foreground"
                            >
                                {{ formatEUR(netAmount) }}
                            </dd>
                        </div>
                    </template>
                </dl>
            </div>
        </FormSection>
    </form>
</template>
