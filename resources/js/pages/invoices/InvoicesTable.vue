<script setup lang="ts">
/**
 * InvoicesTable — tabella fatture condivisa tra la lista (invoices/Index) e il
 * tab Fatture della vista anno. Auto-contenuta: colonne, click-riga → dettaglio,
 * kebab Modifica/Archivia con ConfirmDialog. Il chrome (search, filtri,
 * paginazione) resta al chiamante; lo stato vuoto è personalizzabile via slot.
 */
import { Link, router } from '@inertiajs/vue3';
import { PhArchive, PhPencil } from '@phosphor-icons/vue';
import { computed } from 'vue';
import InvoiceController from '@/actions/App/Http/Controllers/InvoiceController';
import { Badge } from '@/components/ui/badge';
import { ConfirmDialog } from '@/components/ui/dialog';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import {
    DataTable,
    DataTableBody,
    DataTableHeader,
    DataTableRow,
    TableCell,
    TableEmpty,
    TableFooter,
    TableHead,
    TableRow,
} from '@/components/ui/table';
import { useArchiveAction } from '@/composables/useArchiveAction';
import { formatDateIT, formatEUR } from '@/lib/format';
import { withOrigin } from '@/lib/origin';
import type { OriginCrumb } from '@/lib/origin';
import { edit as invoiceEdit, show as invoiceShow } from '@/routes/invoices';
import type { InvoiceListItem } from '@/types';

// `origin`: trail di breadcrumb della superficie ospitante (lista o vista
// anno), propagato a dettaglio/modifica così la pagina ricorda da dove arrivi.
// `withTotals`: footer con le somme di colonna. Da attivare SOLO quando le
// `invoices` sono il set completo (vista anno), mai sulla lista paginata.
const props = withDefaults(
    defineProps<{
        invoices: InvoiceListItem[];
        origin?: OriginCrumb[];
        withTotals?: boolean;
    }>(),
    {
        origin: () => [],
        withTotals: false,
    },
);

const { archiveOpen, archiveTarget, askArchive, confirmArchive } =
    useArchiveAction<InvoiceListItem>((invoice) =>
        InvoiceController.destroy.url({ invoice: invoice.id }),
    );

const totals = computed(() => {
    const sum = (pick: (i: InvoiceListItem) => number): number =>
        props.invoices.reduce((acc, i) => acc + pick(i), 0);

    return {
        amount: sum((i) => i.amount),
        stamp: sum((i) => i.stamp_amount),
        inarcassa: sum((i) => i.inarcassa_amount),
        art15: sum((i) => i.art_15_amount),
        total: sum((i) => i.total),
    };
});
</script>

<template>
    <DataTable>
        <DataTableHeader>
            <TableHead class="w-[100px]">Data</TableHead>
            <TableHead class="w-[110px]">Numero</TableHead>
            <TableHead>Cliente</TableHead>
            <TableHead class="text-right">Imponibile</TableHead>
            <TableHead class="text-right">Bollo</TableHead>
            <TableHead class="text-right">Cassa</TableHead>
            <TableHead class="text-right">Art.15</TableHead>
            <TableHead class="text-right">Totale</TableHead>
            <TableHead class="w-[80px] text-right">Ritenuta</TableHead>
        </DataTableHeader>
        <DataTableBody>
            <TableEmpty v-if="invoices.length === 0" :colspan="10">
                <slot name="empty">Nessuna fattura.</slot>
            </TableEmpty>
            <DataTableRow
                v-for="invoice in invoices"
                v-else
                :key="invoice.id"
                interactive
                @click="
                    router.visit(
                        withOrigin(invoiceShow(invoice.id).url, props.origin),
                    )
                "
            >
                <TableCell class="tabular text-muted-foreground">
                    {{ formatDateIT(invoice.issued_at) }}
                </TableCell>
                <TableCell class="tabular font-medium text-foreground">
                    <span
                        class="block max-w-[110px] truncate"
                        :title="invoice.number"
                        >{{ invoice.number }}</span
                    >
                </TableCell>
                <TableCell class="text-foreground">
                    <span class="block truncate" :title="invoice.client.name">{{
                        invoice.client.name
                    }}</span>
                </TableCell>
                <TableCell class="tabular text-right text-muted-foreground">{{
                    formatEUR(invoice.amount)
                }}</TableCell>
                <TableCell class="tabular text-right text-muted-foreground">{{
                    formatEUR(invoice.stamp_amount)
                }}</TableCell>
                <TableCell class="tabular text-right text-muted-foreground">{{
                    formatEUR(invoice.inarcassa_amount)
                }}</TableCell>
                <TableCell class="tabular text-right text-muted-foreground">{{
                    formatEUR(invoice.art_15_amount)
                }}</TableCell>
                <TableCell
                    class="tabular text-right font-medium text-foreground"
                    >{{ formatEUR(invoice.total) }}</TableCell
                >
                <TableCell class="text-right">
                    <Badge v-if="invoice.bank_withholding" variant="outline"
                        >Sì</Badge
                    >
                    <span v-else class="text-muted-foreground">—</span>
                </TableCell>
                <template #actions>
                    <DropdownMenuItem as-child>
                        <Link
                            :href="
                                withOrigin(
                                    invoiceEdit(invoice.id).url,
                                    props.origin,
                                )
                            "
                        >
                            <PhPencil :size="14" />
                            Modifica
                        </Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        variant="destructive"
                        @select="askArchive(invoice)"
                    >
                        <PhArchive :size="14" />
                        Archivia
                    </DropdownMenuItem>
                </template>
            </DataTableRow>
        </DataTableBody>
        <TableFooter v-if="withTotals && invoices.length > 0">
            <TableRow class="font-medium">
                <TableCell class="text-foreground">Totale</TableCell>
                <TableCell />
                <TableCell />
                <TableCell class="tabular text-right text-foreground">{{ formatEUR(totals.amount) }}</TableCell>
                <TableCell class="tabular text-right text-foreground">{{ formatEUR(totals.stamp) }}</TableCell>
                <TableCell class="tabular text-right text-foreground">{{ formatEUR(totals.inarcassa) }}</TableCell>
                <TableCell class="tabular text-right text-foreground">{{ formatEUR(totals.art15) }}</TableCell>
                <TableCell class="tabular text-right text-foreground">{{ formatEUR(totals.total) }}</TableCell>
                <TableCell />
                <TableCell />
            </TableRow>
        </TableFooter>
    </DataTable>

    <ConfirmDialog
        v-model:open="archiveOpen"
        title="Archiviare la fattura?"
        :description="
            archiveTarget
                ? `«${archiveTarget.number}» verrà nascosta dall'elenco. I dati restano per i calcoli storici.`
                : undefined
        "
        confirm-label="Archivia"
        destructive
        @confirm="confirmArchive"
    />
</template>
