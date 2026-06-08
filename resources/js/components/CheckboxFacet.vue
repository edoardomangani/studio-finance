<script setup lang="ts">
/**
 * CheckboxFacet — una faccetta filtro multi-select (checkbox group).
 *
 * Convenzione faceted: nessuna spunta = nessun filtro (niente voce "Tutti").
 * v-model è l'array dei valori selezionati. Usato nei pannelli filtri
 * (fatture, scadenze) dentro [[FilterPanel]].
 */
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';

type FacetValue = string | number;

defineProps<{
    title: string;
    options: { value: FacetValue; label: string }[];
    /** Prefisso id per a11y (label↔checkbox); deve essere unico nella pagina. */
    idPrefix: string;
}>();

const selected = defineModel<FacetValue[]>({ required: true });

function toggle(value: FacetValue, checked: boolean): void {
    selected.value = checked
        ? [...selected.value, value]
        : selected.value.filter((v) => v !== value);
}
</script>

<template>
    <div>
        <h3 class="kicker mb-2">{{ title }}</h3>
        <div class="flex flex-col gap-1">
            <div
                v-for="opt in options"
                :key="String(opt.value)"
                class="flex items-center gap-2"
            >
                <Checkbox
                    :id="`${idPrefix}-${opt.value}`"
                    :model-value="selected.includes(opt.value)"
                    @update:model-value="
                        (checked) => toggle(opt.value, checked === true)
                    "
                />
                <Label
                    :for="`${idPrefix}-${opt.value}`"
                    class="cursor-pointer text-13 font-normal"
                >
                    {{ opt.label }}
                </Label>
            </div>
        </div>
    </div>
</template>
