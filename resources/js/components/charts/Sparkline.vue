<script setup lang="ts">
/**
 * Sparkline del trend su una serie (es. netto 12 mesi): linea petrol con
 * riempimento sfumato sotto. Disegna in coordinate normalizzate con tratto non
 * scalato (nitido a ogni larghezza). Con meno di due punti non disegna nulla.
 */
import { computed, useId } from 'vue';

const props = withDefaults(
    defineProps<{ points: number[]; height?: number }>(),
    {
        height: 48,
    },
);

// Id univoco del gradiente: più sparkline nella stessa pagina non collidono.
const fillId = `spark-${useId()}`;

const W = 120;
const H = 40;
const PAD = 4; // margine verticale: il tratto non viene tagliato in alto/basso

// Serie → coordinate SVG: x equispaziato su W, y mappato sul range (invertito).
const coords = computed<Array<readonly [number, number]>>(() => {
    const pts = props.points;

    if (pts.length < 2) {
        return [];
    }

    const min = Math.min(...pts);
    const span = Math.max(...pts) - min || 1;

    return pts.map(
        (v, i) =>
            [
                (i / (pts.length - 1)) * W,
                PAD + (1 - (v - min) / span) * (H - PAD * 2),
            ] as const,
    );
});

const line = computed(() =>
    coords.value.map(([x, y]) => `${x},${y}`).join(' '),
);
const area = computed(() =>
    coords.value.length ? `${line.value} ${W},${H} 0,${H}` : '',
);
const last = computed(() => coords.value.at(-1) ?? null);
</script>

<template>
    <svg
        v-if="last"
        :height="height"
        width="100%"
        :viewBox="`0 0 ${W} ${H}`"
        preserveAspectRatio="none"
        class="overflow-visible"
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
</template>
