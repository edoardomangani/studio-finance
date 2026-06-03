<script setup lang="ts">
/**
 * Payments — Index page.
 *
 * Vista pluriennale dei pagamenti (50/pagina, RB9). Subbar: search live sulla
 * descrizione + bottone "Filtri" (stato, anno spesa, anno pagamento) in aside
 * desktop o Sheet mobile. Il pagamento manuale extra-scadenza (F8) si crea dal
 * CTA in alto a destra.
 *
 * Specchia [[deadlines/Index.vue]] per chrome (search + FilterPanel + tabella
 * densa), senza il toggle di stato (lo stato vive nel pannello, tre facet).
 */
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import { PhFunnel, PhMagnifyingGlass, PhPlus } from '@phosphor-icons/vue';
import { computed, onUnmounted, ref, watch } from 'vue';
import FilterPanel from '@/components/FilterPanel.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { InputGroup, InputGroupAddon, InputGroupInput } from '@/components/ui/input-group';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDateIT, formatEUR } from '@/lib/format';
import PaymentFilters from '@/pages/payments/PaymentFilters.vue';
import PaymentFormDialog from '@/pages/payments/PaymentFormDialog.vue';
import { PAYMENT_STATUS_META } from '@/pages/payments/statusMeta';
import { index as paymentsIndex } from '@/routes/payments';
import type {
    AnnualExpenseForPicker,
    EnumOption,
    PaginatedList,
    PaymentFilterState,
    PaymentListItem,
    PaymentStatus,
} from '@/types';

const props = defineProps<{
    payments: PaginatedList<PaymentListItem>;
    filters: {
        search: string;
        status: PaymentStatus[];
        expense_year: number[];
        paid_year: number[];
    };
    availableExpenseYears: number[];
    availablePaidYears: number[];
    statusOptions: EnumOption[];
    annualExpenses: AnnualExpenseForPicker[];
}>();

setLayoutProps({
    pageTitle: 'Pagamenti',
    pageCrumbs: [{ label: 'Pagamenti' }],
    subbar: true,
});

// Dialog di registrazione pagamento manuale extra-scadenza (F8).
const createOpen = ref(false);

// Search reattiva con debounce 250ms.
const searchTerm = ref(props.filters.search);
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(searchTerm, (value) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => applyFilters({ search: value }), 250);
});

onUnmounted(() => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
});

const filtersOpen = ref(false);

const filterState = ref<PaymentFilterState>({
    status: props.filters.status,
    expenseYear: props.filters.expense_year,
    paidYear: props.filters.paid_year,
});

watch(
    () => [props.filters.status, props.filters.expense_year, props.filters.paid_year] as const,
    ([status, expenseYear, paidYear]) => {
        filterState.value = { status, expenseYear, paidYear };
    },
);

const activeFilterCount = computed(() =>
    [props.filters.status, props.filters.expense_year, props.filters.paid_year].filter(
        (v) => v.length > 0,
    ).length,
);

const hasActiveFilters = computed(() => activeFilterCount.value > 0 || !!props.filters.search);

function applyPanelFilters(): void {
    applyFilters({
        status: filterState.value.status,
        expense_year: filterState.value.expenseYear,
        paid_year: filterState.value.paidYear,
    });
}

function clearAllFilters(): void {
    filterState.value = { status: [], expenseYear: [], paidYear: [] };
    applyFilters({ status: [], expense_year: [], paid_year: [] });
}

// Array vuoto → undefined (omette il parametro dalla query).
function nonEmpty<T>(a: T[]): T[] | undefined {
    return a.length > 0 ? a : undefined;
}

function applyFilters(next: {
    search?: string;
    status?: PaymentStatus[];
    expense_year?: number[];
    paid_year?: number[];
}): void {
    router.get(
        paymentsIndex().url,
        {
            search: (next.search ?? props.filters.search) || undefined,
            status: nonEmpty(next.status ?? props.filters.status),
            expense_year: nonEmpty(next.expense_year ?? props.filters.expense_year),
            paid_year: nonEmpty(next.paid_year ?? props.filters.paid_year),
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function goToPage(page: number): void {
    router.get(
        paymentsIndex().url,
        {
            search: props.filters.search || undefined,
            status: nonEmpty(props.filters.status),
            expense_year: nonEmpty(props.filters.expense_year),
            paid_year: nonEmpty(props.filters.paid_year),
            page,
        },
        { preserveState: true, preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Pagamenti" />

    <Teleport to="#page-topbar-actions" defer>
        <Button size="sm" @click="createOpen = true">
            <PhPlus :size="14" weight="bold" />
            Nuovo pagamento
        </Button>
    </Teleport>

    <Teleport to="#page-topbar-search" defer>
        <InputGroup>
            <InputGroupAddon>
                <PhMagnifyingGlass :size="14" />
            </InputGroupAddon>
            <InputGroupInput
                v-model="searchTerm"
                type="search"
                placeholder="Cerca pagamento…"
            />
        </InputGroup>
    </Teleport>

    <Teleport to="#page-topbar-filters" defer>
        <Button
            type="button"
            variant="outline"
            size="sm"
            class="relative"
            :aria-pressed="filtersOpen"
            @click="filtersOpen = !filtersOpen"
        >
            <PhFunnel :size="14" />
            Filtri
            <Badge
                v-if="activeFilterCount > 0"
                variant="secondary"
                class="ml-1 h-4 min-w-4 px-1 text-2xs tabular"
            >
                {{ activeFilterCount }}
            </Badge>
        </Button>
    </Teleport>

    <Table boxed>
        <TableHeader>
            <TableRow>
                <TableHead class="w-[100px]">Data</TableHead>
                <TableHead>Descrizione</TableHead>
                <TableHead>Voce di spesa</TableHead>
                <TableHead class="w-[110px]">Origine</TableHead>
                <TableHead class="w-[70px] text-right">Anno</TableHead>
                <TableHead class="w-[120px] text-right">Importo</TableHead>
                <TableHead class="w-[120px]">Stato</TableHead>
            </TableRow>
        </TableHeader>
        <TableBody>
            <TableEmpty v-if="payments.data.length === 0" :colspan="7">
                <span v-if="hasActiveFilters">Nessun pagamento trovato con questi filtri.</span>
                <span v-else>Nessun pagamento. Registrane uno dal pulsante in alto o dalle scadenze.</span>
            </TableEmpty>
            <TableRow v-for="payment in payments.data" v-else :key="payment.id">
                <TableCell class="tabular text-muted-foreground">
                    <span v-if="payment.paid_at">{{ formatDateIT(payment.paid_at) }}</span>
                    <span v-else>—</span>
                </TableCell>
                <TableCell class="text-foreground">
                    <span v-if="payment.description" class="block truncate" :title="payment.description">
                        {{ payment.description }}
                    </span>
                    <span v-else class="text-muted-foreground">—</span>
                </TableCell>
                <TableCell class="text-muted-foreground">
                    <span v-if="payment.annual_expense_name" class="block truncate" :title="payment.annual_expense_name">
                        {{ payment.annual_expense_name }}
                    </span>
                    <span v-else>—</span>
                </TableCell>
                <TableCell class="text-muted-foreground">
                    {{ payment.is_manual ? 'Manuale' : 'Scadenza' }}
                </TableCell>
                <TableCell class="tabular text-right text-muted-foreground">
                    {{ payment.expense_year ?? '—' }}
                </TableCell>
                <TableCell class="tabular text-right text-foreground">
                    <span v-if="payment.amount !== null">{{ formatEUR(payment.amount) }}</span>
                    <span v-else class="text-muted-foreground">—</span>
                </TableCell>
                <TableCell>
                    <Badge :variant="PAYMENT_STATUS_META[payment.status].variant" class="gap-1">
                        <component :is="PAYMENT_STATUS_META[payment.status].icon" :size="12" />
                        {{ payment.status_label }}
                    </Badge>
                </TableCell>
            </TableRow>
        </TableBody>
    </Table>

    <footer
        v-if="payments.last_page > 1"
        class="mt-3 flex flex-col items-stretch gap-2 sm:flex-row sm:items-center sm:justify-between"
    >
        <span class="tabular text-xs text-muted-foreground">
            {{ payments.from }}–{{ payments.to }} di {{ payments.total }}
        </span>
        <Pagination
            :total="payments.total"
            :items-per-page="payments.per_page"
            :page="payments.current_page"
            :sibling-count="1"
            show-edges
            @update:page="goToPage"
        >
            <PaginationContent v-slot="{ items }">
                <PaginationPrevious />
                <template v-for="(item, idx) in items">
                    <PaginationItem
                        v-if="item.type === 'page'"
                        :key="item.value"
                        :value="item.value"
                        :is-active="item.value === payments.current_page"
                    >
                        {{ item.value }}
                    </PaginationItem>
                    <PaginationEllipsis v-else :key="`e-${idx}`" :index="idx" />
                </template>
                <PaginationNext />
            </PaginationContent>
        </Pagination>
    </footer>

    <FilterPanel
        v-model:open="filtersOpen"
        :active-count="activeFilterCount"
        title="Filtri"
        @apply="applyPanelFilters"
        @clear="clearAllFilters"
    >
        <template #default="{ requestLiveApply }">
            <PaymentFilters
                v-model="filterState"
                :status-options="statusOptions"
                :available-expense-years="availableExpenseYears"
                :available-paid-years="availablePaidYears"
                @update:model-value="requestLiveApply"
            />
        </template>
    </FilterPanel>

    <PaymentFormDialog v-model:open="createOpen" :annual-expenses="annualExpenses" />
</template>
