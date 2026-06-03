<script setup lang="ts">
/**
 * DeadlineFilters — campi filtro della lista scadenze (dentro [[FilterPanel]]).
 *
 * Tipo come radio (poche opzioni); anno di riferimento, anno scadenza e voce
 * di spesa come Select (compatti). Lo stato vive nel toggle dell'index, non
 * qui. v-model sull'intero oggetto filtri; la navigazione la triggera il parent.
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
import type { DeadlineFilterState, DeadlineKind, EnumOption } from '@/types';

defineProps<{
    kindOptions: EnumOption[];
    availableYears: number[];
    availableDueYears: number[];
    expenseItems: { id: number; name: string }[];
}>();

const modelValue = defineModel<DeadlineFilterState>({ required: true });

// reka Select non ammette value="" → sentinel 'all' per "nessun filtro".
const ALL = 'all';

function setKind(value: string): void {
    modelValue.value = { ...modelValue.value, kind: value === '' ? null : (value as DeadlineKind) };
}

function setYear(value: unknown): void {
    modelValue.value = { ...modelValue.value, year: value === ALL || value == null ? null : Number(value) };
}

function setDueYear(value: unknown): void {
    modelValue.value = { ...modelValue.value, dueYear: value === ALL || value == null ? null : Number(value) };
}

function setExpenseItem(value: unknown): void {
    modelValue.value = { ...modelValue.value, expenseItemId: value === ALL || value == null ? null : Number(value) };
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
            <h3 class="kicker mb-2">Anno di riferimento</h3>
            <Select
                :model-value="modelValue.year === null ? ALL : String(modelValue.year)"
                @update:model-value="setYear"
            >
                <SelectTrigger class="w-full">
                    <SelectValue placeholder="Tutti gli anni" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ALL">Tutti gli anni</SelectItem>
                    <SelectItem v-for="y in availableYears" :key="y" :value="String(y)">{{ y }}</SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div>
            <h3 class="kicker mb-2">Anno scadenza</h3>
            <Select
                :model-value="modelValue.dueYear === null ? ALL : String(modelValue.dueYear)"
                @update:model-value="setDueYear"
            >
                <SelectTrigger class="w-full">
                    <SelectValue placeholder="Tutti gli anni" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ALL">Tutti gli anni</SelectItem>
                    <SelectItem v-for="y in availableDueYears" :key="y" :value="String(y)">{{ y }}</SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div>
            <h3 class="kicker mb-2">Voce di spesa</h3>
            <Select
                :model-value="modelValue.expenseItemId === null ? ALL : String(modelValue.expenseItemId)"
                @update:model-value="setExpenseItem"
            >
                <SelectTrigger class="w-full">
                    <SelectValue placeholder="Tutte le voci" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ALL">Tutte le voci</SelectItem>
                    <SelectItem v-for="item in expenseItems" :key="item.id" :value="String(item.id)">
                        {{ item.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>
    </div>
</template>
