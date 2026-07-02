<script setup lang="ts">
/**
 * Andamento anno (dashboard): 12 barre del fatturato dell'anno corrente (petrol)
 * con il fantasma dell'anno precedente dietro, come riferimento da battere — così
 * a inizio anno non si vede il vuoto ma la sagoma dell'anno scorso. Etichetta
 * valore sul mese in corso. Statico: la selezione mese vive sulla pagina anno
 * ([[MonthlyBars]]), qui non serve. Scala comune dal massimo delle due serie.
 */
import { computed, ref } from 'vue';
import ChartTooltip from '@/components/charts/ChartTooltip.vue';
import { formatEUR } from '@/lib/format';
import type { DashboardYearTrendMonth } from '@/types';

const props = defineProps<{
    months: DashboardYearTrendMonth[];
    currentMonth: number | null;
    currentYear: number;
    previousYear: number;
}>();

const MONTHS = [
    'Gen',
    'Feb',
    'Mar',
    'Apr',
    'Mag',
    'Giu',
    'Lug',
    'Ago',
    'Set',
    'Ott',
    'Nov',
    'Dic',
];

const max = computed<number>(() => {
    const vals: number[] = [];

    for (const m of props.months) {
        if (m.current != null) {
            vals.push(m.current);
        }

        if (m.previous != null) {
            vals.push(m.previous);
        }
    }

    return Math.max(...vals, 1);
});

// Il fantasma (e la sua legenda) compaiono solo se l'anno precedente è aperto e
// ha fatturato: niente riferimento promesso ma non disegnato.
const hasPrevious = computed<boolean>(() =>
    props.months.some((m) => m.previous != null),
);

function pct(v: number | null): number {
    return v == null ? 0 : Math.max((v / max.value) * 100, 0);
}

// Hover: card col fatturato corrente + fantasma, ancorata in cima alla barra.
const hoveredIdx = ref<number | null>(null);

const hover = computed(() => {
    const i = hoveredIdx.value;

    if (i === null) {
        return null;
    }

    const m = props.months[i];

    if (m.current == null && m.previous == null) {
        return null;
    }

    const top = 100 - Math.max(pct(m.current), pct(m.previous));

    return {
        leftPct: ((i + 0.5) / props.months.length) * 100,
        topPct: top,
        below: top < 38,
        label: MONTHS[m.month - 1],
        current: m.current,
        previous: m.previous,
    };
});

// "5,6k" compatto per l'etichetta del mese in corso.
function compact(v: number): string {
    return `${(v / 1000).toFixed(1).replace('.', ',')}k`;
}

// Etichetta accessibile della colonna (le barre non sono focusabili): mese +
// fatturato corrente e dell'anno precedente, per lettori di schermo.
function barLabel(m: DashboardYearTrendMonth): string {
    const parts = [MONTHS[m.month - 1]];

    if (m.current != null) {
        parts.push(`${props.currentYear} ${formatEUR(m.current)}`);
    }

    if (m.previous != null) {
        parts.push(`${props.previousYear} ${formatEUR(m.previous)}`);
    }

    return parts.join(' · ');
}
</script>

<template>
    <div class="flex flex-col gap-3">
        <div
            class="relative flex h-[150px] items-end gap-2.5"
            @mouseleave="hoveredIdx = null"
        >
            <div
                v-for="(m, i) in months"
                :key="m.month"
                role="img"
                :aria-label="barLabel(m)"
                class="relative h-full min-w-0 flex-1 rounded"
                :class="hoveredIdx === i ? 'bg-muted/50' : ''"
                @mouseenter="hoveredIdx = i"
            >
                <!-- Fantasma anno precedente: dietro, più largo e tenue. Larghezza
                     centrata con cap (clamp): non cresce oltre il massimo su schermi
                     grandi, si stringe con la colonna ma non sotto il minimo. -->
                <span
                    v-if="m.previous != null"
                    class="absolute bottom-0 left-1/2 w-[clamp(0.875rem,55%,1.5rem)] -translate-x-1/2 rounded-t-sm bg-muted-foreground/25"
                    :style="{ height: `${pct(m.previous)}%` }"
                />
                <!-- Anno corrente: davanti, petrol, più stretto (stesso clamp, scala minore). -->
                <span
                    v-if="m.current != null"
                    class="absolute bottom-0 left-1/2 w-[clamp(0.5rem,34%,0.9375rem)] -translate-x-1/2 rounded-t-sm bg-accent-strong"
                    :style="{ height: `${pct(m.current)}%` }"
                >
                    <span
                        v-if="m.month === currentMonth"
                        class="tabular absolute bottom-full left-1/2 mb-1 -translate-x-1/2 text-[10px] font-semibold whitespace-nowrap text-accent-strong"
                        >{{ compact(m.current) }}</span
                    >
                </span>
            </div>

            <ChartTooltip
                v-if="hover"
                :left-pct="hover.leftPct"
                :top-pct="hover.topPct"
                :below="hover.below"
            >
                <span class="text-muted-foreground">{{ hover.label }}</span>
                <span
                    v-if="hover.current != null"
                    class="tabular font-medium text-foreground"
                    >{{ formatEUR(hover.current) }}</span
                >
                <span
                    v-if="hover.previous != null"
                    class="tabular text-muted-foreground"
                    >· {{ formatEUR(hover.previous) }}</span
                >
            </ChartTooltip>
        </div>
        <div class="flex gap-2.5">
            <span
                v-for="m in months"
                :key="m.month"
                class="tabular min-w-0 flex-1 text-center text-[10.5px]"
                :class="
                    m.month === currentMonth
                        ? 'font-semibold text-accent-strong'
                        : 'text-muted-foreground'
                "
                >{{ MONTHS[m.month - 1] }}</span
            >
        </div>
        <div
            class="flex flex-wrap gap-x-4 gap-y-1 text-13 text-muted-foreground"
        >
            <span class="flex items-center gap-1.5"
                ><i class="size-2 rounded-[2px] bg-accent-strong" />{{
                    currentYear
                }}</span
            >
            <span v-if="hasPrevious" class="flex items-center gap-1.5"
                ><i class="size-2 rounded-[2px] bg-muted-foreground/25" />{{
                    previousYear
                }}
                (riferimento)</span
            >
        </div>
    </div>
</template>
