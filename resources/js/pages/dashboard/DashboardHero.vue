<script setup lang="ts">
/**
 * Fascia hero della dashboard (V4): tre zone affiancate, divise da hairline.
 *  - Stipendio del mese: numero-eroe (netto), + fatturato e spese del mese.
 *  - Netto · ultimi 12 mesi: sparkline della serie + variazione primo→ultimo.
 *  - Anno: cumulato + progress mesi + proiezione fine anno.
 * Su desktop largo le tre zone respirano affiancate (lettura "cruscotto"); sotto
 * il breakpoint collassano a fasce orizzontali (hairline). Il numero-eroe scala
 * fluido (clamp) e resta nero: nel sistema sobrio il petrol non tocca lo stipendio.
 */
import { PhTrendDown, PhTrendUp } from '@phosphor-icons/vue';
import { computed } from 'vue';
import Sparkline from '@/components/charts/Sparkline.vue';
import { formatEUR, formatPercent } from '@/lib/format';
import type {
    DashboardNetTrend,
    DashboardThisMonth,
    DashboardYear,
} from '@/types';

const props = defineProps<{
    monthName: string;
    displayYear: number;
    displayMonth: number;
    thisMonth: DashboardThisMonth | null;
    netTrend: DashboardNetTrend;
    year: DashboardYear;
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

const progressPct = computed<number>(() =>
    Math.min(100, (props.year.months_elapsed / 12) * 100),
);

// Estremi della finestra a 12 mesi (mese + valore sotto la sparkline, così la
// linea ha un riferimento): il primo è il mese successivo al mostrato (un anno
// fa), l'ultimo è il mese mostrato.
const startMonthLabel = computed<string>(() => MONTHS[props.displayMonth % 12]);
const endMonthLabel = computed<string>(() => MONTHS[props.displayMonth - 1]);
const firstNet = computed<number | null>(
    () => props.netTrend.points[0] ?? null,
);
const lastNet = computed<number | null>(
    () => props.netTrend.points.at(-1) ?? null,
);
</script>

<template>
    <div
        class="grid divide-y divide-border overflow-hidden rounded-lg border border-border bg-card md:grid-cols-[1fr_1.25fr_1fr] md:divide-y-0"
    >
        <!-- Stipendio del mese -->
        <section class="flex flex-col px-6 py-5">
            <header class="flex h-7 items-center">
                <h3 class="kicker text-muted-foreground">
                    Stipendio · {{ monthName }} {{ displayYear }}
                </h3>
            </header>
            <template v-if="thisMonth">
                <p
                    class="tabular mt-2 text-[clamp(2.125rem,1.5rem+2vw,3.25rem)] leading-[0.95] font-medium tracking-[-0.03em] whitespace-nowrap text-foreground"
                >
                    {{ formatEUR(thisMonth.net) }}
                </p>
                <div
                    class="mt-auto flex justify-between pt-4 text-13 text-muted-foreground"
                >
                    <span>Fatturato</span>
                    <span class="tabular font-medium text-foreground">{{
                        formatEUR(thisMonth.invoice_total)
                    }}</span>
                </div>
            </template>
            <p v-else class="mt-2 text-13 text-muted-foreground">
                Nessun dato per il mese in corso.
            </p>
        </section>

        <!-- Netto · ultimi 12 mesi (sparkline) -->
        <section class="relative flex flex-col justify-center gap-2 px-6 py-5">
            <span
                class="absolute inset-y-5 left-0 hidden w-px bg-border md:block"
                aria-hidden="true"
            />
            <div class="flex items-baseline justify-between gap-3">
                <span class="text-2xs text-muted-foreground"
                    >Netto · ultimi 12 mesi</span
                >
                <span
                    v-if="netTrend.percent !== null"
                    class="inline-flex items-center gap-0.5 text-13 font-medium"
                    :class="
                        netTrend.percent >= 0
                            ? 'text-success'
                            : 'text-destructive'
                    "
                >
                    <component
                        :is="netTrend.percent >= 0 ? PhTrendUp : PhTrendDown"
                        :size="13"
                    />
                    {{ formatPercent(Math.abs(netTrend.percent), 0) }}
                </span>
            </div>
            <Sparkline :points="netTrend.points" :height="50" />
            <div
                class="flex items-baseline justify-between gap-3 text-2xs text-muted-foreground"
            >
                <span v-if="firstNet !== null" class="whitespace-nowrap"
                    >{{ startMonthLabel }}
                    <span class="tabular text-foreground">{{
                        formatEUR(firstNet)
                    }}</span></span
                >
                <span v-if="lastNet !== null" class="whitespace-nowrap"
                    >{{ endMonthLabel }}
                    <span class="tabular text-foreground">{{
                        formatEUR(lastNet)
                    }}</span></span
                >
            </div>
        </section>

        <!-- Anno -->
        <section class="relative flex flex-col px-6 py-5">
            <span
                class="absolute inset-y-5 left-0 hidden w-px bg-border md:block"
                aria-hidden="true"
            />
            <header class="flex h-7 items-center">
                <h3 class="kicker text-muted-foreground">
                    Anno {{ year.year }}
                </h3>
            </header>
            <div class="mt-2 flex flex-col gap-1">
                <p class="text-13 text-muted-foreground">Fatturato cumulato</p>
                <p
                    class="tabular text-[1.625rem] leading-none font-medium tracking-tight whitespace-nowrap text-foreground"
                >
                    {{ formatEUR(year.invoice_total) }}
                </p>
            </div>
            <div class="mt-auto flex flex-col gap-2 pt-4">
                <div class="h-1 overflow-hidden rounded-full bg-muted">
                    <div
                        class="h-full rounded-full bg-accent-strong"
                        :style="{ width: `${progressPct}%` }"
                    />
                </div>
                <div
                    class="flex items-baseline justify-between gap-3 text-2xs text-muted-foreground"
                >
                    <span>{{ year.months_elapsed }}/12 mesi</span>
                    <span
                        >Proiez.
                        <span class="tabular font-medium text-foreground">{{
                            formatEUR(year.projection)
                        }}</span></span
                    >
                </div>
            </div>
        </section>
    </div>
</template>
