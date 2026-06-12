<script setup lang="ts">
/**
 * Sparkline del trend su una serie (es. netto mensile): linea petrol con
 * riempimento sfumato sotto. La serie può contenere `null` (mesi futuri/senza
 * dato): la linea copre solo i punti validi e si ferma lì. Una serie
 * `comparison` opzionale (es. anno precedente) è disegnata dietro come linea
 * grigia tratteggiata — entrambe sulla stessa scala (min/max sui punti validi)
 * così il confronto è onesto. In hover compaiono i punti col valore. Coordinate
 * normalizzate, tratto non scalato. Con meno di due punti non disegna.
 */
import { computed, ref, useId } from 'vue';
import ChartTooltip from '@/components/charts/ChartTooltip.vue';

const props = withDefaults(
    defineProps<{
        points: (number | null)[];
        comparison?: (number | null)[];
        /** Etichetta per slot (es. mese) mostrata nel tooltip in hover. */
        labels?: string[];
        /** Formatta il valore nel tooltip (default: numero grezzo). */
        formatValue?: (n: number) => string;
        height?: number;
    }>(),
    {
        comparison: undefined,
        labels: undefined,
        formatValue: (n: number) => String(n),
        height: 48,
    },
);

// Id univoco del gradiente: più sparkline nella stessa pagina non collidono.
const fillId = `spark-${useId()}`;

const W = 120;
const H = 40;
const PAD = 4; // margine verticale: il tratto non viene tagliato in alto/basso

// Slot dell'asse x: il massimo fra le due serie (così la principale, se più corta,
// si ferma a metà asse invece di occuparlo tutto).
const slots = computed(() =>
    Math.max(props.points.length, props.comparison?.length ?? 0),
);

// Scala condivisa fra serie principale e confronto: il confronto è significativo
// solo se le due linee usano lo stesso min/max (sui soli punti validi).
const scale = computed<{ min: number; span: number } | null>(() => {
    const all = [...props.points, ...(props.comparison ?? [])].filter(
        (v): v is number => v != null,
    );

    if (all.length < 2) {
        return null;
    }

    const min = Math.min(...all);

    return { min, span: Math.max(...all) - min || 1 };
});

function xAt(i: number): number {
    return slots.value > 1 ? (i / (slots.value - 1)) * W : 0;
}

function yAt(v: number): number {
    const s = scale.value!;

    return PAD + (1 - (v - s.min) / s.span) * (H - PAD * 2);
}

// Indici dei punti validi (non null) di una serie.
function validIndices(series: (number | null)[]): number[] {
    return series.flatMap((v, i) => (v == null ? [] : [i]));
}

function lineFor(series: (number | null)[]): string {
    if (scale.value === null) {
        return '';
    }

    return validIndices(series)
        .map((i) => `${xAt(i)},${yAt(series[i] as number)}`)
        .join(' ');
}

const line = computed(() => lineFor(props.points));
const comparisonLine = computed(() => lineFor(props.comparison ?? []));

// Area sotto la linea principale: dal primo all'ultimo punto valido (non a tutta
// larghezza, così segue la linea quando si ferma al mese in corso).
const area = computed(() => {
    const idx = validIndices(props.points);

    if (scale.value === null || idx.length < 2) {
        return '';
    }

    return `${line.value} ${xAt(idx[idx.length - 1])},${H} ${xAt(idx[0])},${H}`;
});

// ── Hover: guida + punti + tooltip col valore del punto ──────────────────────
const root = ref<HTMLElement | null>(null);
const hoverIdx = ref<number | null>(null);

function onMove(e: MouseEvent): void {
    if (root.value === null || slots.value < 2) {
        return;
    }

    const rect = root.value.getBoundingClientRect();
    const ratio = (e.clientX - rect.left) / rect.width;
    hoverIdx.value = Math.min(
        slots.value - 1,
        Math.max(0, Math.round(ratio * (slots.value - 1))),
    );
}

function onLeave(): void {
    hoverIdx.value = null;
}

const hover = computed(() => {
    const i = hoverIdx.value;

    if (i === null || scale.value === null) {
        return null;
    }

    const main = props.points[i] ?? null;
    const comp = props.comparison?.[i] ?? null;

    if (main === null && comp === null) {
        return null;
    }

    const anchorTop = main !== null ? yAt(main) : yAt(comp as number);

    return {
        leftPct: (xAt(i) / W) * 100,
        anchorTopPct: (anchorTop / H) * 100,
        below: (anchorTop / H) * 100 < 38, // punto in alto → tooltip sotto
        label: props.labels?.[i] ?? null,
        main:
            main === null
                ? null
                : { topPct: (yAt(main) / H) * 100, value: main },
        comp:
            comp === null
                ? null
                : { topPct: (yAt(comp) / H) * 100, value: comp },
    };
});
</script>

<template>
    <div
        v-if="line"
        ref="root"
        class="relative"
        :style="{ height: `${height}px` }"
        @mousemove="onMove"
        @mouseleave="onLeave"
    >
        <svg
            width="100%"
            height="100%"
            :viewBox="`0 0 ${W} ${H}`"
            preserveAspectRatio="none"
            class="block overflow-visible"
            aria-hidden="true"
        >
            <defs>
                <linearGradient :id="fillId" x1="0" y1="0" x2="0" y2="1">
                    <stop
                        offset="0"
                        stop-color="var(--accent-strong)"
                        stop-opacity="0.16"
                    />
                    <stop
                        offset="1"
                        stop-color="var(--accent-strong)"
                        stop-opacity="0"
                    />
                </linearGradient>
            </defs>
            <!-- Confronto (anno precedente): dietro, grigio tratteggiato. -->
            <polyline
                v-if="comparisonLine"
                :points="comparisonLine"
                fill="none"
                stroke="var(--muted-foreground)"
                stroke-opacity="0.55"
                stroke-width="1.5"
                stroke-dasharray="3 3"
                stroke-linecap="round"
                stroke-linejoin="round"
                vector-effect="non-scaling-stroke"
            />
            <polygon :points="area" :fill="`url(#${fillId})`" />
            <polyline
                :points="line"
                fill="none"
                stroke="var(--accent-strong)"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                vector-effect="non-scaling-stroke"
            />
        </svg>

        <template v-if="hover">
            <span
                class="pointer-events-none absolute inset-y-0 w-px bg-border"
                :style="{ left: `${hover.leftPct}%` }"
            />
            <span
                v-if="hover.comp"
                class="pointer-events-none absolute size-1.5 -translate-x-1/2 -translate-y-1/2 rounded-full bg-muted-foreground"
                :style="{
                    left: `${hover.leftPct}%`,
                    top: `${hover.comp.topPct}%`,
                }"
            />
            <span
                v-if="hover.main"
                class="pointer-events-none absolute size-2 -translate-x-1/2 -translate-y-1/2 rounded-full bg-accent-strong ring-2 ring-card"
                :style="{
                    left: `${hover.leftPct}%`,
                    top: `${hover.main.topPct}%`,
                }"
            />
            <ChartTooltip
                :left-pct="hover.leftPct"
                :top-pct="hover.anchorTopPct"
                :below="hover.below"
            >
                <span v-if="hover.label" class="text-muted-foreground">{{
                    hover.label
                }}</span>
                <span
                    v-if="hover.main"
                    class="tabular font-medium text-foreground"
                    >{{ formatValue(hover.main.value) }}</span
                >
                <span v-if="hover.comp" class="tabular text-muted-foreground"
                    >· {{ formatValue(hover.comp.value) }}</span
                >
            </ChartTooltip>
        </template>
    </div>
</template>
