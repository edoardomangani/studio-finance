<script setup lang="ts">
/**
 * Barra di copertura: quota "pagato" (accent forte) + quota "resto" da
 * versare/accantonare (accent linea) su un totale di riferimento. Clamp 0–100;
 * quando il pagato copre tutto il totale la barra è piena e il resto sparisce.
 * Usata dalla banda KPI dell'anno e dalla dashboard generale.
 */
import { computed } from 'vue';

const props = defineProps<{
    paid: number;
    /** Quota ancora da versare/accantonare (≤ 0 se sovra-pagato → resto a zero). */
    rest: number;
    total: number;
}>();

const paidPct = computed<number>(() => {
    const total = props.total > 0 ? props.total : 1;

    return Math.min(100, Math.max(0, (props.paid / total) * 100));
});

const restPct = computed<number>(() => {
    const total = props.total > 0 ? props.total : 1;

    return Math.min(100 - paidPct.value, Math.max(0, (props.rest / total) * 100));
});
</script>

<template>
    <div class="flex h-2 overflow-hidden rounded-full bg-muted">
        <span class="h-full bg-accent-strong" :style="{ width: `${paidPct}%` }" />
        <span class="h-full bg-accent-line" :style="{ width: `${restPct}%` }" />
    </div>
</template>
