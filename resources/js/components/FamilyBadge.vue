<script setup lang="ts">
/**
 * Badge della famiglia di una voce di spesa: icona + colore fissi del tipo
 * ([[EXPENSE_KIND_META]]) col nome (rinominabile) della famiglia dell'utente.
 * Usato nelle tabelle spese (vista anno e template).
 */
import { computed } from 'vue';
import { EXPENSE_KIND_META } from '@/lib/expenseKind';
import type { ExpenseKind } from '@/types';

const props = defineProps<{ kind: ExpenseKind; name: string }>();

const meta = computed(() => EXPENSE_KIND_META[props.kind]);
</script>

<template>
    <span
        class="inline-flex w-fit items-center gap-1.5 whitespace-nowrap rounded-sm px-1.5 py-0.5 text-xs font-medium"
        :style="{ color: meta.color, background: `color-mix(in oklch, ${meta.color} 12%, transparent)` }"
    >
        <component :is="meta.icon" :size="12" weight="bold" />
        {{ name }}
    </span>
</template>
