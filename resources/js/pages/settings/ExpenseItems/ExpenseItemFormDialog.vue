<script setup lang="ts">
/**
 * ExpenseItemFormDialog — create/edit di una voce di spesa template. `item`
 * null = creazione. I campi numerici pertinenti dipendono dal tipo di calcolo
 * (aliquota/min/max per le percentuali, importo per le fisse). Guscio
 * `ResponsiveDialog` (dialog desktop · bottom sheet mobile).
 */
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import ExpenseItemController from '@/actions/App/Http/Controllers/Settings/ExpenseItemController';
import FormField from '@/components/forms/FormField.vue';
import ResponsiveDialog from '@/components/ResponsiveDialog.vue';
import { Field, FieldGroup, FieldLabel } from '@/components/ui/field';
import { DecimalInput, Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import type {
    EnumOption,
    ExpenseCalculationType,
    ExpenseItem,
    ExpenseKind,
} from '@/types';

const props = defineProps<{
    item: ExpenseItem | null;
    calculationTypes: EnumOption[];
    familyKinds: EnumOption[];
}>();

const open = defineModel<boolean>('open', { default: false });

// Campi numerici `number | string`: input grezzi (no formattazione in edit), il
// vuoto è '' (Laravel ConvertEmptyStringsToNull → null lato server).
type FormPayload = {
    name: string;
    calculation_type: ExpenseCalculationType;
    kind: ExpenseKind;
    default_rate: number | string;
    default_minimum: number | string;
    default_maximum: number | string;
    default_amount: number | string;
    active: boolean;
    position: number;
};

const emptyForm = (): FormPayload => ({
    name: '',
    calculation_type: 'fixed_annual',
    kind: 'fixed',
    default_rate: '',
    default_minimum: '',
    default_maximum: '',
    default_amount: '',
    active: true,
    position: 0,
});

const form = useForm<FormPayload>(emptyForm());

const isPercentage = computed(
    () =>
        form.calculation_type === 'percentage_of_irpef_income' ||
        form.calculation_type === 'percentage_of_iva_revenue',
);
const isFixed = computed(() => form.calculation_type === 'fixed_annual');

const dialogTitle = computed(() =>
    props.item ? 'Modifica voce di spesa' : 'Nuova voce di spesa',
);
const dialogDescription = computed(() =>
    props.item
        ? 'Modifica i default del template. Le istanze già create negli anni esistenti restano invariate.'
        : 'Aggiungi una voce di spesa al catalogo. Verrà proposta nei prossimi anni che apri.',
);

// A ogni apertura: idrata dai valori della voce (edit) o azzera (create).
watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    form.clearErrors();
    const item = props.item;
    form.defaults(
        item
            ? {
                  name: item.name,
                  calculation_type: item.calculation_type,
                  kind: item.kind,
                  default_rate: item.default_rate ?? '',
                  default_minimum: item.default_minimum ?? '',
                  default_maximum: item.default_maximum ?? '',
                  default_amount: item.default_amount ?? '',
                  active: item.active,
                  position: item.position,
              }
            : emptyForm(),
    );
    form.reset();
});

function onSubmit(): void {
    if (!isPercentage.value) {
        form.default_rate = '';
        form.default_minimum = '';
        form.default_maximum = '';
    }

    if (!isFixed.value) {
        form.default_amount = '';
    }

    const onSuccess = () => {
        open.value = false;
    };

    if (props.item) {
        form.patch(
            ExpenseItemController.update.url({ expenseItem: props.item.id }),
            { preserveScroll: true, onSuccess },
        );
    } else {
        form.post(ExpenseItemController.store.url(), {
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
        submit-form="expense-item-form"
        :submit-label="item ? 'Salva modifiche' : 'Aggiungi voce'"
        :submitting="form.processing"
    >
        <form id="expense-item-form" @submit.prevent="onSubmit">
            <FieldGroup>
                <FormField label="Nome" for="item-name" required>
                    <Input
                        id="item-name"
                        v-model="form.name"
                        placeholder="Es. Imposta sostitutiva"
                    />
                    <template v-if="form.errors.name" #error>{{
                        form.errors.name
                    }}</template>
                </FormField>

                <FormField label="Tipo di calcolo" for="item-type" required>
                    <Select v-model="form.calculation_type">
                        <SelectTrigger id="item-type" class="w-full">
                            <SelectValue placeholder="Seleziona…" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="opt in calculationTypes"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <template v-if="form.errors.calculation_type" #error>{{
                        form.errors.calculation_type
                    }}</template>
                </FormField>

                <FormField label="Famiglia" for="item-kind" required>
                    <Select v-model="form.kind">
                        <SelectTrigger id="item-kind" class="w-full">
                            <SelectValue placeholder="Seleziona…" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="opt in familyKinds"
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

                <div
                    v-if="isPercentage"
                    class="grid grid-cols-1 gap-4 md:grid-cols-3"
                >
                    <FormField label="Aliquota (%)" for="item-rate">
                        <DecimalInput
                            id="item-rate"
                            v-model="form.default_rate"
                            :min="0"
                            :max="100"
                            class="tabular text-right"
                            placeholder="0,00"
                        />
                        <template v-if="form.errors.default_rate" #error>{{
                            form.errors.default_rate
                        }}</template>
                    </FormField>

                    <FormField label="Minimale (€)" for="item-min">
                        <DecimalInput
                            id="item-min"
                            v-model="form.default_minimum"
                            :min="0"
                            class="tabular text-right"
                            placeholder="0,00"
                        />
                        <template v-if="form.errors.default_minimum" #error>{{
                            form.errors.default_minimum
                        }}</template>
                    </FormField>

                    <FormField label="Massimale (€)" for="item-max">
                        <DecimalInput
                            id="item-max"
                            v-model="form.default_maximum"
                            :min="0"
                            class="tabular text-right"
                            placeholder="0,00"
                        />
                        <template v-if="form.errors.default_maximum" #error>{{
                            form.errors.default_maximum
                        }}</template>
                    </FormField>
                </div>

                <FormField
                    v-if="isFixed"
                    label="Importo annuale (€)"
                    for="item-amount"
                >
                    <DecimalInput
                        id="item-amount"
                        v-model="form.default_amount"
                        :min="0"
                        class="tabular text-right"
                        placeholder="0,00"
                    />
                    <template v-if="form.errors.default_amount" #error>{{
                        form.errors.default_amount
                    }}</template>
                </FormField>

                <Field orientation="horizontal">
                    <Switch id="item-active" v-model="form.active" />
                    <FieldLabel for="item-active" class="font-normal">
                        Voce attiva (proposta nei nuovi anni)
                    </FieldLabel>
                </Field>
            </FieldGroup>
        </form>
    </ResponsiveDialog>
</template>
