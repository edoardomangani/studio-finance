<script setup lang="ts">
/**
 * InvoicesMobileList — faccia mobile (<lg) di InvoicesTable: lista "transaction
 * row" con monogramma cliente, importo a destra e meta (numero · data). Niente
 * kebab: la riga è un Link al dettaglio, dove vivono le azioni. Il breakdown
 * (imponibile/bollo/cassa/art.15) sta nel dettaglio, non qui.
 */
import { Link } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { useInitials } from '@/composables/useInitials';
import { formatDateIT, formatEUR } from '@/lib/format';
import { withOrigin } from '@/lib/origin';
import type { OriginCrumb } from '@/lib/origin';
import { show as invoiceShow } from '@/routes/invoices';
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

const { getInitials } = useInitials();
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
                <Link
                    :href="withOrigin(invoiceShow(invoice.id).url, props.origin)"
                    class="flex items-center gap-3 py-2.5 transition-colors active:bg-accent"
                >
                    <span
                        class="flex size-9 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-semibold text-foreground"
                        aria-hidden="true"
                    >
                        {{ getInitials(invoice.client.name) }}
                    </span>

                    <div class="min-w-0 flex-1">
                        <div class="truncate font-medium text-foreground">
                            {{ invoice.client.name }}
                        </div>
                        <div
                            class="mt-0.5 truncate text-xs text-muted-foreground"
                        >
                            n. {{ invoice.number }} ·
                            {{ formatDateIT(invoice.issued_at) }}
                        </div>
                    </div>

                    <div class="flex shrink-0 flex-col items-end gap-0.5">
                        <span
                            class="tabular text-sm font-semibold"
                            :class="
                                invoice.total < 0
                                    ? 'text-destructive'
                                    : 'text-foreground'
                            "
                        >
                            {{ formatEUR(invoice.total) }}
                        </span>
                        <Badge
                            v-if="invoice.bank_withholding"
                            variant="outline"
                            class="py-0 text-2xs"
                        >
                            Ritenuta
                        </Badge>
                    </div>
                </Link>
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
