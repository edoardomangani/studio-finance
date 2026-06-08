<script setup lang="ts">
/**
 * Liste compatte della Panoramica: ultime fatture (deep-link al dettaglio, con
 * origine Anni/anno) e prossime scadenze aperte (riga → side-sheet). I "Tutte"
 * rimandano ai rispettivi tab via l'evento `go`.
 */
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DataTable,
    DataTableBody,
    DataTableHeader,
    DataTableRow,
    TableCell,
    TableEmpty,
    TableHead,
} from '@/components/ui/table';
import { formatDateIT, formatEUR } from '@/lib/format';
import { withOrigin } from '@/lib/origin';
import type { OriginCrumb } from '@/lib/origin';
import DeadlineSheet from '@/pages/deadlines/DeadlineSheet.vue';
import { show as invoiceShow } from '@/routes/invoices';
import type { DeadlineListItem, YearShow } from '@/types';

const props = defineProps<{ year: YearShow; origin: OriginCrumb[] }>();
const emit = defineEmits<{ (e: 'go', tab: string): void }>();

const LIMIT = 10;

const recentInvoices = computed(() => props.year.invoices.slice(0, LIMIT));
const openDeadlines = computed(() =>
    props.year.deadlines.filter((d) => d.status === 'open'),
);
const upcomingDeadlines = computed(() => openDeadlines.value.slice(0, LIMIT));

// Contatore "N di M" quando l'elenco è troncato (altrimenti vuoto).
function shownOf(shown: number, total: number): string {
    return total > shown ? `${shown} di ${total}` : String(total);
}

// Side-sheet scadenza: stesso dettaglio della lista Scadenze.
const sheetOpen = ref(false);
const selected = ref<DeadlineListItem | null>(null);

function openDeadline(deadline: DeadlineListItem): void {
    selected.value = deadline;
    sheetOpen.value = true;
}
</script>

<template>
    <div class="grid gap-6 lg:grid-cols-2">
        <section>
            <header class="mb-2 flex items-baseline justify-between gap-3">
                <h2 class="kicker text-muted-foreground">
                    Ultime fatture
                    <span class="tabular ml-1 font-normal normal-case">{{ shownOf(recentInvoices.length, year.invoices.length) }}</span>
                </h2>
                <Button variant="link" size="sm" class="h-auto p-0" @click="emit('go', 'invoices')">
                    Tutte
                </Button>
            </header>
            <DataTable>
                <DataTableHeader :has-actions="false">
                    <TableHead>Fattura</TableHead>
                    <TableHead class="w-[110px] text-right">Totale</TableHead>
                </DataTableHeader>
                <DataTableBody>
                    <TableEmpty v-if="recentInvoices.length === 0" :colspan="2">
                        Nessuna fattura in quest'anno.
                    </TableEmpty>
                    <DataTableRow
                        v-for="inv in recentInvoices"
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
                            <span class="text-muted-foreground"> · {{ formatDateIT(inv.issued_at) }}</span>
                            <span class="block truncate text-xs text-muted-foreground">{{ inv.client.name }}</span>
                        </TableCell>
                        <TableCell class="tabular text-right text-foreground">{{ formatEUR(inv.total) }}</TableCell>
                    </DataTableRow>
                </DataTableBody>
            </DataTable>
        </section>

        <section>
            <header class="mb-2 flex items-baseline justify-between gap-3">
                <h2 class="kicker text-muted-foreground">
                    Scadenze aperte
                    <span class="tabular ml-1 font-normal normal-case">{{ shownOf(upcomingDeadlines.length, openDeadlines.length) }}</span>
                </h2>
                <Button variant="link" size="sm" class="h-auto p-0" @click="emit('go', 'deadlines')">
                    Tutte
                </Button>
            </header>
            <DataTable>
                <DataTableHeader :has-actions="false">
                    <TableHead>Scadenza</TableHead>
                    <TableHead class="w-[120px] text-right">Previsto</TableHead>
                </DataTableHeader>
                <DataTableBody>
                    <TableEmpty v-if="upcomingDeadlines.length === 0" :colspan="2">
                        Nessuna scadenza aperta.
                    </TableEmpty>
                    <DataTableRow
                        v-for="d in upcomingDeadlines"
                        v-else
                        :key="d.id"
                        interactive
                        :active="sheetOpen && selected?.id === d.id"
                        @click="openDeadline(d)"
                    >
                        <TableCell>
                            <span class="block truncate text-foreground">{{ d.name }}</span>
                            <span class="block text-xs text-muted-foreground">{{ formatDateIT(d.due_at) }}</span>
                        </TableCell>
                        <TableCell class="tabular text-right text-foreground">
                            <span v-if="d.expected_amount !== null">{{ formatEUR(d.expected_amount) }}</span>
                            <span v-else class="text-muted-foreground">—</span>
                        </TableCell>
                    </DataTableRow>
                </DataTableBody>
            </DataTable>
        </section>

        <DeadlineSheet v-model:open="sheetOpen" :deadline="selected" />
    </div>
</template>
