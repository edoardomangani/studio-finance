<script setup lang="ts">
/**
 * Fascia hero della dashboard: tre pannelli, una domanda ciascuno.
 *  - Questo mese: stipendio (netto) + copertura fatturato/spese + delta YoY.
 *  - Da coprire (tutti gli anni, pannello centrale più largo): Spese da pagare a
 *    oggi (competenza) e Scadenze da pagare (cassa), affiancate.
 *  - Anno: cumulato + sparkline del netto + proiezione + reddito IRPEF.
 * Layout come la banda KPI dell'anno: kicker + titolo/importo in alto, elemento
 * di fondo ancorato in basso (mt-auto) → footer allineati tra i pannelli.
 */
import { PhArrowDownRight, PhArrowUpRight } from '@phosphor-icons/vue';
import { computed } from 'vue';
import SplitBar from '@/components/charts/SplitBar.vue';
import { Badge } from '@/components/ui/badge';
import { formatEUR, formatPercent } from '@/lib/format';
import type { DashboardThisMonth, DashboardToCover, DashboardYear } from '@/types';

const props = defineProps<{
    monthName: string;
    calendarYear: number;
    previousYear: number;
    thisMonth: DashboardThisMonth | null;
    toCover: DashboardToCover;
    year: DashboardYear;
}>();

const progressPct = computed<number>(() => Math.min(100, (props.year.months_elapsed / 12) * 100));
</script>

<template>
    <div class="grid divide-y divide-border overflow-hidden rounded-lg border border-border bg-card md:grid-cols-[1fr_1.3fr_1fr] md:divide-x md:divide-y-0">
        <!-- Questo mese -->
        <section class="flex flex-col bg-accent-strong/4 px-5 py-3.5">
            <div class="flex flex-col gap-3">
                <header class="flex h-7 items-center">
                    <h3 class="kicker text-muted-foreground">Questo mese · {{ monthName }} {{ calendarYear }}</h3>
                </header>
                <div v-if="thisMonth" class="flex flex-col gap-1">
                    <p class="text-13 text-muted-foreground">Stipendio del mese</p>
                    <p class="tabular text-2xl font-medium leading-none tracking-tight text-foreground">{{ formatEUR(thisMonth.net) }}</p>
                </div>
                <p v-else class="text-13 text-muted-foreground">Nessun dato per il mese in corso.</p>
            </div>
            <div v-if="thisMonth" class="mt-auto flex flex-col gap-2.5 pt-5">
                <SplitBar :paid="thisMonth.net" :rest="thisMonth.expenses" :total="thisMonth.invoice_total" />
                <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
                    <span>Fatturato <span class="tabular font-medium text-foreground">{{ formatEUR(thisMonth.invoice_total) }}</span></span>
                    <span>Spese <span class="tabular font-medium text-foreground">{{ formatEUR(thisMonth.expenses) }}</span></span>
                </div>
                <p v-if="thisMonth.yoy_percent !== null" class="flex items-center gap-1 text-xs text-muted-foreground">
                    <span class="inline-flex items-center gap-0.5" :class="thisMonth.yoy_percent >= 0 ? 'text-success' : 'text-destructive'">
                        <component :is="thisMonth.yoy_percent >= 0 ? PhArrowUpRight : PhArrowDownRight" :size="12" />
                        {{ formatPercent(Math.abs(thisMonth.yoy_percent), 0) }}
                    </span>
                    vs {{ monthName.toLowerCase() }} {{ previousYear }}
                </p>
            </div>
        </section>

        <!-- Da coprire · tutti gli anni (pannello centrale, più largo) -->
        <section class="flex flex-col px-5 py-3.5">
            <header class="flex h-7 items-center">
                <h3 class="kicker text-muted-foreground">Da coprire · tutti gli anni</h3>
            </header>
            <div class="mt-3 flex flex-1 items-stretch gap-4">
                <div class="flex flex-1 flex-col">
                    <p class="text-13 text-muted-foreground">Spese da pagare · a oggi</p>
                    <p class="tabular mt-1.5 text-2xl font-medium leading-none tracking-tight text-foreground">{{ formatEUR(toCover.expenses_due_to_date) }}</p>
                    <p class="mt-1 text-[11px] text-muted-foreground/80">Maturate, non ancora pagate</p>
                    <p class="mt-auto pt-3 text-xs text-muted-foreground">Pagato a oggi <span class="tabular text-foreground font-medium">{{ formatEUR(toCover.paid_to_date) }}</span></p>
                </div>
                <div class="w-px self-stretch bg-border-soft" />
                <div class="flex flex-1 flex-col">
                    <p class="text-13 text-muted-foreground">Scadenze da pagare · tutte</p>
                    <p class="tabular mt-1.5 text-2xl font-medium leading-none tracking-tight text-foreground">{{ formatEUR(toCover.deadlines_due) }}</p>
                    <p class="mt-1 text-[11px] text-muted-foreground/80">Importi delle scadenze aperte</p>
                    <div class="mt-auto pt-3">
                        <Badge variant="warning" class="tabular">{{ toCover.open_deadlines_count }} scadenze aperte</Badge>
                    </div>
                </div>
            </div>
        </section>

        <!-- Anno -->
        <section class="flex flex-col px-5 py-3.5">
            <div class="flex flex-col gap-3">
                <header class="flex h-7 items-center">
                    <h3 class="kicker text-muted-foreground">Anno {{ year.year }}</h3>
                </header>
                <div class="flex flex-col gap-1">
                    <p class="text-13 text-muted-foreground">Fatturato cumulato</p>
                    <p class="tabular text-2xl font-medium leading-none tracking-tight text-foreground">{{ formatEUR(year.invoice_total) }}</p>
                </div>
            </div>
            <div class="mt-auto flex flex-col gap-2 pt-5">
                <div class="h-1 overflow-hidden rounded-full bg-muted">
                    <div class="h-full rounded-full bg-accent-strong" :style="{ width: `${progressPct}%` }" />
                </div>
                <div class="flex items-baseline justify-between gap-3 text-xs text-muted-foreground">
                    <span>{{ year.months_elapsed }}/12 mesi</span>
                    <span>Proiez. <span class="tabular text-foreground font-medium">{{ formatEUR(year.projection) }}</span></span>
                </div>
                <div class="flex items-baseline justify-between gap-3 text-xs">
                    <span class="text-muted-foreground">Reddito IRPEF</span>
                    <span class="tabular text-foreground">
                        <span class="font-medium">{{ formatEUR(year.irpef_income_net) }}</span><span class="text-muted-foreground"> · {{ formatEUR(year.irpef_income_net / (year.months_elapsed || 12)) }}/mese</span>
                    </span>
                </div>
            </div>
        </section>
    </div>
</template>
