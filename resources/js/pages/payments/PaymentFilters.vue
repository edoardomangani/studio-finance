<script setup lang="ts">
/**
 * PaymentFilters — campi filtro della lista pagamenti (dentro [[FilterPanel]]).
 *
 * Faccette multi-select via [[CheckboxFacet]] (nessuna spunta = nessun filtro):
 * stato, anno di riferimento (spesa), anno della data di cassa. v-model
 * sull'intero oggetto filtri. Specchia [[deadlines/DeadlineFilters]].
 */
import { computed } from 'vue';
import CheckboxFacet from '@/components/CheckboxFacet.vue';
import type { EnumOption, PaymentFilterState, PaymentStatus } from '@/types';

const props = defineProps<{
    statusOptions: EnumOption[];
    availableExpenseYears: number[];
    availablePaidYears: number[];
}>();

const modelValue = defineModel<PaymentFilterState>({ required: true });

const expenseYearOptions = computed(() => props.availableExpenseYears.map((y) => ({ value: y, label: String(y) })));
const paidYearOptions = computed(() => props.availablePaidYears.map((y) => ({ value: y, label: String(y) })));

// Riassegna l'intero oggetto (non muta annidato) così il defineModel del
// parent emette e il live-apply desktop scatta.
function setFacet<K extends keyof PaymentFilterState>(key: K, values: PaymentFilterState[K]): void {
    modelValue.value = { ...modelValue.value, [key]: values };
}
</script>

<template>
    <div class="space-y-6">
        <CheckboxFacet
            title="Stato"
            id-prefix="flt-status"
            :options="statusOptions"
            :model-value="modelValue.status"
            @update:model-value="(v) => setFacet('status', v as PaymentStatus[])"
        />
        <CheckboxFacet
            title="Anno di riferimento"
            id-prefix="flt-exp-year"
            :options="expenseYearOptions"
            :model-value="modelValue.expenseYear"
            @update:model-value="(v) => setFacet('expenseYear', v as number[])"
        />
        <CheckboxFacet
            title="Anno pagamento"
            id-prefix="flt-paid-year"
            :options="paidYearOptions"
            :model-value="modelValue.paidYear"
            @update:model-value="(v) => setFacet('paidYear', v as number[])"
        />
    </div>
</template>
