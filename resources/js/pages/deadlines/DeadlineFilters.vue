<script setup lang="ts">
/**
 * DeadlineFilters — contenuto puro dei filtri della lista scadenze.
 *
 * Componente "dumb": renderizzato sia nell'aside inline desktop sia nello
 * Sheet mobile (vedi [[deadlines/Index.vue]]). v-model sull'intero oggetto
 * filtri; la navigazione Inertia la triggera il parent.
 */
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import type { DeadlineFilterState, DeadlineKind, EnumOption } from '@/types';

defineProps<{
    kindOptions: EnumOption[];
    availableYears: number[];
}>();

const modelValue = defineModel<DeadlineFilterState>({ required: true });

function setKind(value: string): void {
    modelValue.value = {
        ...modelValue.value,
        kind: value === '' ? null : (value as DeadlineKind),
    };
}

function setYear(value: string): void {
    modelValue.value = {
        ...modelValue.value,
        year: value === '' ? null : Number(value),
    };
}
</script>

<template>
    <div class="space-y-6">
        <div>
            <h3 class="kicker mb-2">Tipo</h3>
            <RadioGroup
                :model-value="modelValue.kind ?? ''"
                class="gap-1"
                @update:model-value="(v) => setKind(String(v ?? ''))"
            >
                <div class="flex items-center gap-2">
                    <RadioGroupItem id="flt-kind-all" value="" />
                    <Label for="flt-kind-all" class="cursor-pointer text-13 font-normal">Tutti</Label>
                </div>
                <div v-for="o in kindOptions" :key="o.value" class="flex items-center gap-2">
                    <RadioGroupItem :id="`flt-kind-${o.value}`" :value="o.value" />
                    <Label :for="`flt-kind-${o.value}`" class="cursor-pointer text-13 font-normal">
                        {{ o.label }}
                    </Label>
                </div>
            </RadioGroup>
        </div>

        <div>
            <h3 class="kicker mb-2">Anno</h3>
            <RadioGroup
                :model-value="modelValue.year === null ? '' : String(modelValue.year)"
                class="gap-1"
                @update:model-value="(v) => setYear(String(v ?? ''))"
            >
                <div class="flex items-center gap-2">
                    <RadioGroupItem id="flt-year-all" value="" />
                    <Label for="flt-year-all" class="cursor-pointer text-13 font-normal">Tutti</Label>
                </div>
                <div v-for="y in availableYears" :key="y" class="flex items-center gap-2">
                    <RadioGroupItem :id="`flt-year-${y}`" :value="String(y)" />
                    <Label :for="`flt-year-${y}`" class="tabular cursor-pointer text-13 font-normal">
                        {{ y }}
                    </Label>
                </div>
            </RadioGroup>
        </div>
    </div>
</template>
