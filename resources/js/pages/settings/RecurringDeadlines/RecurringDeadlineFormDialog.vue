<script setup lang="ts">
/**
 * RecurringDeadlineFormDialog — create/edit di una scadenza tipo. `deadline`
 * null = creazione. Kind `payment` richiede voce collegata + offset anno spesa
 * + tipo quota; `fulfillment` non ha collegamenti. Guscio `ResponsiveDialog`
 * (dialog desktop · bottom sheet mobile).
 */
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import RecurringDeadlineController from '@/actions/App/Http/Controllers/Settings/RecurringDeadlineController';
import FormField from '@/components/forms/FormField.vue';
import ResponsiveDialog from '@/components/ResponsiveDialog.vue';
import { Field, FieldGroup, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    NumberField,
    NumberFieldContent,
    NumberFieldDecrement,
    NumberFieldIncrement,
    NumberFieldInput,
} from '@/components/ui/number-field';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import type {
    DeadlineKind,
    DueYearOffset,
    EnumOption,
    ExpenseYearOffset,
    QuotaType,
    RecurringDeadline,
} from '@/types';

const props = defineProps<{
    deadline: RecurringDeadline | null;
    kinds: EnumOption[];
    dueYearOffsets: EnumOption[];
    expenseYearOffsets: EnumOption[];
    quotaTypes: EnumOption[];
    activeExpenseItems: { id: number; name: string }[];
}>();

const open = defineModel<boolean>('open', { default: false });

type FormPayload = {
    name: string;
    day: number;
    month: number;
    kind: DeadlineKind;
    expense_item_id: number | null;
    due_year_offset: DueYearOffset;
    expense_year_offset: ExpenseYearOffset;
    quota_type: QuotaType | null;
    active: boolean;
};

const emptyForm = (): FormPayload => ({
    name: '',
    day: 30,
    month: 6,
    kind: 'payment',
    expense_item_id: null,
    due_year_offset: 'current',
    expense_year_offset: 'current',
    quota_type: null,
    active: true,
});

const MONTH_LABELS = [
    'Gennaio',
    'Febbraio',
    'Marzo',
    'Aprile',
    'Maggio',
    'Giugno',
    'Luglio',
    'Agosto',
    'Settembre',
    'Ottobre',
    'Novembre',
    'Dicembre',
];

const form = useForm<FormPayload>(emptyForm());

const isPayment = computed(() => form.kind === 'payment');

const dialogTitle = computed(() =>
    props.deadline ? 'Modifica scadenza tipo' : 'Nuova scadenza tipo',
);
const dialogDescription = computed(() =>
    props.deadline
        ? 'Modifica i parametri della scadenza. Le istanze già create negli anni esistenti restano invariate.'
        : 'Aggiungi una scadenza ricorrente al catalogo. Verrà generata nei prossimi anni che apri.',
);

// Adempimento (fulfillment) non paga nulla: azzera i collegamenti al pagamento.
watch(
    () => form.kind,
    (nextKind) => {
        if (nextKind === 'fulfillment') {
            form.expense_item_id = null;
            form.quota_type = null;
        }
    },
);

// A ogni apertura: idrata dalla scadenza (edit) o azzera (create).
watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    form.clearErrors();
    const d = props.deadline;
    form.defaults(
        d
            ? {
                  name: d.name,
                  day: d.day,
                  month: d.month,
                  kind: d.kind,
                  expense_item_id: d.expense_item_id,
                  due_year_offset: d.due_year_offset,
                  expense_year_offset: d.expense_year_offset,
                  quota_type: d.quota_type,
                  active: d.active,
              }
            : emptyForm(),
    );
    form.reset();
});

function onSubmit(): void {
    const onSuccess = () => {
        open.value = false;
    };

    if (props.deadline) {
        form.patch(
            RecurringDeadlineController.update.url({
                recurringDeadline: props.deadline.id,
            }),
            { preserveScroll: true, onSuccess },
        );
    } else {
        form.post(RecurringDeadlineController.store.url(), {
            preserveScroll: true,
            onSuccess,
        });
    }
}
</script>

<template>
    <ResponsiveDialog
        v-model:open="open"
        :title="dialogTitle"
        :description="dialogDescription"
        submit-form="deadline-form"
        :submit-label="deadline ? 'Salva modifiche' : 'Aggiungi scadenza'"
        :submitting="form.processing"
    >
        <form id="deadline-form" @submit.prevent="onSubmit">
            <FieldGroup>
                <FormField label="Nome" for="d-name" required>
                    <Input
                        id="d-name"
                        v-model="form.name"
                        placeholder="Es. Saldo imposta sostitutiva"
                    />
                    <template v-if="form.errors.name" #error>{{
                        form.errors.name
                    }}</template>
                </FormField>

                <div class="grid grid-cols-2 gap-4">
                    <FormField label="Giorno" for="d-day" required>
                        <NumberField
                            id="d-day"
                            v-model="form.day"
                            :min="1"
                            :max="31"
                            :step="1"
                            :format-options="{ maximumFractionDigits: 0 }"
                        >
                            <NumberFieldContent>
                                <NumberFieldDecrement />
                                <NumberFieldInput class="tabular" />
                                <NumberFieldIncrement />
                            </NumberFieldContent>
                        </NumberField>
                        <template v-if="form.errors.day" #error>{{
                            form.errors.day
                        }}</template>
                    </FormField>

                    <FormField label="Mese" for="d-month" required>
                        <Select v-model.number="form.month">
                            <SelectTrigger id="d-month" class="w-full">
                                <SelectValue placeholder="Seleziona…" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="(label, idx) in MONTH_LABELS"
                                    :key="idx"
                                    :value="idx + 1"
                                >
                                    {{ label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <template v-if="form.errors.month" #error>{{
                            form.errors.month
                        }}</template>
                    </FormField>
                </div>

                <FormField label="Tipo" for="d-kind" required>
                    <Select v-model="form.kind">
                        <SelectTrigger id="d-kind" class="w-full">
                            <SelectValue placeholder="Seleziona…" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="opt in kinds"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <template v-if="form.errors.kind" #error>{{
                        form.errors.kind
                    }}</template>
                </FormField>

                <FormField
                    label="Anno della scadenza"
                    for="d-due-year"
                    required
                    hint="Cade nello stesso anno del wizard o nell'anno successivo (es. saldo IS, bolli Q4)."
                >
                    <Select v-model="form.due_year_offset">
                        <SelectTrigger id="d-due-year" class="w-full">
                            <SelectValue placeholder="Seleziona…" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="opt in dueYearOffsets"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <template v-if="form.errors.due_year_offset" #error>{{
                        form.errors.due_year_offset
                    }}</template>
                </FormField>

                <FormField
                    v-if="isPayment"
                    label="Voce di spesa collegata"
                    for="d-item"
                    required
                >
                    <Select
                        v-model.number="form.expense_item_id"
                        :disabled="activeExpenseItems.length === 0"
                    >
                        <SelectTrigger id="d-item" class="w-full">
                            <SelectValue placeholder="Seleziona una voce…" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="item in activeExpenseItems"
                                :key="item.id"
                                :value="item.id"
                            >
                                {{ item.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <template v-if="activeExpenseItems.length === 0" #hint>
                        Nessuna voce di spesa attiva. Vai a Voci di spesa per
                        crearne una prima di collegarla a un pagamento.
                    </template>
                    <template v-if="form.errors.expense_item_id" #error>{{
                        form.errors.expense_item_id
                    }}</template>
                </FormField>

                <FormField
                    v-if="isPayment"
                    label="Anno di riferimento spesa"
                    for="d-expense-year"
                    required
                    hint="Imposta «successivo» per scadenze che pagano la spesa dell'anno N+1 (es. parcella commercialista a dicembre)."
                >
                    <Select v-model="form.expense_year_offset">
                        <SelectTrigger id="d-expense-year" class="w-full">
                            <SelectValue placeholder="Seleziona…" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="opt in expenseYearOffsets"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <template v-if="form.errors.expense_year_offset" #error>{{
                        form.errors.expense_year_offset
                    }}</template>
                </FormField>

                <FormField
                    v-if="isPayment"
                    label="Tipo quota"
                    for="d-quota"
                    hint="Determina l'importo previsto suggerito alla registrazione del pagamento. Lascia vuoto per nessun suggerimento."
                >
                    <Select
                        :model-value="form.quota_type ?? 'none'"
                        @update:model-value="
                            (v) =>
                                (form.quota_type =
                                    v === 'none' ? null : (v as QuotaType))
                        "
                    >
                        <SelectTrigger id="d-quota" class="w-full">
                            <SelectValue placeholder="Nessuno" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="none">Nessuno</SelectItem>
                            <SelectItem
                                v-for="opt in quotaTypes"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <template v-if="form.errors.quota_type" #error>{{
                        form.errors.quota_type
                    }}</template>
                </FormField>

                <Field orientation="horizontal">
                    <Switch id="d-active" v-model="form.active" />
                    <FieldLabel for="d-active" class="font-normal">
                        Scadenza attiva (generata nei nuovi anni)
                    </FieldLabel>
                </Field>
            </FieldGroup>
        </form>
    </ResponsiveDialog>
</template>
