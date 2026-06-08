<script setup lang="ts">
/**
 * DeadlineFilters — campi filtro della lista scadenze (dentro [[FilterPanel]]).
 *
 * Faccette multi-select via [[CheckboxFacet]] (nessuna spunta = nessun filtro):
 * tipo, anno di riferimento, anno scadenza, voce di spesa. Lo stato vive nel
 * toggle dell'index. v-model sull'intero oggetto filtri.
 */
import { computed } from 'vue';
import CheckboxFacet from '@/components/CheckboxFacet.vue';
import type { DeadlineFilterState, EnumOption } from '@/types';

const props = defineProps<{
    kindOptions: EnumOption[];
    availableYears: number[];
    availableDueYears: number[];
    expenseItems: { id: number; name: string }[];
}>();

const modelValue = defineModel<DeadlineFilterState>({ required: true });

const yearOptions = computed(() =>
    props.availableYears.map((y) => ({ value: y, label: String(y) })),
);
const dueYearOptions = computed(() =>
    props.availableDueYears.map((y) => ({ value: y, label: String(y) })),
);
const expenseOptions = computed(() =>
    props.expenseItems.map((i) => ({ value: i.id, label: i.name })),
);

// Riassegna l'intero oggetto (non muta annidato) così il defineModel del
// parent emette e il live-apply desktop scatta.
function setFacet<K extends keyof DeadlineFilterState>(
    key: K,
    values: DeadlineFilterState[K],
): void {
    modelValue.value = { ...modelValue.value, [key]: values };
}
</script>

<template>
    <div class="space-y-6">
        <CheckboxFacet
            title="Tipo"
            id-prefix="flt-kind"
            :options="kindOptions"
            :model-value="modelValue.kind"
            @update:model-value="
                (v) => setFacet('kind', v as DeadlineFilterState['kind'])
            "
        />
        <CheckboxFacet
            title="Anno di riferimento"
            id-prefix="flt-year"
            :options="yearOptions"
            :model-value="modelValue.year"
            @update:model-value="(v) => setFacet('year', v as number[])"
        />
        <CheckboxFacet
            title="Anno scadenza"
            id-prefix="flt-dueyear"
            :options="dueYearOptions"
            :model-value="modelValue.dueYear"
            @update:model-value="(v) => setFacet('dueYear', v as number[])"
        />
        <CheckboxFacet
            title="Voce di spesa"
            id-prefix="flt-voce"
            :options="expenseOptions"
            :model-value="modelValue.expenseItemId"
            @update:model-value="
                (v) => setFacet('expenseItemId', v as number[])
            "
        />
    </div>
</template>
