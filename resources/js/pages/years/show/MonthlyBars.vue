<script setup lang="ts">
/**
 * Grafico a barre dei 12 mesi (anno in corso/chiuso): altezza = totale fatture,
 * suddivisa in netto (base) + accantonato (sopra). Mese in corso in petrol,
 * mesi futuri come stub tratteggiato. Click su una barra → seleziona il mese
 * (sincronizzato con la tabella mensile). Collega la lettura visiva alla matrice.
 */
import { computed } from 'vue';
import { formatEUR } from '@/lib/format';
import type { YearMonth } from '@/types';

const props = defineProps<{
    months: YearMonth[];
    selected: number | null;
    /** Mese in corso (solo anno corrente); null altrimenti. */
    currentMonth?: number | null;
    /** Anno interamente futuro (aperto in anticipo): tutti i mesi sono stub. */
    allFuture?: boolean;
}>();

const emit = defineEmits<{ (e: 'select', month: number): void }>();

const MONTHS = ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'];
const MONTHS_FULL = [
    'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno',
    'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre',
];

const maxInvoice = computed<number>(() => Math.max(...props.months.map((m) => m.invoice_total), 1));

// Futuro = anno futuro, o mesi oltre il corrente nell'anno in corso: niente dati.
function isFuture(m: YearMonth): boolean {
    return props.allFuture === true || (props.currentMonth != null && m.month > props.currentMonth);
}

function barHeight(m: YearMonth): string {
    if (isFuture(m)) {
        return '8px';
    }

    return `${Math.max((m.invoice_total / maxInvoice.value) * 100, 2)}%`;
}

function netPct(m: YearMonth): number {
    return m.invoice_total > 0 ? Math.max(0, (m.net / m.invoice_total) * 100) : 0;
}

function expPct(m: YearMonth): number {
    return m.invoice_total > 0 ? Math.min(100, (m.total_expenses / m.invoice_total) * 100) : 0;
}
</script>

<template>
    <div class="flex flex-col gap-3">
        <div class="flex h-[150px] items-end gap-2">
            <button
                v-for="m in months"
                :key="m.month"
                type="button"
                class="group flex h-full flex-1 cursor-pointer flex-col items-center gap-1.5"
                :title="`${MONTHS_FULL[m.month - 1]} · ${formatEUR(m.invoice_total)}`"
                :aria-label="`${MONTHS_FULL[m.month - 1]}: fatturato ${formatEUR(m.invoice_total)}, netto ${formatEUR(m.net)}`"
                @click="emit('select', m.month)"
            >
                <span class="flex w-full flex-1 items-end justify-center">
                    <span
                        class="flex w-3/5 min-w-2 max-w-[26px] flex-col justify-end overflow-hidden rounded-t-sm transition-shadow"
                        :class="[
                            isFuture(m) ? 'border border-dashed border-border' : 'bg-muted group-hover:ring-1 group-hover:ring-accent-line',
                            selected === m.month && !isFuture(m) ? 'ring-1 ring-accent-strong ring-offset-1' : '',
                        ]"
                        :style="{ height: barHeight(m) }"
                    >
                        <template v-if="!isFuture(m) && m.invoice_total > 0">
                            <span
                                class="w-full"
                                :class="m.month === currentMonth ? 'bg-accent-line' : 'bg-muted-foreground/25'"
                                :style="{ height: `${expPct(m)}%` }"
                            />
                            <span
                                class="w-full"
                                :class="m.month === currentMonth ? 'bg-accent-strong' : 'bg-muted-foreground'"
                                :style="{ height: `${netPct(m)}%` }"
                            />
                        </template>
                    </span>
                </span>
                <span
                    class="tabular text-[10.5px]"
                    :class="m.month === currentMonth ? 'font-semibold text-accent-strong' : selected === m.month ? 'text-foreground' : 'text-muted-foreground'"
                >
                    {{ MONTHS[m.month - 1] }}
                </span>
            </button>
        </div>
        <div class="flex flex-wrap gap-x-4 gap-y-1 text-13 text-muted-foreground">
            <span class="flex items-center gap-1.5"><i class="size-2 rounded-[2px] bg-muted-foreground" />Netto</span>
            <span class="flex items-center gap-1.5"><i class="size-2 rounded-[2px] bg-muted-foreground/25" />Spese</span>
            <span class="flex items-center gap-1.5"><i class="size-2 rounded-[2px] bg-accent-strong" />Mese in corso</span>
        </div>
    </div>
</template>
