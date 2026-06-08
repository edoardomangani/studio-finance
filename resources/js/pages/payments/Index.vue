<script setup lang="ts">
/**
 * Payments — registro di cassa.
 *
 * Vista pluriennale dei soli pagamenti effettuati (`paid`, 50/pagina, RB9): i
 * fatti di cassa. I pianificati si gestiscono dalle Scadenze (sorgente di
 * verità del ciclo di vita), non qui. Subbar: search live sulla descrizione +
 * bottone "Filtri" (anno spesa, anno pagamento). Il pagamento manuale extra-
 * scadenza (F8) si crea dal CTA in alto a destra.
 *
 * Specchia [[deadlines/Index.vue]] per chrome (search + FilterPanel + tabella).
 */
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import { PhFunnel, PhMagnifyingGlass, PhPlus } from '@phosphor-icons/vue';
import { computed, onUnmounted, ref, watch } from 'vue';
import FilterPanel from '@/components/FilterPanel.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import PaymentFilters from '@/pages/payments/PaymentFilters.vue';
import PaymentsTable from '@/pages/payments/PaymentsTable.vue';
import { index as paymentsIndex } from '@/routes/payments';
import type {
    AnnualExpenseForPicker,
    PaginatedList,
    PaymentFilterState,
    PaymentListItem,
} from '@/types';

const props = defineProps<{
    payments: PaginatedList<PaymentListItem>;
    filters: {
        search: string;
        expense_year: number[];
        paid_year: number[];
    };
    availableExpenseYears: number[];
    availablePaidYears: number[];
    annualExpenses: AnnualExpenseForPicker[];
}>();

setLayoutProps({
    pageTitle: 'Pagamenti',
    pageCrumbs: [{ label: 'Pagamenti' }],
    subbar: true,
});

// Tabella (con form crea/modifica e ConfirmDialog) estratta in [[PaymentsTable]];
// la creazione manuale si apre via il metodo esposto dalla tabella.
const table = ref<InstanceType<typeof PaymentsTable> | null>(null);

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
    expenseYear: props.filters.expense_year,
    paidYear: props.filters.paid_year,
});

watch(
    () => [props.filters.expense_year, props.filters.paid_year] as const,
    ([expenseYear, paidYear]) => {
        filterState.value = { expenseYear, paidYear };
    },
);

const activeFilterCount = computed(
    () =>
        [props.filters.expense_year, props.filters.paid_year].filter(
            (v) => v.length > 0,
        ).length,
);

const hasActiveFilters = computed(
    () => activeFilterCount.value > 0 || !!props.filters.search,
);

function applyPanelFilters(): void {
    applyFilters({
        expense_year: filterState.value.expenseYear,
        paid_year: filterState.value.paidYear,
    });
}

function clearAllFilters(): void {
    filterState.value = { expenseYear: [], paidYear: [] };
    applyFilters({ expense_year: [], paid_year: [] });
}

// Array vuoto → undefined (omette il parametro dalla query).
function nonEmpty<T>(a: T[]): T[] | undefined {
    return a.length > 0 ? a : undefined;
}

function applyFilters(next: {
    search?: string;
    expense_year?: number[];
    paid_year?: number[];
}): void {
    router.get(
        paymentsIndex().url,
        {
            search: (next.search ?? props.filters.search) || undefined,
            expense_year: nonEmpty(
                next.expense_year ?? props.filters.expense_year,
            ),
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
        <Button size="sm" @click="table?.openCreate()">
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
                class="tabular ml-1 h-4 min-w-4 px-1 text-2xs"
            >
                {{ activeFilterCount }}
            </Badge>
        </Button>
    </Teleport>

    <PaymentsTable
        ref="table"
        :payments="payments.data"
        :annual-expenses="annualExpenses"
    >
        <template #empty>
            <span v-if="hasActiveFilters">
                Nessun pagamento trovato con questi filtri.
            </span>
            <span v-else>
                Nessun pagamento registrato. Registrane uno dal pulsante in alto
                o dalle scadenze.
            </span>
        </template>
    </PaymentsTable>

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
                :available-expense-years="availableExpenseYears"
                :available-paid-years="availablePaidYears"
                @update:model-value="requestLiveApply"
            />
        </template>
    </FilterPanel>
</template>
