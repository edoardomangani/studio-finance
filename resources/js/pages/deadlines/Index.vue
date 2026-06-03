<script setup lang="ts">
/**
 * Deadlines — Index page.
 *
 * Vista cronologica pluriennale delle scadenze (50/pagina), con l'importo
 * previsto (suggerimento, RB8) calcolato a runtime per ogni riga. Subbar:
 * search live sul nome + toggle stato (segmentatore primario) accanto al
 * bottone "Filtri" (tipo / anno) in aside desktop o Sheet mobile.
 *
 * Click su una riga → side-sheet (registrazione pagamento + reversibilità).
 */
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import {
    PhCheckCircle,
    PhCircleDashed,
    PhFunnel,
    PhListBullets,
    PhMagnifyingGlass,
} from '@phosphor-icons/vue';
import { computed, onUnmounted, ref, watch } from 'vue';
import type { Component } from 'vue';
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
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { formatDateIT, formatEUR } from '@/lib/format';
import DeadlineFilters from '@/pages/deadlines/DeadlineFilters.vue';
import DeadlineSheet from '@/pages/deadlines/DeadlineSheet.vue';
import { DEADLINE_STATUS_META } from '@/pages/deadlines/statusMeta';
import { index as deadlinesIndex } from '@/routes/deadlines';
import type {
    DeadlineFilterState,
    DeadlineListItem,
    DeadlineStateFilter,
    EnumOption,
    PaginatedList,
} from '@/types';

const props = defineProps<{
    deadlines: PaginatedList<DeadlineListItem>;
    filters: {
        search: string;
        state: DeadlineStateFilter | null;
        kind: string | null;
        year: number | null;
        due_year: number | null;
        expense_item_id: number | null;
    };
    availableYears: number[];
    availableDueYears: number[];
    expenseItems: { id: number; name: string }[];
    kindOptions: EnumOption[];
}>();

// Toggle stato: segmentatore primario. 'closed' raccoglie completate + non
// dovute ("cose fatte"). Stesse icone degli stati riga.
const STATE_TABS: { value: string; label: string; icon: Component }[] = [
    { value: 'open', label: 'Aperte', icon: PhCircleDashed },
    { value: 'closed', label: 'Completate', icon: PhCheckCircle },
    { value: 'all', label: 'Tutte', icon: PhListBullets },
];

setLayoutProps({
    pageTitle: 'Scadenze',
    pageCrumbs: [{ label: 'Scadenze' }],
    subbar: true,
});

// Side-sheet: aperto al click su una riga, alimentato dai dati di riga.
const sheetOpen = ref(false);
const selectedDeadline = ref<DeadlineListItem | null>(null);

function openDeadline(deadline: DeadlineListItem): void {
    selectedDeadline.value = deadline;
    sheetOpen.value = true;
}

// Toggle stato (segmentatore primario, accanto al bottone Filtri): 'all'
// quando nessun filtro stato è attivo.
const statusTab = computed(() => props.filters.state ?? 'all');

function setStatus(value: unknown): void {
    const next = value === 'open' || value === 'closed' ? value : null;
    applyFilters({ state: next });
}


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

const filterState = ref<DeadlineFilterState>({
    kind: (props.filters.kind as DeadlineFilterState['kind']) ?? null,
    year: props.filters.year,
    dueYear: props.filters.due_year,
    expenseItemId: props.filters.expense_item_id,
});

watch(
    () => [props.filters.kind, props.filters.year, props.filters.due_year, props.filters.expense_item_id] as const,
    ([kind, year, dueYear, expenseItemId]) => {
        filterState.value = {
            kind: (kind as DeadlineFilterState['kind']) ?? null,
            year,
            dueYear,
            expenseItemId,
        };
    },
);

// Lo stato vive nel toggle segmentato, non nel pannello: il badge "Filtri"
// conta tipo, anni e voce.
const activeFilterCount = computed(() =>
    [
        props.filters.kind,
        props.filters.year,
        props.filters.due_year,
        props.filters.expense_item_id,
    ].filter((v) => v !== null).length,
);

const hasActiveFilters = computed(() => activeFilterCount.value > 0 || !!props.filters.search);

// Applica i filtri del pannello (desktop live via requestLiveApply, mobile
// via "Applica"). Lo stato resta nel toggle.
function applyPanelFilters(): void {
    applyFilters({
        kind: filterState.value.kind,
        year: filterState.value.year,
        due_year: filterState.value.dueYear,
        expense_item_id: filterState.value.expenseItemId,
    });
}

function clearAllFilters(): void {
    // Pulisce i filtri del pannello; lo stato resta nel toggle.
    filterState.value = { kind: null, year: null, dueYear: null, expenseItemId: null };
    applyFilters({ kind: null, year: null, due_year: null, expense_item_id: null });
}

function applyFilters(next: {
    search?: string;
    state?: DeadlineStateFilter | null;
    kind?: string | null;
    year?: number | null;
    due_year?: number | null;
    expense_item_id?: number | null;
}): void {
    router.get(
        deadlinesIndex().url,
        {
            search: (next.search ?? props.filters.search) || undefined,
            state: (next.state !== undefined ? next.state : props.filters.state) ?? undefined,
            kind: (next.kind !== undefined ? next.kind : props.filters.kind) ?? undefined,
            year: (next.year !== undefined ? next.year : props.filters.year) ?? undefined,
            due_year: (next.due_year !== undefined ? next.due_year : props.filters.due_year) ?? undefined,
            expense_item_id: (next.expense_item_id !== undefined ? next.expense_item_id : props.filters.expense_item_id) ?? undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function goToPage(page: number): void {
    router.get(
        deadlinesIndex().url,
        {
            search: props.filters.search || undefined,
            state: props.filters.state ?? undefined,
            kind: props.filters.kind ?? undefined,
            year: props.filters.year ?? undefined,
            due_year: props.filters.due_year ?? undefined,
            expense_item_id: props.filters.expense_item_id ?? undefined,
            page,
        },
        { preserveState: true, preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Scadenze" />

    <Teleport to="#page-topbar-search" defer>
        <InputGroup>
            <InputGroupAddon>
                <PhMagnifyingGlass :size="14" />
            </InputGroupAddon>
            <InputGroupInput
                v-model="searchTerm"
                type="search"
                placeholder="Cerca scadenza…"
            />
        </InputGroup>
    </Teleport>

    <Teleport to="#page-topbar-filters" defer>
        <div class="flex items-center gap-2">
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
            <ToggleGroup
                :model-value="statusTab"
                type="single"
                variant="boxed"
                size="sm"
                @update:model-value="setStatus"
            >
                <ToggleGroupItem
                    v-for="tab in STATE_TABS"
                    :key="tab.value"
                    :value="tab.value"
                >
                    <component :is="tab.icon" :size="14" />
                    {{ tab.label }}
                </ToggleGroupItem>
            </ToggleGroup>
        </div>
    </Teleport>

    <Table boxed>
        <TableHeader>
            <TableRow>
                <TableHead class="w-[100px]">Scadenza</TableHead>
                <TableHead>Descrizione</TableHead>
                <TableHead>Voce di spesa</TableHead>
                <TableHead class="w-[110px]">Tipo</TableHead>
                <TableHead class="w-[70px] text-right">Anno</TableHead>
                <TableHead class="w-[120px] text-right">Previsto</TableHead>
                <TableHead class="w-[120px]">Stato</TableHead>
            </TableRow>
        </TableHeader>
        <TableBody>
            <TableEmpty v-if="deadlines.data.length === 0" :colspan="7">
                <span v-if="hasActiveFilters">Nessuna scadenza trovata con questi filtri.</span>
                <span v-else>Nessuna scadenza. Apri un anno per generarle.</span>
            </TableEmpty>
            <TableRow
                v-for="deadline in deadlines.data"
                v-else
                :key="deadline.id"
                class="cursor-pointer transition-colors hover:bg-muted/40"
                :class="
                    sheetOpen && selectedDeadline?.id === deadline.id
                        ? 'bg-accent-strong/5 shadow-[inset_2px_0_0_0_var(--accent-strong)]'
                        : ''
                "
                @click="openDeadline(deadline)"
            >
                <TableCell class="tabular text-muted-foreground">
                    {{ formatDateIT(deadline.due_at) }}
                </TableCell>
                <TableCell class="text-foreground">
                    <span class="block truncate" :title="deadline.name">{{ deadline.name }}</span>
                </TableCell>
                <TableCell class="text-muted-foreground">
                    <span v-if="deadline.annual_expense_name" class="block truncate" :title="deadline.annual_expense_name">
                        {{ deadline.annual_expense_name }}
                    </span>
                    <span v-else>—</span>
                </TableCell>
                <TableCell class="text-muted-foreground">{{ deadline.kind_label }}</TableCell>
                <TableCell class="tabular text-right text-muted-foreground">{{ deadline.year }}</TableCell>
                <TableCell class="tabular text-right text-foreground">
                    <span v-if="deadline.expected_amount !== null">{{ formatEUR(deadline.expected_amount) }}</span>
                    <span v-else class="text-muted-foreground">—</span>
                </TableCell>
                <TableCell>
                    <Badge :variant="DEADLINE_STATUS_META[deadline.status].variant" class="gap-1">
                        <component :is="DEADLINE_STATUS_META[deadline.status].icon" :size="12" />
                        {{ deadline.status_label }}
                    </Badge>
                </TableCell>
            </TableRow>
        </TableBody>
    </Table>

    <footer
        v-if="deadlines.last_page > 1"
        class="mt-3 flex flex-col items-stretch gap-2 sm:flex-row sm:items-center sm:justify-between"
    >
        <span class="tabular text-xs text-muted-foreground">
            {{ deadlines.from }}–{{ deadlines.to }} di {{ deadlines.total }}
        </span>
        <Pagination
            :total="deadlines.total"
            :items-per-page="deadlines.per_page"
            :page="deadlines.current_page"
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
                        :is-active="item.value === deadlines.current_page"
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
        description="Restringi per tipo o anno."
        @apply="applyPanelFilters"
        @clear="clearAllFilters"
    >
        <template #default="{ requestLiveApply }">
            <DeadlineFilters
                v-model="filterState"
                :kind-options="kindOptions"
                :available-years="availableYears"
                :available-due-years="availableDueYears"
                :expense-items="expenseItems"
                @update:model-value="requestLiveApply"
            />
        </template>
    </FilterPanel>

    <DeadlineSheet v-model:open="sheetOpen" :deadline="selectedDeadline" />
</template>
