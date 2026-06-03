<script setup lang="ts">
/**
 * AnnualExpensePicker — combobox autocomplete per imputare un pagamento
 * manuale (F8) a una spesa annuale, tra tutti gli anni esistenti. Filtra
 * nome + anno in client-side (volumi piccoli: poche spese per anno).
 *
 * Nessun inline-create: le spese annuali nascono dal wizard di apertura anno,
 * non si creano da qui. Specchia [[ClientPicker]] senza il sentinel "Crea".
 */
import { PhArrowRight, PhCheck } from '@phosphor-icons/vue';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Combobox,
    ComboboxAnchor,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxList,
    ComboboxTrigger,
} from '@/components/ui/combobox';
import type { AnnualExpenseForPicker } from '@/types';

const props = defineProps<{
    annualExpenses: AnnualExpenseForPicker[];
    id?: string;
    invalid?: boolean;
}>();

const selectedId = defineModel<number | null>({ default: null });

const searchTerm = ref('');
const open = ref(false);

// Reset del search a ogni apertura: reka-ui può scrivere l'id selezionato
// dentro l'input alla riapertura (vedi ClientPicker).
watch(open, (isOpen) => {
    if (isOpen) {
        searchTerm.value = '';
    }
});

const selected = computed<AnnualExpenseForPicker | null>(() =>
    selectedId.value !== null
        ? (props.annualExpenses.find((e) => e.id === selectedId.value) ?? null)
        : null,
);

const filtered = computed<AnnualExpenseForPicker[]>(() => {
    const q = searchTerm.value.toLowerCase().trim();

    if (!q) {
        return props.annualExpenses;
    }

    return props.annualExpenses.filter(
        (e) => e.name.toLowerCase().includes(q) || String(e.year).includes(q),
    );
});

function handleUpdate(value: unknown): void {
    const id = typeof value === 'number' ? value : null;
    selectedId.value = id;
    searchTerm.value = '';
}
</script>

<template>
    <Combobox
        :model-value="selectedId ?? undefined"
        v-model:open="open"
        @update:model-value="handleUpdate"
    >
        <ComboboxAnchor class="w-full">
            <ComboboxTrigger as-child>
                <Button
                    :id="id"
                    type="button"
                    variant="outline"
                    class="w-full justify-between font-normal"
                    :class="invalid ? 'border-destructive' : ''"
                >
                    <span :class="selected ? '' : 'text-muted-foreground/70'">
                        {{ selected?.name ?? 'Cerca spesa…' }}
                    </span>
                    <div class="flex items-center">
                        <span v-if="selected" class="ml-2 tabular text-xs text-muted-foreground">
                            {{ selected.year }}
                        </span>
                        <PhArrowRight class="ml-2 size-3.5 rotate-90 opacity-50" />
                    </div>
                </Button>
            </ComboboxTrigger>
        </ComboboxAnchor>

        <ComboboxList class="w-(--reka-combobox-trigger-width)">
            <div class="relative">
                <ComboboxInput
                    v-model="searchTerm"
                    :display-value="() => ''"
                    placeholder="Cerca per nome o anno…"
                />
            </div>

            <ComboboxEmpty>Nessuna spesa trovata.</ComboboxEmpty>

            <ComboboxGroup v-if="filtered.length > 0">
                <ComboboxItem
                    v-for="e in filtered"
                    :key="e.id"
                    :value="e.id"
                    class="flex items-center justify-between gap-2"
                >
                    <span>{{ e.name }}</span>
                    <div class="flex items-center gap-2">
                        <span class="tabular text-xs text-muted-foreground">{{ e.year }}</span>
                        <ComboboxItemIndicator>
                            <PhCheck class="size-3.5" />
                        </ComboboxItemIndicator>
                    </div>
                </ComboboxItem>
            </ComboboxGroup>
        </ComboboxList>
    </Combobox>

    <p v-if="annualExpenses.length === 0" class="mt-1 text-xs text-muted-foreground">
        Nessuna spesa annuale. Apri un anno per generarle.
    </p>
</template>
