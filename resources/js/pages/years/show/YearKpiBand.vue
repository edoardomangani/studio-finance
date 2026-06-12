<script setup lang="ts">
/**
 * Banda KPI dell'anno (competenza), allineata alla fascia hero della dashboard:
 *  - Fatturato (numero-eroe) · reddito netto · netto bancario.
 *  - Netto · {anno}: sparkline del netto mese per mese + variazione primo→ultimo.
 *  - Imposte e contributi: spese da pagare (a oggi/anno), barra di copertura,
 *    totale spese, toggle "A oggi · Anno".
 *
 * Anno in corso: il toggle scambia maturato-a-oggi (azione) ↔ intero anno
 * (pianificazione); il netto usa il maturato a oggi; la sparkline copre i mesi
 * trascorsi. Anno chiuso: niente toggle, cifre definitive, sparkline su 12 mesi.
 * Residuo zero → "Saldato"/"In pari"; sovra-pagato → negativo (verde, credito).
 * La cassa cross-anno (F24) vive nelle Scadenze, non qui.
 */
import { PhTrendDown, PhTrendUp } from '@phosphor-icons/vue';
import { computed, ref } from 'vue';
import Sparkline from '@/components/charts/Sparkline.vue';
import SplitBar from '@/components/charts/SplitBar.vue';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
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

const isPast = computed<boolean>(() => props.year.meta.time_state === 'past');

// Media mensile del netto bancario / finestra sparkline: ÷ mesi trascorsi (anno
// in corso), 12 (chiuso), 0 (futuro).
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
        bankIncome: t.bank_income,
        bankMonthly:
            monthsElapsed.value > 0 ? t.bank_income / monthsElapsed.value : 0,
    };
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

// Sparkline: netto mese per mese fino all'ultimo mese utile (12 se l'anno è
// chiuso). La variazione % è primo→ultimo (coerente con l'hero della dashboard).
const elapsedMonths = computed(() =>
    props.year.months.filter((m) => m.month <= sparklineUpto.value),
);
const netSeries = computed<number[]>(() =>
    elapsedMonths.value.map((m) => m.net),
);
const netTrendPercent = computed<number | null>(() => {
    const s = netSeries.value;

    if (s.length < 2 || Math.abs(s[0]) === 0) {
        return null;
    }

    return Math.round(((s[s.length - 1] - s[0]) / Math.abs(s[0])) * 1000) / 10;
});
const firstMonth = computed(() => elapsedMonths.value[0] ?? null);
const lastMonth = computed(() => elapsedMonths.value.at(-1) ?? null);

// Punti sparkline: tutti e 12 i mesi, netto fino al mese utile e null dopo (la
// linea petrol si ferma al mese in corso; l'asse resta sull'anno intero).
const sparklinePoints = computed<(number | null)[]>(() =>
    props.year.months.map((m) =>
        m.month <= sparklineUpto.value ? m.net : null,
    ),
);

// Confronto: netto dell'anno precedente su tutti i 12 mesi (fantasma
// tratteggiato), se l'anno prima è aperto.
const previousNet = computed<number[] | null>(() => props.year.previous_net);

const tax = computed(() => {
    const t = props.year.totals;
    const anno = useAnno.value;

    // Eroe = Spese da pagare (residuo); Totale spese scende sotto la barra.
    const due = anno ? t.expenses_due : t.expenses_due_to_date;

    return {
        dueLabel:
            isPast.value || anno ? 'Spese da pagare' : 'Spese da pagare a oggi',
        due,
        total: anno ? t.expenses_definitive : t.expenses_amount_to_date,
        paid: t.expenses_paid, // alimenta la barra di copertura
        rest: Math.max(0, due), // quota barra (mai negativa)
        negative: due < 0,
        zero: due === 0,
    };
});
</script>

<template>
    <div
        class="grid divide-y divide-border overflow-hidden rounded-lg border border-border bg-card md:grid-cols-2 md:divide-y-0 xl:grid-cols-[1fr_0.85fr_1.3fr]"
    >
        <!-- Fatturato -->
        <section class="flex flex-col px-6 py-5">
            <header class="flex h-7 items-center">
                <h3 class="kicker text-muted-foreground">Fatturato</h3>
            </header>
            <p
                class="tabular mt-2 text-[clamp(2.125rem,1.5rem+2vw,3.25rem)] leading-[0.95] font-medium tracking-[-0.03em] whitespace-nowrap text-foreground"
            >
                {{ formatEUR(income.fatturato) }}
            </p>
            <div class="mt-auto flex flex-col gap-1.5 pt-4 text-13">
                <div class="flex items-baseline justify-between gap-3">
                    <span class="whitespace-nowrap text-muted-foreground"
                        >Netto</span
                    >
                    <span class="tabular font-medium text-foreground">{{
                        formatEUR(income.netto)
                    }}</span>
                </div>
                <div class="flex items-baseline justify-between gap-3">
                    <span class="whitespace-nowrap text-muted-foreground"
                        >Netto bancario</span
                    >
                    <span class="tabular font-medium text-foreground"
                        >{{ formatEUR(income.bankIncome)
                        }}<span class="font-normal text-muted-foreground">
                            · {{ formatEUR(income.bankMonthly) }}/mese</span
                        ></span
                    >
                </div>
            </div>
        </section>

        <!-- Netto · anno (sparkline dei mesi). Visibile dove c'è spazio: impilata
             sotto md e col layout a 3 colonne da xl in su; nascosta nel range
             md–xl, dove la banda sta a 2 colonne e la sidebar la stringe. -->
        <section
            class="relative flex flex-col justify-center gap-2 px-6 py-5 md:hidden xl:flex"
        >
            <span
                class="absolute inset-y-5 left-0 hidden w-px bg-border md:block"
                aria-hidden="true"
            />
            <div class="flex items-baseline justify-between gap-3">
                <span
                    class="flex items-baseline gap-2 text-2xs text-muted-foreground"
                >
                    Netto · {{ year.year }}
                </span>
                <span
                    v-if="netTrendPercent !== null"
                    class="inline-flex items-center gap-0.5 text-13 font-medium"
                    :class="
                        netTrendPercent >= 0
                            ? 'text-success'
                            : 'text-destructive'
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

        <!-- Imposte e contributi -->
        <section class="relative flex flex-col px-6 py-5">
            <span
                class="absolute inset-y-5 left-0 hidden w-px bg-border md:block"
                aria-hidden="true"
            />
            <div class="flex flex-col gap-2.5">
                <header class="flex h-7 items-center justify-between gap-3">
                    <h3 class="kicker text-muted-foreground">
                        Imposte e contributi
                    </h3>
                    <ToggleGroup
                        v-if="!isPast"
                        :model-value="view"
                        type="single"
                        variant="boxed"
                        size="sm"
                        aria-label="Periodo imposte"
                        @update:model-value="setView"
                    >
                        <ToggleGroupItem value="oggi" aria-label="A oggi"
                            >A oggi</ToggleGroupItem
                        >
                        <ToggleGroupItem value="anno" aria-label="Intero anno"
                            >Anno</ToggleGroupItem
                        >
                    </ToggleGroup>
                </header>
                <div class="flex flex-col gap-1">
                    <p class="text-13 text-muted-foreground">
                        {{ tax.dueLabel }}
                    </p>
                    <p
                        v-if="tax.zero"
                        class="text-[1.625rem] leading-none font-medium tracking-tight text-foreground"
                    >
                        {{ isPast ? 'Saldato' : 'In pari' }}
                    </p>
                    <p
                        v-else
                        class="tabular text-[1.625rem] leading-none font-medium tracking-tight"
                        :class="
                            tax.negative ? 'text-success' : 'text-foreground'
                        "
                    >
                        {{ formatEUR(tax.due) }}
                    </p>
                </div>
            </div>
            <div class="mt-auto flex flex-col gap-2.5 pt-4">
                <SplitBar
                    :paid="tax.paid"
                    :rest="tax.rest"
                    :total="tax.total"
                />
                <div class="flex items-baseline justify-between gap-3 text-13">
                    <span class="whitespace-nowrap text-muted-foreground"
                        >Totale spese</span
                    >
                    <span class="tabular font-medium text-foreground">{{
                        formatEUR(tax.total)
                    }}</span>
                </div>
            </div>
        </section>
    </div>
</template>
