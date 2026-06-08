<script setup lang="ts">
/**
 * InvoiceFilters — campi filtro della Index fatture (dentro [[FilterPanel]]).
 *
 * Anno: faccetta multi-select ([[CheckboxFacet]], nessuna spunta = nessun
 * filtro). Cliente: Select singolo (lista lunga/dinamica, i checkbox non
 * scalano). Ritenuta: tri-state reso con due checkbox (Con / Senza); nessuna
 * o entrambe = tutte. v-model sull'intero oggetto filtri.
 */
import { computed } from 'vue';
import CheckboxFacet from '@/components/CheckboxFacet.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { ClientForPicker } from '@/types';

export type InvoiceFilterState = {
    year: number[];
    client_id: number | null;
    withholding: boolean | null;
};

const props = defineProps<{
    availableYears: number[];
    clients: ClientForPicker[];
}>();

const modelValue = defineModel<InvoiceFilterState>({ required: true });

const yearOptions = computed(() =>
    props.availableYears.map((y) => ({ value: y, label: String(y) })),
);

const ALL_CLIENTS = 'all';

function setYear(values: number[]): void {
    modelValue.value = { ...modelValue.value, year: values };
}

function setClient(value: unknown): void {
    const next =
        value === ALL_CLIENTS || value === '' || value == null
            ? null
            : Number(value);
    modelValue.value = { ...modelValue.value, client_id: next };
}

// Ritenuta tri-state ↔ 2 checkbox. true→['with'], false→['without'], null→[].
const withholdingSelected = computed<string[]>(() => {
    if (modelValue.value.withholding === true) {
        return ['with'];
    }

    if (modelValue.value.withholding === false) {
        return ['without'];
    }

    return [];
});

function setWithholding(values: string[]): void {
    // Solo "with" → true, solo "without" → false, nessuna o entrambe → tutte.
    let next: boolean | null = null;

    if (values.length === 1) {
        next = values[0] === 'with';
    }

    modelValue.value = { ...modelValue.value, withholding: next };
}
</script>

<template>
    <div class="space-y-6">
        <CheckboxFacet
            title="Anno emissione"
            id-prefix="flt-year"
            :options="yearOptions"
            :model-value="modelValue.year"
            @update:model-value="(v) => setYear(v as number[])"
        />

        <div>
            <h3 class="kicker mb-2">Cliente</h3>
            <Select
                :model-value="
                    modelValue.client_id === null
                        ? ALL_CLIENTS
                        : String(modelValue.client_id)
                "
                @update:model-value="setClient"
            >
                <SelectTrigger class="w-full">
                    <SelectValue placeholder="Tutti i clienti" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ALL_CLIENTS"
                        >Tutti i clienti</SelectItem
                    >
                    <SelectItem
                        v-for="c in clients"
                        :key="c.id"
                        :value="String(c.id)"
                    >
                        {{ c.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <CheckboxFacet
            title="Ritenuta"
            id-prefix="flt-withholding"
            :options="[
                { value: 'with', label: 'Con ritenuta' },
                { value: 'without', label: 'Senza ritenuta' },
            ]"
            :model-value="withholdingSelected"
            @update:model-value="(v) => setWithholding(v as string[])"
        />
    </div>
</template>
