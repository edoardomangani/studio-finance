<script setup lang="ts">
/**
 * DeadlineFormDialog — crea o modifica una scadenza ad-hoc, e modifica i campi
 * sicuri di una standard (nome, data).
 *
 * Form piatto, un passo → modale ([[ResponsiveDialog]] mode="dialog"). Il tipo
 * (radio-card con descrizione) si sceglie solo in creazione: a creazione fatta
 * è immutabile. La spesa collegata è modificabile solo su una scadenza ad-hoc
 * di pagamento non ancora pagata; altrimenti è mostrata in sola lettura. La
 * registrazione del pagamento avviene poi dal side-sheet, come per le standard.
 */
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import DeadlineController from '@/actions/App/Http/Controllers/DeadlineController';
import AnnualExpensePicker from '@/components/AnnualExpensePicker.vue';
import DateField from '@/components/forms/DateField.vue';
import FormField from '@/components/forms/FormField.vue';
import ResponsiveDialog from '@/components/ResponsiveDialog.vue';
import { ChoiceCardRadio } from '@/components/ui/choice';
import { Field, FieldGroup } from '@/components/ui/field';
import { DecimalInput, Input } from '@/components/ui/input';
import { RadioGroup } from '@/components/ui/radio-group';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type {
    AnnualExpenseForPicker,
    DeadlineKind,
    DeadlineListItem,
    EnumOption,
    YearOption,
} from '@/types';

const props = defineProps<{
    annualExpenses: AnnualExpenseForPicker[];
    yearOptions: YearOption[];
    kindOptions: EnumOption[];
    /** null = creazione; valorizzata = modifica. */
    deadline?: DeadlineListItem | null;
}>();

const open = defineModel<boolean>('open', { default: false });

// Descrizioni dei tipi (copy UI; il backend manda solo value+label).
const KIND_DESCRIPTIONS: Record<DeadlineKind, string> = {
    payment: 'Collega a una spesa e genera un pagamento da registrare.',
    fulfillment: 'Solo promemoria con scadenza, nessun pagamento.',
};

type FormPayload = {
    kind: DeadlineKind;
    name: string;
    due_at: string;
    annual_expense_id: number | null;
    year_id: number | null;
    manual_expected_amount: string;
};

const emptyForm = (): FormPayload => ({
    kind: 'payment',
    name: '',
    due_at: '',
    annual_expense_id: null,
    year_id: null,
    manual_expected_amount: '',
});

const form = useForm<FormPayload>(emptyForm());

const isEditing = computed(() => !!props.deadline);

// La spesa è modificabile solo su una ad-hoc di pagamento non ancora pagata.
const canEditExpense = computed(
    () =>
        !!props.deadline &&
        props.deadline.is_custom &&
        props.deadline.kind === 'payment' &&
        props.deadline.payment?.status !== 'paid',
);

const kindLabel = computed(
    () =>
        props.kindOptions.find((o) => o.value === form.kind)?.label ??
        form.kind,
);

watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    form.clearErrors();

    if (props.deadline) {
        form.defaults({
            kind: props.deadline.kind,
            name: props.deadline.name,
            due_at: props.deadline.due_at,
            annual_expense_id: props.deadline.annual_expense_id,
            year_id: null,
            // Per le ad-hoc expected_amount = previsto manuale salvato.
            manual_expected_amount:
                props.deadline.expected_amount !== null
                    ? String(props.deadline.expected_amount)
                    : '',
        });
    } else {
        form.defaults(emptyForm());
    }

    form.reset();
});

function setKind(value: unknown): void {
    if (value === 'payment' || value === 'fulfillment') {
        form.kind = value;
    }
}

function submit(): void {
    if (props.deadline) {
        // Modifica: nome/data sempre; spesa e previsto solo se lecito.
        form.transform((data) => ({
            name: data.name,
            due_at: data.due_at,
            ...(canEditExpense.value
                ? {
                      annual_expense_id: data.annual_expense_id,
                      manual_expected_amount:
                          data.manual_expected_amount.trim() || null,
                  }
                : {}),
        }));

        form.patch(
            DeadlineController.update.url({ deadline: props.deadline.id }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    open.value = false;
                },
                onError: (errors) => {
                    if (Object.keys(errors).length === 0) {
                        toast.error('Aggiornamento non riuscito. Riprova.');
                    }
                },
            },
        );

        return;
    }

    // Creazione: azzera i campi non pertinenti al tipo.
    form.transform((data) => ({
        ...data,
        annual_expense_id:
            data.kind === 'payment' ? data.annual_expense_id : null,
        year_id: data.kind === 'fulfillment' ? data.year_id : null,
        manual_expected_amount:
            data.kind === 'payment'
                ? data.manual_expected_amount.trim() || null
                : null,
    }));

    form.post(DeadlineController.store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
        onError: (errors) => {
            if (Object.keys(errors).length === 0) {
                toast.error('Creazione non riuscita. Riprova.');
            }
        },
    });
}
</script>

<template>
    <ResponsiveDialog
        v-model:open="open"
        :title="isEditing ? 'Modifica scadenza' : 'Nuova scadenza'"
        :description="
            isEditing
                ? 'Aggiorna la scadenza. Il tipo non cambia dopo la creazione.'
                : 'Aggiungi un obbligo non previsto dalle scadenze tipo. Lo registri poi come tutte le altre.'
        "
        submit-form="deadline-form"
        :submit-label="isEditing ? 'Salva modifiche' : 'Crea scadenza'"
        :submitting="form.processing"
    >
        <form id="deadline-form" @submit.prevent="submit">
            <FieldGroup>
                <!-- Tipo: scelta solo in creazione (poi immutabile). -->
                <FormField
                    v-if="!isEditing"
                    label="Tipo"
                    for="deadline-kind"
                    required
                >
                    <RadioGroup
                        id="deadline-kind"
                        :model-value="form.kind"
                        class="flex gap-2"
                        @update:model-value="setKind"
                    >
                        <ChoiceCardRadio
                            v-for="opt in kindOptions"
                            :key="opt.value"
                            :value="opt.value"
                            :title="opt.label"
                            :description="
                                KIND_DESCRIPTIONS[opt.value as DeadlineKind]
                            "
                        />
                    </RadioGroup>
                </FormField>

                <!-- In modifica il tipo è contesto in sola lettura. -->
                <Field v-else orientation="horizontal" class="justify-between">
                    <span class="text-13 text-muted-foreground">Tipo</span>
                    <span class="text-13 font-medium text-foreground">{{
                        kindLabel
                    }}</span>
                </Field>

                <!-- Spesa: picker quando creabile/modificabile, altrimenti read-only. -->
                <FormField
                    v-if="
                        form.kind === 'payment' &&
                        (!isEditing || canEditExpense)
                    "
                    label="Spesa"
                    for="deadline-expense"
                    required
                >
                    <AnnualExpensePicker
                        id="deadline-expense"
                        v-model="form.annual_expense_id"
                        :annual-expenses="annualExpenses"
                        :invalid="!!form.errors.annual_expense_id"
                    />
                    <template v-if="form.errors.annual_expense_id" #error>{{
                        form.errors.annual_expense_id
                    }}</template>
                </FormField>

                <Field
                    v-else-if="isEditing && deadline?.kind === 'payment'"
                    orientation="horizontal"
                    class="justify-between"
                >
                    <span class="text-13 text-muted-foreground">Spesa</span>
                    <span class="text-13 text-foreground">{{
                        deadline?.annual_expense_name ?? '—'
                    }}</span>
                </Field>

                <!-- Previsto manuale: suggerimento opzionale, solo su ad-hoc di
                     pagamento modificabili (stessa condizione della spesa). -->
                <FormField
                    v-if="
                        form.kind === 'payment' &&
                        (!isEditing || canEditExpense)
                    "
                    label="Importo previsto"
                    for="deadline-expected"
                >
                    <DecimalInput
                        id="deadline-expected"
                        v-model="form.manual_expected_amount"
                        :min="0"
                        placeholder="0,00"
                        class="tabular"
                    />
                    <template #hint
                        >Suggerimento precompilato alla registrazione.
                        Facoltativo.</template
                    >
                    <template
                        v-if="form.errors.manual_expected_amount"
                        #error
                        >{{ form.errors.manual_expected_amount }}</template
                    >
                </FormField>

                <!-- Anno: select solo in creazione di un adempimento. -->
                <FormField
                    v-if="!isEditing && form.kind === 'fulfillment'"
                    label="Anno"
                    for="deadline-year"
                    required
                >
                    <Select v-model.number="form.year_id">
                        <SelectTrigger id="deadline-year" class="w-full">
                            <SelectValue placeholder="Seleziona…" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="y in yearOptions"
                                :key="y.id"
                                :value="y.id"
                            >
                                {{ y.year }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <template v-if="form.errors.year_id" #error>{{
                        form.errors.year_id
                    }}</template>
                </FormField>

                <FormField label="Nome" for="deadline-name" required>
                    <Input
                        id="deadline-name"
                        v-model="form.name"
                        placeholder="Es. Imposta di bollo straordinaria"
                    />
                    <template v-if="form.errors.name" #error>{{
                        form.errors.name
                    }}</template>
                </FormField>

                <FormField
                    label="Data di scadenza"
                    for="deadline-date"
                    required
                >
                    <DateField id="deadline-date" v-model="form.due_at" />
                    <template v-if="form.errors.due_at" #error>{{
                        form.errors.due_at
                    }}</template>
                </FormField>
            </FieldGroup>
        </form>
    </ResponsiveDialog>
</template>
