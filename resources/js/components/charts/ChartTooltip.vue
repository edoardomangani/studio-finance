<script setup lang="ts">
/**
 * Tooltip flottante condiviso dei grafici (sparkline, barre): card posizionata
 * in percentuale dentro il contenitore *relativo* del grafico, sopra il punto/
 * barra in hover (sotto se il punto è troppo in alto). Solo chrome + posizione;
 * il contenuto (mese, valori) lo passa il chiamante via slot. `left` viene
 * clampato così la card non esce dai bordi del grafico.
 */
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{ leftPct: number; topPct: number; below?: boolean }>(),
    { below: false },
);

const left = computed(() => Math.min(90, Math.max(10, props.leftPct)));
</script>

<template>
    <div
        class="pointer-events-none absolute z-10 flex -translate-x-1/2 items-baseline gap-1.5 rounded-md border border-border bg-card px-2 py-1 text-2xs whitespace-nowrap shadow-sm"
        :class="below ? 'translate-y-2' : '-translate-y-[calc(100%+0.5rem)]'"
        :style="{ left: `${left}%`, top: `${topPct}%` }"
    >
        <slot />
    </div>
</template>
