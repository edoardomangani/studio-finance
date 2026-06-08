<script setup lang="ts">
/**
 * Panoramica della vista anno (tutto a competenza): striscia KPI sul reddito,
 * Riserva dell'anno (numero-guida "da mettere da parte"), accantonamento del
 * mese corrente, tabella mensile e liste recenti. La cassa cross-year (F24 in
 * arrivo) non vive qui: è una timeline trasversale agli anni (Scadenze).
 */
import { computed, ref, watch } from 'vue';
import { formatEUR } from '@/lib/format';
import MonthlyBars from '@/pages/years/show/MonthlyBars.vue';
import MonthlyTable from '@/pages/years/show/MonthlyTable.vue';
import MonthSheet from '@/pages/years/show/MonthSheet.vue';
import OverviewLists from '@/pages/years/show/OverviewLists.vue';
import YearKpiBand from '@/pages/years/show/YearKpiBand.vue';
import { index as yearsIndex, show as yearShow } from '@/routes/years';
import type { YearMonth, YearShow } from '@/types';

const props = defineProps<{ year: YearShow }>();
const emit = defineEmits<{ (e: 'go', tab: string): void }>();

const MONTHS = [
    'Gennaio',
    'Febbraio',
    'Marzo',
    'Aprile',
    'Maggio',
    'Giugno',
    'Luglio',
    'Agosto',
    'Settembre',
    'Ottobre',
    'Novembre',
    'Dicembre',
];

// Origine per i deep-link a fattura: Anni / {anno}.
const origin = [
    { label: 'Anni', href: yearsIndex().url },
    { label: String(props.year.year), href: yearShow(props.year.year).url },
];

// Mese corrente (solo per l'anno in corso): per l'highlight e la sintesi
// "questo mese". L'orologio client basta per un evidenziatore di UI.
const currentMonth = computed<number | null>(() =>
    props.year.meta.time_state === 'current' ? new Date().getMonth() + 1 : null,
);

// Mese selezionato (barre ↔ tabella): default al mese in corso, altrimenti
// l'ultimo con attività (anno chiuso), altrimenti dicembre.
const defaultMonth = computed<number>(() => {
    if (currentMonth.value) {
        return currentMonth.value;
    }

    return [...props.year.months].reverse().find((m) => m.invoice_total > 0)?.month ?? 12;
});

const selectedMonth = ref<number>(defaultMonth.value);
watch(() => props.year.year, () => (selectedMonth.value = defaultMonth.value));

const selectedData = computed<YearMonth | null>(
    () => props.year.months.find((m) => m.month === selectedMonth.value) ?? null,
);

// Side-sheet dettaglio mese (drill-in dalla riga; la barra solo seleziona).
const monthSheetOpen = ref(false);
const sheetMonth = ref<YearMonth | null>(null);

function openMonth(month: YearMonth): void {
    selectedMonth.value = month.month;
    sheetMonth.value = month;
    monthSheetOpen.value = true;
}
</script>

<template>
    <div class="flex flex-col gap-6">
        <!-- Anno pre-aperto: nessun dato finché non viene aperto formalmente. -->
        <div
            v-if="year.pre_opened"
            class="rounded-lg border border-dashed border-border px-4 py-8 text-center"
        >
            <p class="text-13 font-medium text-foreground">Anno pre-aperto</p>
            <p class="mx-auto mt-1 max-w-md text-13 text-muted-foreground">
                Creato in automatico da una scadenza dell'anno precedente. Mesi, fatture,
                scadenze e calcoli compaiono quando lo apri formalmente.
            </p>
        </div>

        <template v-else>
            <!-- Sintesi: focale "da mettere da parte" + reddito (competenza). -->
            <YearKpiBand :year="year" />

            <section class="flex flex-col gap-3">
                <header class="flex items-baseline justify-between gap-3">
                    <h2 class="kicker text-muted-foreground">Andamento mensile</h2>
                    <p v-if="selectedData" class="flex items-center gap-2 text-13 text-muted-foreground">
                        <span class="rounded bg-accent px-1.5 py-0.5 text-xs font-medium text-accent-strong">{{ MONTHS[selectedData.month - 1] }}</span>
                        <span>
                            Fatturato <span class="tabular font-medium text-foreground">{{ formatEUR(selectedData.invoice_total) }}</span>
                            · Netto <span class="tabular font-medium text-foreground">{{ formatEUR(selectedData.net) }}</span>
                        </span>
                    </p>
                </header>
                <div class="rounded-lg border border-border bg-card p-4">
                    <MonthlyBars
                        :months="year.months"
                        :selected="selectedMonth"
                        :current-month="currentMonth"
                        :all-future="year.meta.time_state === 'future'"
                        @select="(month) => (selectedMonth = month)"
                    />
                </div>
                <MonthlyTable
                    :months="year.months"
                    :totals="year.totals"
                    :current-month="currentMonth"
                    :selected-month="selectedMonth"
                    @select="openMonth"
                />
            </section>

            <OverviewLists :year="year" :origin="origin" @go="(tab) => emit('go', tab)" />
        </template>

        <p v-if="year.note" class="text-13 text-muted-foreground">{{ year.note }}</p>

        <MonthSheet v-model:open="monthSheetOpen" :month="sheetMonth" :origin="origin" />
    </div>
</template>
