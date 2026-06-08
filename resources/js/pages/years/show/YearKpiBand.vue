<script setup lang="ts">
/**
 * Banda KPI dell'anno (competenza) a due famiglie:
 *  - Reddito: Fatturato (eroe) · Reddito netto · Reddito IRPEF (fiscale).
 *  - Imposte e contributi: eroe = Spese da pagare (a oggi o dell'anno), barra di
 *    copertura, sotto Totale spese e Pagato, toggle "A oggi · Anno".
 *
 * Anno in corso: il toggle scambia maturato-a-oggi (azione) ↔ intero anno
 * (pianificazione); il netto usa il maturato a oggi. Anno chiuso: niente toggle,
 * cifre definitive. Residuo zero → "Saldato"/"In pari"; sovra-pagato → negativo
 * (verde, è un credito).
 * La cassa cross-anno (F24) vive nelle Scadenze, non qui.
 */
import { computed, ref } from 'vue';
import SplitBar from '@/components/charts/SplitBar.vue';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { formatEUR } from '@/lib/format';
import type { YearShow } from '@/types';

const props = defineProps<{ year: YearShow }>();

const isPast = computed<boolean>(() => props.year.meta.time_state === 'past');

// Media mensile del reddito IRPEF: ÷ mesi trascorsi (anno in corso), ÷12 (chiuso).
const monthsElapsed = computed<number>(() => {
    const state = props.year.meta.time_state;

    if (state === 'future') {
        return 0;
    }

    return state === 'past' ? 12 : new Date().getMonth() + 1;
});

const view = ref<'oggi' | 'anno'>('oggi');
const useAnno = computed<boolean>(() => isPast.value || view.value === 'anno');

// Single toggle: ignora il deselect (clic sull'attivo non svuota la vista).
function setView(value: unknown): void {
    if (value === 'oggi' || value === 'anno') {
        view.value = value;
    }
}

const income = computed(() => {
    const t = props.year.totals;

    return {
        fatturato: t.invoice_total,
        netto: isPast.value ? t.net : t.net_to_date,
        irpef: t.irpef_income_net,
        irpefMonthly: monthsElapsed.value > 0 ? t.irpef_income_net / monthsElapsed.value : 0,
    };
});

const tax = computed(() => {
    const t = props.year.totals;
    const anno = useAnno.value;

    // Eroe = Spese da pagare (residuo); Totale spese e Pagato scendono sotto la barra.
    const due = anno ? t.expenses_due : t.expenses_due_to_date;

    return {
        dueLabel: isPast.value ? 'Spese da pagare' : anno ? 'Spese da pagare' : 'Spese da pagare a oggi',
        due,
        total: anno ? t.expenses_definitive : t.expenses_amount_to_date,
        paid: t.expenses_paid,
        rest: Math.max(0, due), // quota barra (mai negativa)
        negative: due < 0,
        zero: due === 0,
    };
});
</script>

<template>
    <div class="grid divide-y divide-border overflow-hidden rounded-lg border border-border bg-card md:grid-cols-[1fr_1.25fr] md:divide-x md:divide-y-0">
        <!-- Reddito -->
        <section class="flex flex-col px-5 py-3.5">
            <div class="flex flex-col gap-3">
                <header class="flex h-7 items-center">
                    <h3 class="kicker text-muted-foreground">Reddito{{ isPast ? '' : ' a oggi' }}</h3>
                </header>
                <div class="flex flex-col gap-1">
                    <p class="text-13 text-muted-foreground">Fatturato</p>
                    <p class="tabular text-2xl font-medium leading-none tracking-tight text-foreground">{{ formatEUR(income.fatturato) }}</p>
                </div>
            </div>
            <div class="mt-auto flex flex-col gap-1.5 pt-5 text-13">
                <div class="flex items-baseline justify-between gap-3">
                    <span class="whitespace-nowrap text-muted-foreground">Reddito netto effettivo</span>
                    <span class="tabular text-foreground font-medium">{{ formatEUR(income.netto) }}</span>
                </div>
                <div class="flex items-baseline justify-between gap-3">
                    <span class="whitespace-nowrap text-muted-foreground">Reddito IRPEF</span>
                    <span class="tabular text-foreground font-medium">
                        {{ formatEUR(income.irpef) }}<span class="text-muted-foreground font-normal"> · {{ formatEUR(income.irpefMonthly) }}/mese</span>
                    </span>
                </div>
            </div>
        </section>

        <!-- Imposte e contributi -->
        <section class="flex flex-col px-5 py-3.5">
            <div class="flex flex-col gap-3">
                <header class="flex h-7 items-center justify-between gap-3">
                    <h3 class="kicker text-muted-foreground">Imposte e contributi</h3>
                    <ToggleGroup
                        v-if="!isPast"
                        :model-value="view"
                        type="single"
                        variant="boxed"
                        size="sm"
                        aria-label="Periodo imposte"
                        @update:model-value="setView"
                    >
                        <ToggleGroupItem value="oggi" aria-label="A oggi">A oggi</ToggleGroupItem>
                        <ToggleGroupItem value="anno" aria-label="Intero anno">Anno</ToggleGroupItem>
                    </ToggleGroup>
                </header>
                <div class="flex flex-col gap-1">
                    <p class="text-13 text-muted-foreground">{{ tax.dueLabel }}</p>
                    <p v-if="tax.zero" class="text-2xl font-medium leading-none tracking-tight text-foreground">{{ isPast ? 'Saldato' : 'In pari' }}</p>
                    <p v-else class="tabular text-2xl font-medium leading-none tracking-tight" :class="tax.negative ? 'text-success' : 'text-foreground'">{{ formatEUR(tax.due) }}</p>
                </div>
            </div>
            <div class="mt-auto flex flex-col gap-2.5 pt-5">
                <SplitBar :paid="tax.paid" :rest="tax.rest" :total="tax.total" />
                <div class="flex flex-col gap-1.5 text-13">
                    <div class="flex items-baseline justify-between gap-3">
                        <span class="whitespace-nowrap text-muted-foreground">Totale spese</span>
                        <span class="tabular font-medium text-foreground">{{ formatEUR(tax.total) }}</span>
                    </div>
                    <div class="flex items-baseline justify-between gap-3">
                        <span class="whitespace-nowrap text-muted-foreground">Pagato</span>
                        <span class="tabular font-medium text-foreground">{{ formatEUR(tax.paid) }}</span>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
