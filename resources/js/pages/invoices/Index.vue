<script setup lang="ts">
/**
 * Invoices — Index page.
 *
 * Tabella paginata (50/pagina). Subbar: search testuale live + bottone
 * "Filtri" (badge counter). I campi filtro (anno, cliente, ritenuta) vivono
 * in [[InvoiceFilters]] dentro [[FilterPanel]], che gestisce la chrome
 * responsive (aside desktop / Sheet mobile) e la semantica apply.
 */
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import {
    PhFunnel,
    PhMagnifyingGlass,
    PhPlus,
    PhUploadSimple,
} from '@phosphor-icons/vue';
import { computed, onUnmounted, ref, watch } from 'vue';
import FilterPanel from '@/components/FilterPanel.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group';
import { DataTablePagination } from '@/components/ui/table';
import InvoiceFilters from '@/pages/invoices/InvoiceFilters.vue';
import type { InvoiceFilterState } from '@/pages/invoices/InvoiceFilters.vue';
import InvoicesTable from '@/pages/invoices/InvoicesTable.vue';
import {
    create as invoicesCreate,
    index as invoicesIndex,
} from '@/routes/invoices';
import type { ClientForPicker, InvoiceListItem, PaginatedList } from '@/types';

const props = defineProps<{
    invoices: PaginatedList<InvoiceListItem>;
    filters: {
        search: string;
        year: number[];
        client_id: number | null;
        withholding: boolean | null;
    };
    availableYears: number[];
    clientsForFilter: ClientForPicker[];
}>();

setLayoutProps({
    pageTitle: 'Fatture',
    pageCrumbs: [{ label: 'Fatture' }],
    subbar: true,
});

// Search reattiva con debounce 250ms.
const searchTerm = ref(props.filters.search);
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(searchTerm, (value) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => {
        applyFilters({ search: value });
    }, 250);
});

onUnmounted(() => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
});

// Stato filtri:
// - `filtersOpen`: visibilità del pannello (aside inline o Sheet mobile)
// - `filterState`: stato locale dei radio/select dei filtri, è la fonte
//   "draft" che diventa committed quando navighiamo. Su desktop applichiamo
//   live al cambio; su mobile aspettiamo "Applica" (UX più decisa).
const filtersOpen = ref(false);

const filterState = ref<InvoiceFilterState>({
    year: props.filters.year,
    client_id: props.filters.client_id,
    withholding: props.filters.withholding,
});

watch(
    () =>
        [
            props.filters.year,
            props.filters.client_id,
            props.filters.withholding,
        ] as const,
    ([year, clientId, withholding]) => {
        filterState.value = { year, client_id: clientId, withholding };
    },
);

const activeFilterCount = computed(() => {
    let n = 0;

    if (props.filters.year.length > 0) {
        n++;
    }

    if (props.filters.client_id !== null) {
        n++;
    }

    if (props.filters.withholding !== null) {
        n++;
    }

    return n;
});

const hasActiveFilters = computed(
    () => activeFilterCount.value > 0 || !!props.filters.search,
);

// Applica i filtri del pannello (desktop live via requestLiveApply, mobile
// via "Applica").
function applyPanelFilters(): void {
    applyFilters({
        year: filterState.value.year,
        client_id: filterState.value.client_id,
        withholding: filterState.value.withholding,
    });
}

function clearAllFilters(): void {
    filterState.value = { year: [], client_id: null, withholding: null };
    applyFilters({ year: [], client_id: null, withholding: null });
}

// Array vuoto → undefined (omette il parametro dalla query).
function nonEmpty<T>(a: T[]): T[] | undefined {
    return a.length > 0 ? a : undefined;
}

function applyFilters(next: {
    search?: string;
    year?: number[];
    client_id?: number | null;
    withholding?: boolean | null;
}): void {
    router.get(
        invoicesIndex().url,
        {
            search: (next.search ?? props.filters.search) || undefined,
            year: nonEmpty(next.year ?? props.filters.year),
            client_id:
                (next.client_id !== undefined
                    ? next.client_id
                    : props.filters.client_id) ?? undefined,
            withholding:
                next.withholding !== undefined
                    ? next.withholding === null
                        ? undefined
                        : next.withholding
                          ? 1
                          : 0
                    : props.filters.withholding === null
                      ? undefined
                      : props.filters.withholding
                        ? 1
                        : 0,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function goToPage(page: number): void {
    router.get(
        invoicesIndex().url,
        {
            search: props.filters.search || undefined,
            year: nonEmpty(props.filters.year),
            client_id: props.filters.client_id ?? undefined,
            withholding:
                props.filters.withholding === null
                    ? undefined
                    : props.filters.withholding
                      ? 1
                      : 0,
            page,
        },
        { preserveState: true, preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Fatture" />

    <Teleport to="#page-topbar-actions" defer>
        <Button as-child variant="outline" size="sm">
            <Link href="/invoices/import">
                <PhUploadSimple :size="14" />
                Importa XML
            </Link>
        </Button>
        <Button as-child size="sm">
            <Link :href="invoicesCreate().url">
                <PhPlus :size="14" weight="bold" />
                Nuova fattura
            </Link>
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
                placeholder="Cerca per numero o cliente…"
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

    <InvoicesTable
        :invoices="invoices.data"
        :origin="[{ label: 'Fatture', href: invoicesIndex().url }]"
    >
        <template #empty>
            <span v-if="hasActiveFilters">
                Nessuna fattura trovata con questi filtri.
            </span>
            <span v-else>
                Nessuna fattura. Creane una dal pulsante in alto.
            </span>
        </template>
    </InvoicesTable>

    <DataTablePagination
        v-if="invoices.last_page > 1"
        :page="invoices.current_page"
        :per-page="invoices.per_page"
        :total="invoices.total"
        @update:page="goToPage"
    />

    <FilterPanel
        v-model:open="filtersOpen"
        :active-count="activeFilterCount"
        title="Filtri"
        @apply="applyPanelFilters"
        @clear="clearAllFilters"
    >
        <template #default="{ requestLiveApply }">
            <InvoiceFilters
                v-model="filterState"
                :available-years="availableYears"
                :clients="clientsForFilter"
                @update:model-value="requestLiveApply"
            />
        </template>
    </FilterPanel>
</template>
