<script setup lang="ts">
/**
 * Tabella mensile della vista anno (12 righe): imponibile, bolli, volume
 * affari IVA, totale fatture, spese accantonate (RB11), netto e rapporto
 * netto/lordo. Riga totali in fondo dai `totals` d'anno. Cuore analitico della
 * Panoramica; densa, mono per i numeri (allineamento sotto-virgola).
 *
 * Click su una riga → side-sheet col dettaglio del mese (fatture + spese): la
 * matrice resta intatta, il dettaglio vive fuori dalla griglia.
 */
import {
    DataTable,
    DataTableBody,
    DataTableHeader,
    DataTableRow,
    TableCell,
    TableFooter,
    TableHead,
    TableRow,
} from '@/components/ui/table';
import { formatEUR, formatPercent } from '@/lib/format';
import type { YearMonth, YearTotals } from '@/types';

defineProps<{
    months: YearMonth[];
    totals: YearTotals;
    selectedMonth: number | null;
    /** Mese in corso da evidenziare (solo anno corrente); null altrimenti. */
    currentMonth?: number | null;
}>();
const emit = defineEmits<{ (e: 'select', month: YearMonth): void }>();

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

function ratio(value: number): string {
    return formatPercent(value * 100);
}
</script>

<template>
    <DataTable>
        <DataTableHeader :has-actions="false">
            <TableHead class="w-[64px]">Mese</TableHead>
            <TableHead class="text-right">Imponibile</TableHead>
            <TableHead class="text-right">Bolli</TableHead>
            <TableHead class="text-right">Volume affari</TableHead>
            <TableHead class="text-right">Totale fatture</TableHead>
            <TableHead class="text-right">Spese</TableHead>
            <TableHead class="text-right">Netto</TableHead>
            <TableHead class="w-[90px] text-right">Netto/Lordo</TableHead>
        </DataTableHeader>
        <DataTableBody>
            <DataTableRow
                v-for="m in months"
                :key="m.month"
                interactive
                :active="selectedMonth === m.month"
                @click="emit('select', m)"
            >
                <TableCell class="font-medium text-foreground">
                    <span class="flex items-center gap-1.5">
                        {{ MONTHS[m.month - 1] }}
                        <span
                            v-if="m.month === currentMonth"
                            class="size-1.5 shrink-0 rounded-full bg-accent-vivid"
                            aria-label="Mese in corso"
                        />
                    </span>
                </TableCell>
                <TableCell class="tabular text-right text-muted-foreground">{{
                    formatEUR(m.taxable_amount)
                }}</TableCell>
                <TableCell class="tabular text-right text-muted-foreground">{{
                    formatEUR(m.stamp_duty)
                }}</TableCell>
                <TableCell class="tabular text-right text-muted-foreground">{{
                    formatEUR(m.vat_turnover)
                }}</TableCell>
                <TableCell class="tabular text-right text-foreground">{{
                    formatEUR(m.invoice_total)
                }}</TableCell>
                <TableCell class="tabular text-right text-muted-foreground">{{
                    formatEUR(m.total_expenses)
                }}</TableCell>
                <TableCell class="tabular text-right text-foreground">{{
                    formatEUR(m.net)
                }}</TableCell>
                <TableCell class="tabular text-right text-muted-foreground">
                    <span v-if="m.invoice_total > 0">{{
                        ratio(m.net_to_gross_ratio)
                    }}</span>
                    <span v-else>—</span>
                </TableCell>
            </DataTableRow>
        </DataTableBody>
        <TableFooter>
            <TableRow class="font-medium">
                <TableCell class="text-foreground">Anno</TableCell>
                <TableCell class="tabular text-right text-foreground">{{
                    formatEUR(totals.taxable_amount)
                }}</TableCell>
                <TableCell class="tabular text-right text-foreground">{{
                    formatEUR(totals.stamp_duty)
                }}</TableCell>
                <TableCell class="tabular text-right text-foreground">{{
                    formatEUR(totals.vat_turnover)
                }}</TableCell>
                <TableCell class="tabular text-right text-foreground">{{
                    formatEUR(totals.invoice_total)
                }}</TableCell>
                <TableCell class="tabular text-right text-foreground">{{
                    formatEUR(totals.expenses_definitive)
                }}</TableCell>
                <TableCell class="tabular text-right text-foreground">{{
                    formatEUR(totals.net)
                }}</TableCell>
                <TableCell class="tabular text-right text-muted-foreground">
                    <span v-if="totals.invoice_total > 0">{{
                        ratio(totals.net / totals.invoice_total)
                    }}</span>
                    <span v-else>—</span>
                </TableCell>
            </TableRow>
        </TableFooter>
    </DataTable>
</template>
