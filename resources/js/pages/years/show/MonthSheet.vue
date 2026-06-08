<script setup lang="ts">
/**
 * MonthSheet — dettaglio di un mese, aperto dal click su una riga della
 * tabella mensile (Panoramica). Sola lettura: riepilogo del mese + tabella
 * fatture (riga → dettaglio) e tabella spese accantonate. Si alimenta dai dati
 * di riga, nessuna fetch. `ResponsiveDialog mode="sheet"` → pannello destro su
 * desktop, bottom sheet su mobile.
 */
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import ResponsiveDialog from '@/components/ResponsiveDialog.vue';
import {
    DataTable,
    DataTableBody,
    DataTableHeader,
    DataTableRow,
    TableCell,
    TableEmpty,
    TableHead,
} from '@/components/ui/table';
import { formatDateIT, formatEUR, formatPercent } from '@/lib/format';
import { withOrigin } from '@/lib/origin';
import type { OriginCrumb } from '@/lib/origin';
import { show as invoiceShow } from '@/routes/invoices';
import type { YearMonth } from '@/types';

// `origin`: trail di breadcrumb dell'anno, propagato ai deep-link a fattura.
const props = withDefaults(
    defineProps<{ month: YearMonth | null; origin?: OriginCrumb[] }>(),
    { origin: () => [] },
);
const open = defineModel<boolean>('open', { default: false });

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

const title = computed(() =>
    props.month ? MONTHS[props.month.month - 1] : '',
);

const ratioText = computed(() => {
    const m = props.month;

    return m && m.invoice_total > 0
        ? formatPercent(m.net_to_gross_ratio * 100)
        : '—';
});
</script>

<template>
    <ResponsiveDialog
        v-model:open="open"
        mode="sheet"
        :title="title"
        description="Dettaglio del mese"
    >
        <div v-if="month" class="flex flex-col gap-7">
            <!-- Riepilogo. Imponibile/bolli/volume affari sono la composizione
                 del fatturato (non addendi del totale): lista piatta. L'unica
                 somma vera è Netto = Totale fatture − Spese, marcata dalla riga. -->
            <dl class="text-13">
                <div class="flex items-baseline justify-between gap-4 py-1">
                    <dt class="text-muted-foreground">Imponibile</dt>
                    <dd class="tabular text-foreground">
                        {{ formatEUR(month.taxable_amount) }}
                    </dd>
                </div>
                <div class="flex items-baseline justify-between gap-4 py-1">
                    <dt class="text-muted-foreground">Bolli</dt>
                    <dd class="tabular text-foreground">
                        {{ formatEUR(month.stamp_duty) }}
                    </dd>
                </div>
                <div class="flex items-baseline justify-between gap-4 py-1">
                    <dt class="text-muted-foreground">Volume affari IVA</dt>
                    <dd class="tabular text-foreground">
                        {{ formatEUR(month.vat_turnover) }}
                    </dd>
                </div>

                <div
                    class="mt-3 flex items-baseline justify-between gap-4 py-1"
                >
                    <dt class="text-foreground">Totale fatture</dt>
                    <dd class="tabular text-foreground">
                        {{ formatEUR(month.invoice_total) }}
                    </dd>
                </div>
                <div class="flex items-baseline justify-between gap-4 py-1">
                    <dt class="text-muted-foreground">Spese accantonate</dt>
                    <dd class="tabular text-muted-foreground">
                        − {{ formatEUR(month.total_expenses) }}
                    </dd>
                </div>
                <div
                    class="flex items-baseline justify-between gap-4 border-t border-border pt-2"
                >
                    <dt class="font-medium text-foreground">Netto</dt>
                    <dd class="tabular font-medium text-foreground">
                        {{ formatEUR(month.net) }}
                    </dd>
                </div>
                <div class="mt-1 flex items-baseline justify-between gap-4">
                    <dt class="text-muted-foreground">
                        Rapporto netto / lordo
                    </dt>
                    <dd class="tabular text-muted-foreground">
                        {{ ratioText }}
                    </dd>
                </div>
            </dl>

            <section>
                <h3 class="kicker mb-2 text-muted-foreground">Fatture</h3>
                <DataTable>
                    <DataTableHeader :has-actions="false">
                        <TableHead>Fattura</TableHead>
                        <TableHead class="w-[110px] text-right"
                            >Totale</TableHead
                        >
                    </DataTableHeader>
                    <DataTableBody>
                        <TableEmpty
                            v-if="month.invoices.length === 0"
                            :colspan="2"
                        >
                            Nessuna fattura nel mese.
                        </TableEmpty>
                        <DataTableRow
                            v-for="inv in month.invoices"
                            v-else
                            :key="inv.id"
                            interactive
                            @click="router.visit(withOrigin(invoiceShow(inv.id).url, origin))"
                        >
                            <TableCell>
                                <Link
                                    :href="withOrigin(invoiceShow(inv.id).url, origin)"
                                    class="font-medium text-foreground outline-none focus-visible:underline"
                                    @click.stop
                                >
                                    {{ inv.number }}
                                </Link>
                                <span class="text-muted-foreground">
                                    · {{ formatDateIT(inv.issued_at) }}</span
                                >
                                <span
                                    class="block truncate text-xs text-muted-foreground"
                                >
                                    {{ inv.client.name }}
                                </span>
                            </TableCell>
                            <TableCell
                                class="tabular text-right text-foreground"
                                >{{ formatEUR(inv.total) }}</TableCell
                            >
                        </DataTableRow>
                    </DataTableBody>
                </DataTable>
            </section>

            <section>
                <h3 class="kicker mb-2 text-muted-foreground">
                    Spese accantonate
                </h3>
                <DataTable>
                    <DataTableHeader :has-actions="false">
                        <TableHead>Voce</TableHead>
                        <TableHead class="w-[120px] text-right"
                            >Accantonato</TableHead
                        >
                    </DataTableHeader>
                    <DataTableBody>
                        <TableEmpty v-if="month.expenses.length === 0" :colspan="2">
                            Nessuna spesa accantonata nel mese.
                        </TableEmpty>
                        <DataTableRow v-for="e in month.expenses" v-else :key="e.id">
                            <TableCell class="text-foreground">{{
                                e.name
                            }}</TableCell>
                            <TableCell
                                class="tabular text-right text-muted-foreground"
                                >{{ formatEUR(e.expected) }}</TableCell
                            >
                        </DataTableRow>
                    </DataTableBody>
                </DataTable>
            </section>
        </div>
    </ResponsiveDialog>
</template>
