<script setup lang="ts">
/**
 * Zona "Netto · {anno}" della banda KPI: sparkline del netto mese per mese, con
 * la linea petrol fino al mese utile (si ferma al precedente se il mese in corso
 * non ha ancora fatturato) e il fantasma tratteggiato dell'anno prima su tutti i
 * 12 mesi. Estratta da YearKpiBand per coesione (logica serie + variazione %).
 * Visibile dove c'è spazio (impilata < md, layout a 3 colonne da xl); nascosta
 * nel range md–xl dove la banda sta a 2 colonne e la sidebar la stringe.
 */
import { PhTrendDown, PhTrendUp } from '@phosphor-icons/vue';
import { computed } from 'vue';
import Sparkline from '@/components/charts/Sparkline.vue';
import { formatEUR, formatPercent } from '@/lib/format';
import type { YearShow } from '@/types';

const props = defineProps<{ year: YearShow }>();

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

// Mesi trascorsi: 0 (futuro), 12 (chiuso), mese corrente (in corso).
const monthsElapsed = computed<number>(() => {
    const state = props.year.meta.time_state;

    if (state === 'future') {
        return 0;
    }

    return state === 'past' ? 12 : new Date().getMonth() + 1;
});

// Ultimo mese della sparkline: i mesi trascorsi, ma se il mese in corso non ha
// ancora fatturato si ferma al precedente — stessa logica del "mese mostrato"
// in dashboard generale (non si chiude la serie su un mese vuoto).
const sparklineUpto = computed<number>(() => {
    const upto = monthsElapsed.value;

    if (props.year.meta.time_state !== 'current' || upto <= 1) {
        return upto;
    }

    const current = props.year.months.find((m) => m.month === upto);

    return current && current.invoice_total > 0 ? upto : upto - 1;
});

const elapsedMonths = computed(() =>
    props.year.months.filter((m) => m.month <= sparklineUpto.value),
);
const netSeries = computed<number[]>(() =>
    elapsedMonths.value.map((m) => m.net),
);

// Variazione % primo→ultimo (coerente con l'hero della dashboard).
const netTrendPercent = computed<number | null>(() => {
    const s = netSeries.value;

    if (s.length < 2 || Math.abs(s[0]) === 0) {
        return null;
    }

    return Math.round(((s[s.length - 1] - s[0]) / Math.abs(s[0])) * 1000) / 10;
});

const firstMonth = computed(() => elapsedMonths.value[0] ?? null);
const lastMonth = computed(() => elapsedMonths.value.at(-1) ?? null);

// Punti: tutti e 12 i mesi, netto fino al mese utile e null dopo (la linea si
// ferma al mese in corso; l'asse resta sull'anno intero).
const sparklinePoints = computed<(number | null)[]>(() =>
    props.year.months.map((m) =>
        m.month <= sparklineUpto.value ? m.net : null,
    ),
);

// Confronto: netto dell'anno precedente su tutti i 12 mesi, se aperto.
const previousNet = computed<number[] | null>(() => props.year.previous_net);
</script>

<template>
    <section
        class="relative flex flex-col justify-center gap-2 px-6 py-5 md:hidden xl:flex"
    >
        <span
            class="absolute inset-y-5 left-0 hidden w-px bg-border md:block"
            aria-hidden="true"
        />
        <div class="flex items-baseline justify-between gap-3">
            <span class="text-2xs text-muted-foreground"
                >Netto · {{ year.year }}</span
            >
            <span
                v-if="netTrendPercent !== null"
                class="inline-flex items-center gap-0.5 text-13 font-medium"
                :class="
                    netTrendPercent >= 0 ? 'text-success' : 'text-destructive'
                "
            >
                <component
                    :is="netTrendPercent >= 0 ? PhTrendUp : PhTrendDown"
                    :size="13"
                />
                {{ formatPercent(Math.abs(netTrendPercent), 0) }}
            </span>
        </div>
        <Sparkline
            :points="sparklinePoints"
            :comparison="previousNet ?? undefined"
            :labels="MONTHS"
            :format-value="formatEUR"
            :height="50"
        />
        <div
            class="flex items-baseline justify-between gap-3 text-2xs text-muted-foreground"
        >
            <span v-if="firstMonth" class="whitespace-nowrap"
                >{{ MONTHS[firstMonth.month - 1] }}
                <span class="tabular text-foreground">{{
                    formatEUR(firstMonth.net)
                }}</span></span
            >
            <span v-if="lastMonth" class="whitespace-nowrap"
                >{{ MONTHS[lastMonth.month - 1] }}
                <span class="tabular text-foreground">{{
                    formatEUR(lastMonth.net)
                }}</span></span
            >
        </div>
    </section>
</template>
