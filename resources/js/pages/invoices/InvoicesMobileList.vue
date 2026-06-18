<script setup lang="ts">
/**
 * InvoicesMobileList — faccia mobile (<lg) di InvoicesTable: lista a card con
 * solo l'essenziale (cliente, totale, numero·data, ritenuta). Il breakdown
 * vive nel dettaglio. Stesso click→dettaglio e stesse azioni della tabella;
 * l'archiviazione è delegata al parent (che possiede il ConfirmDialog).
 */
import { Link, router } from '@inertiajs/vue3';
import {
    PhArchive,
    PhCopy,
    PhDotsThreeVertical,
    PhPencil,
} from '@phosphor-icons/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { formatDateIT, formatEUR } from '@/lib/format';
import { withOrigin } from '@/lib/origin';
import type { OriginCrumb } from '@/lib/origin';
import {
    create as invoiceCreate,
    edit as invoiceEdit,
    show as invoiceShow,
} from '@/routes/invoices';
import type { InvoiceListItem } from '@/types';

const props = withDefaults(
    defineProps<{
        invoices: InvoiceListItem[];
        origin?: OriginCrumb[];
        withTotals?: boolean;
        total?: number;
    }>(),
    { origin: () => [], withTotals: false, total: 0 },
);

const emit = defineEmits<{ archive: [invoice: InvoiceListItem] }>();

function goToInvoice(invoice: InvoiceListItem): void {
    router.visit(withOrigin(invoiceShow(invoice.id).url, props.origin));
}
</script>

<template>
    <div>
        <div
            v-if="invoices.length === 0"
            class="py-10 text-center text-sm text-muted-foreground"
        >
            <slot name="empty">Nessuna fattura.</slot>
        </div>
        <ul v-else class="divide-y divide-border">
            <li v-for="invoice in invoices" :key="invoice.id">
                <div
                    class="flex items-center gap-2 py-3 transition-colors active:bg-accent"
                    role="button"
                    tabindex="0"
                    @click="goToInvoice(invoice)"
                    @keydown.enter="goToInvoice(invoice)"
                >
                    <div class="min-w-0 flex-1">
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="truncate font-medium text-foreground">
                                {{ invoice.client.name }}
                            </span>
                            <span
                                class="tabular shrink-0 text-sm font-medium"
                                :class="
                                    invoice.total < 0
                                        ? 'text-destructive'
                                        : 'text-foreground'
                                "
                            >
                                {{ formatEUR(invoice.total) }}
                            </span>
                        </div>
                        <div
                            class="mt-0.5 flex items-center gap-1.5 text-xs text-muted-foreground"
                        >
                            <span class="tabular truncate">{{
                                invoice.number
                            }}</span>
                            <span aria-hidden="true">·</span>
                            <span class="tabular shrink-0">{{
                                formatDateIT(invoice.issued_at)
                            }}</span>
                            <Badge
                                v-if="invoice.bank_withholding"
                                variant="outline"
                                class="ml-0.5 shrink-0 py-0 text-2xs"
                            >
                                Ritenuta
                            </Badge>
                        </div>
                    </div>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                class="shrink-0 text-muted-foreground"
                                aria-label="Azioni"
                                @click.stop
                            >
                                <PhDotsThreeVertical :size="18" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
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
                            <DropdownMenuItem as-child>
                                <Link
                                    :href="
                                        invoiceCreate.url({
                                            query: { from: invoice.id },
                                        })
                                    "
                                >
                                    <PhCopy :size="14" />
                                    Duplica
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                variant="destructive"
                                @select="emit('archive', invoice)"
                            >
                                <PhArchive :size="14" />
                                Archivia
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </li>
        </ul>
        <div
            v-if="withTotals && invoices.length > 0"
            class="flex items-center justify-between border-t border-border py-3 font-medium"
        >
            <span class="text-foreground">Totale</span>
            <span
                class="tabular"
                :class="total < 0 ? 'text-destructive' : 'text-foreground'"
            >
                {{ formatEUR(total) }}
            </span>
        </div>
    </div>
</template>
