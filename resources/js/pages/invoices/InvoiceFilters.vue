<script setup lang="ts">
/**
 * InvoiceFilters — contenuto puro dei filtri della Index fatture.
 *
 * Componente "dumb" senza opinioni sul container: viene renderizzato sia
 * dentro l'aside inline desktop (#page-right-sidebar) sia dentro lo Sheet
 * mobile. La logica di apertura/chiusura del pannello vive nel parent.
 *
 * v-model si lega all'intero oggetto filtri (anno + cliente + ritenuta);
 * il parent triggera la navigazione Inertia quando opportuno (es. su
 * "Applica" o on-change live).
 */
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { ClientForPicker } from '@/types';

export type InvoiceFilterState = {
    year: number | null;
    client_id: number | null;
    withholding: boolean | null;
};

defineProps<{
    availableYears: number[];
    clients: ClientForPicker[];
}>();

const modelValue = defineModel<InvoiceFilterState>({ required: true });

function setYear(value: string): void {
    modelValue.value = {
        ...modelValue.value,
        year: value === '' ? null : Number(value),
    };
}

/* reka-ui Select non ammette value="" (sentinel interno per "no selection").
   Usiamo "all" come sentinel esplicito → null nel modelValue. */
const ALL_CLIENTS = 'all';

function setClient(value: unknown): void {
    if (
        value === ALL_CLIENTS ||
        value === '' ||
        value === undefined ||
        value === null
    ) {
        modelValue.value = { ...modelValue.value, client_id: null };
        return;
    }

    modelValue.value = { ...modelValue.value, client_id: Number(value) };
}

function setWithholding(value: string): void {
    let next: boolean | null = null;

    if (value === 'yes') {
        next = true;
    } else if (value === 'no') {
        next = false;
    }

    modelValue.value = { ...modelValue.value, withholding: next };
}
</script>

<template>
    <div class="space-y-6">
        <!-- Anno -->
        <div>
            <h3 class="kicker mb-2">Anno emissione</h3>
            <RadioGroup
                :model-value="
                    modelValue.year === null ? '' : String(modelValue.year)
                "
                class="gap-1"
                @update:model-value="(v) => setYear(String(v ?? ''))"
            >
                <div class="flex items-center gap-2">
                    <RadioGroupItem id="flt-year-all" value="" />
                    <Label
                        for="flt-year-all"
                        class="cursor-pointer text-13 font-normal"
                    >
                        Tutti
                    </Label>
                </div>
                <div
                    v-for="y in availableYears"
                    :key="y"
                    class="flex items-center gap-2"
                >
                    <RadioGroupItem :id="`flt-year-${y}`" :value="String(y)" />
                    <Label
                        :for="`flt-year-${y}`"
                        class="tabular cursor-pointer text-13 font-normal"
                    >
                        {{ y }}
                    </Label>
                </div>
            </RadioGroup>
        </div>

        <!-- Cliente -->
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

        <!-- Ritenuta tri-state -->
        <div>
            <h3 class="kicker mb-2">Ritenuta</h3>
            <RadioGroup
                :model-value="
                    modelValue.withholding === null
                        ? 'all'
                        : modelValue.withholding
                          ? 'yes'
                          : 'no'
                "
                class="gap-1"
                @update:model-value="(v) => setWithholding(String(v ?? 'all'))"
            >
                <div class="flex items-center gap-2">
                    <RadioGroupItem id="flt-with-all" value="all" />
                    <Label
                        for="flt-with-all"
                        class="cursor-pointer text-13 font-normal"
                    >
                        Tutte
                    </Label>
                </div>
                <div class="flex items-center gap-2">
                    <RadioGroupItem id="flt-with-yes" value="yes" />
                    <Label
                        for="flt-with-yes"
                        class="cursor-pointer text-13 font-normal"
                    >
                        Con ritenuta
                    </Label>
                </div>
                <div class="flex items-center gap-2">
                    <RadioGroupItem id="flt-with-no" value="no" />
                    <Label
                        for="flt-with-no"
                        class="cursor-pointer text-13 font-normal"
                    >
                        Senza ritenuta
                    </Label>
                </div>
            </RadioGroup>
        </div>
    </div>
</template>
