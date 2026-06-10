<script setup lang="ts">
/**
 * Dashboard generale (Fase 9.b): mese in corso, "da coprire" cross-anno e
 * pannello anno (fascia hero), spese del mese scomposte e tre liste compatte
 * (prossime scadenze, ultime fatture e pagamenti). Le liste riusano il pattern
 * `DataTable` della Panoramica anno. Empty state quando non c'è alcun anno.
 */
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import { PhArrowRight, PhCalendarBlank } from '@phosphor-icons/vue';
import { computed } from 'vue';
import StackedBar from '@/components/charts/StackedBar.vue';
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
import { EXPENSE_KIND_META } from '@/lib/expenseKind';
import { formatDateIT, formatEUR } from '@/lib/format';
import DashboardHero from '@/pages/dashboard/DashboardHero.vue';
import { index as deadlinesIndex } from '@/routes/deadlines';
import { index as invoicesIndex } from '@/routes/invoices';
import { index as paymentsIndex } from '@/routes/payments';
import { show as yearShow } from '@/routes/years';
import type { DashboardData } from '@/types';

const props = defineProps<{ dashboard: DashboardData }>();

setLayoutProps({ pageTitle: 'Dashboard' });

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

// Variante "piena" (null su empty state): narrowing per il template e i figli.
const data = computed(() =>
    props.dashboard.has_data ? props.dashboard : null,
);

// Mese mostrato (in corso se ha fatturato, altrimenti il precedente): nome e anno
// vengono dal backend, non dalla data di oggi.
const monthName = computed(() =>
    data.value ? MONTHS[data.value.display_month - 1] : '',
);

// Totale spese del mese per l'header; la composizione (barra + legenda
// interattiva) vive in [[StackedBar]].
const accrualTotal = computed(() =>
    data.value
        ? data.value.month_expenses.reduce((sum, e) => sum + e.amount, 0)
        : 0,
);

// Spese del mese → segmenti barra: colore fisso per famiglia, nome dell'utente.
const monthExpenseItems = computed(() =>
    (data.value?.month_expenses ?? []).map((e) => ({
        id: e.kind,
        label: e.label,
        amount: e.amount,
        color: EXPENSE_KIND_META[e.kind].color,
    })),
);
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col gap-8">
        <!-- Empty state: nessun anno aperto. -->
        <div
            v-if="!data"
            class="rounded-lg border border-dashed border-border px-4 py-12 text-center"
        >
            <p class="text-13 font-medium text-foreground">
                Nessun anno aperto
            </p>
            <p class="mx-auto mt-1 max-w-md text-13 text-muted-foreground">
                Apri il tuo primo anno per vedere mese in corso, spese da
                coprire e andamento.
            </p>
        </div>

        <template v-else>
            <!-- Azione di pagina nel topbar, come le altre viste. -->
            <Teleport to="#page-topbar-actions" defer>
                <Button
                    variant="secondary"
                    size="sm"
                    @click="router.visit(yearShow(data.current_year).url)"
                >
                    <PhCalendarBlank :size="14" />
                    Vai all'anno {{ data.current_year }}
                    <PhArrowRight :size="14" />
                </Button>
            </Teleport>

            <DashboardHero
                :month-name="monthName"
                :display-year="data.display_year"
                :previous-year="data.display_year - 1"
                :this-month="data.this_month"
                :to-cover="data.to_cover"
                :year="data.year"
            />

            <!-- Spese del mese (barra impilata + legenda) + Scadenze aperte: la
                 lista scadenze è il box "lungo", il box Spese si allunga (flex-1 +
                 justify-between) per allineare il fondo. -->
            <div class="grid items-stretch gap-4 md:grid-cols-2">
                <!-- Sempre presente (anche a 0) per non far collassare la griglia. -->
                <section class="flex flex-col gap-2">
                    <header class="flex items-baseline justify-between gap-3">
                        <h2 class="kicker text-muted-foreground">
                            Spese del mese · {{ monthName }}
                            {{ data.display_year }}
                        </h2>
                        <span
                            class="tabular text-13 font-medium text-foreground"
                            >{{ formatEUR(accrualTotal) }}</span
                        >
                    </header>
                    <StackedBar :items="monthExpenseItems" class="flex-1" />
                </section>

                <section class="flex flex-col gap-2">
                    <header class="flex items-baseline justify-between gap-3">
                        <h2 class="kicker text-muted-foreground">
                            Scadenze aperte
                        </h2>
                        <Button
                            variant="link"
                            size="sm"
                            class="h-auto gap-1 p-0"
                            @click="router.visit(deadlinesIndex().url)"
                        >
                            Tutte
                            <PhArrowRight :size="13" />
                        </Button>
                    </header>
                    <DataTable>
                        <DataTableHeader :has-actions="false">
                            <TableHead>Scadenza</TableHead>
                            <TableHead class="w-[120px] text-right"
                                >Previsto</TableHead
                            >
                        </DataTableHeader>
                        <DataTableBody>
                            <TableEmpty
                                v-if="data.open_deadlines.length === 0"
                                :colspan="2"
                            >
                                Nessuna scadenza aperta.
                            </TableEmpty>
                            <DataTableRow
                                v-for="d in data.open_deadlines"
                                v-else
                                :key="d.id"
                            >
                                <TableCell>
                                    <span
                                        class="block truncate text-foreground"
                                        >{{ d.name }}</span
                                    >
                                    <span
                                        class="block text-xs text-muted-foreground"
                                        >{{ formatDateIT(d.due_at)
                                        }}<template v-if="d.year">
                                            · {{ d.year }}</template
                                        ></span
                                    >
                                </TableCell>
                                <TableCell
                                    class="tabular text-right text-foreground"
                                >
                                    <span v-if="d.expected_amount !== null">{{
                                        formatEUR(d.expected_amount)
                                    }}</span>
                                    <span v-else class="text-muted-foreground"
                                        >—</span
                                    >
                                </TableCell>
                            </DataTableRow>
                        </DataTableBody>
                    </DataTable>
                </section>
            </div>

            <!-- Ultime fatture + Ultimi pagamenti -->
            <div class="grid gap-4 md:grid-cols-2">
                <section class="flex flex-col gap-2">
                    <header class="flex items-baseline justify-between gap-3">
                        <h2 class="kicker text-muted-foreground">
                            Ultime fatture
                        </h2>
                        <Button
                            variant="link"
                            size="sm"
                            class="h-auto gap-1 p-0"
                            @click="router.visit(invoicesIndex().url)"
                        >
                            Tutte
                            <PhArrowRight :size="13" />
                        </Button>
                    </header>
                    <DataTable>
                        <DataTableHeader :has-actions="false">
                            <TableHead>Fattura</TableHead>
                            <TableHead class="w-[110px] text-right"
                                >Totale</TableHead
                            >
                        </DataTableHeader>
                        <DataTableBody>
                            <TableEmpty
                                v-if="data.recent_invoices.length === 0"
                                :colspan="2"
                            >
                                Nessuna fattura.
                            </TableEmpty>
                            <DataTableRow
                                v-for="inv in data.recent_invoices"
                                v-else
                                :key="inv.id"
                            >
                                <TableCell>
                                    <span class="font-medium text-foreground">{{
                                        inv.number
                                    }}</span>
                                    <span class="text-muted-foreground">
                                        ·
                                        {{ formatDateIT(inv.issued_at) }}</span
                                    >
                                    <span
                                        v-if="inv.client_name"
                                        class="block truncate text-xs text-muted-foreground"
                                        >{{ inv.client_name }}</span
                                    >
                                </TableCell>
                                <TableCell
                                    class="tabular text-right text-foreground"
                                    >{{ formatEUR(inv.total) }}</TableCell
                                >
                            </DataTableRow>
                        </DataTableBody>
                    </DataTable>
                </section>

                <section class="flex flex-col gap-2">
                    <header class="flex items-baseline justify-between gap-3">
                        <h2 class="kicker text-muted-foreground">
                            Ultimi pagamenti
                        </h2>
                        <Button
                            variant="link"
                            size="sm"
                            class="h-auto gap-1 p-0"
                            @click="router.visit(paymentsIndex().url)"
                        >
                            Tutti
                            <PhArrowRight :size="13" />
                        </Button>
                    </header>
                    <DataTable>
                        <DataTableHeader :has-actions="false">
                            <TableHead>Pagamento</TableHead>
                            <TableHead class="w-[110px] text-right"
                                >Importo</TableHead
                            >
                        </DataTableHeader>
                        <DataTableBody>
                            <TableEmpty
                                v-if="data.recent_payments.length === 0"
                                :colspan="2"
                            >
                                Nessun pagamento.
                            </TableEmpty>
                            <DataTableRow
                                v-for="p in data.recent_payments"
                                v-else
                                :key="p.id"
                            >
                                <TableCell>
                                    <span
                                        class="block truncate text-foreground"
                                        >{{
                                            p.description ??
                                            p.annual_expense_name ??
                                            'Pagamento'
                                        }}</span
                                    >
                                    <span
                                        class="block text-xs text-muted-foreground"
                                        >{{ formatDateIT(p.paid_at) }}</span
                                    >
                                </TableCell>
                                <TableCell
                                    class="tabular text-right text-foreground"
                                    >{{ formatEUR(p.amount) }}</TableCell
                                >
                            </DataTableRow>
                        </DataTableBody>
                    </DataTable>
                </section>
            </div>
        </template>
    </div>
</template>
