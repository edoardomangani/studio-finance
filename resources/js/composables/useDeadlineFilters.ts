/**
 * useDeadlineFilters — chrome filtri della lista scadenze: search live
 * (debounce 250ms), toggle stato (segmentatore primario), pannello faccette
 * multi-select e paginazione. Tiene insieme stato draft + navigazione Inertia,
 * lasciando alla pagina solo la vista. Lo stato vive nel toggle, non nel
 * pannello: il badge "Filtri" conta le sole faccette attive.
 */
import { router } from '@inertiajs/vue3';
import { computed, onUnmounted, ref, watch } from 'vue';
import type { ComputedRef, Ref } from 'vue';
import { index as deadlinesIndex } from '@/routes/deadlines';
import type { DeadlineFilterState, DeadlineStateFilter } from '@/types';

type DeadlineFilters = {
    search: string;
    state: DeadlineStateFilter | null;
    kind: string[];
    year: number[];
    due_year: number[];
    expense_item_id: number[];
};

type FilterPatch = {
    search?: string;
    state?: DeadlineStateFilter | null;
    kind?: string[];
    year?: number[];
    due_year?: number[];
    expense_item_id?: number[];
};

export function useDeadlineFilters(getFilters: () => DeadlineFilters): {
    searchTerm: Ref<string>;
    statusTab: ComputedRef<string>;
    setStatus: (value: unknown) => void;
    filtersOpen: Ref<boolean>;
    filterState: Ref<DeadlineFilterState>;
    activeFilterCount: ComputedRef<number>;
    hasActiveFilters: ComputedRef<boolean>;
    applyPanelFilters: () => void;
    clearAllFilters: () => void;
    goToPage: (page: number) => void;
} {
    const filters = computed(getFilters);

    // Array vuoto → undefined (omette il parametro dalla query).
    function nonEmpty<T>(a: T[]): T[] | undefined {
        return a.length > 0 ? a : undefined;
    }

    function applyFilters(next: FilterPatch): void {
        router.get(
            deadlinesIndex().url,
            {
                search: (next.search ?? filters.value.search) || undefined,
                state: (next.state !== undefined ? next.state : filters.value.state) ?? undefined,
                kind: nonEmpty(next.kind ?? filters.value.kind),
                year: nonEmpty(next.year ?? filters.value.year),
                due_year: nonEmpty(next.due_year ?? filters.value.due_year),
                expense_item_id: nonEmpty(next.expense_item_id ?? filters.value.expense_item_id),
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    // Toggle stato (segmentatore primario): 'all' quando nessun filtro stato.
    const statusTab = computed(() => filters.value.state ?? 'all');

    function setStatus(value: unknown): void {
        const next = value === 'open' || value === 'closed' ? value : null;
        applyFilters({ state: next });
    }

    // Search reattiva con debounce 250ms.
    const searchTerm = ref(filters.value.search);
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
        kind: filters.value.kind as DeadlineFilterState['kind'],
        year: filters.value.year,
        dueYear: filters.value.due_year,
        expenseItemId: filters.value.expense_item_id,
    });

    // Risincronizza il draft quando i filtri applicati cambiano (navigazione).
    watch(
        () => [filters.value.kind, filters.value.year, filters.value.due_year, filters.value.expense_item_id] as const,
        ([kind, year, dueYear, expenseItemId]) => {
            filterState.value = {
                kind: kind as DeadlineFilterState['kind'],
                year,
                dueYear,
                expenseItemId,
            };
        },
    );

    const activeFilterCount = computed(() =>
        [filters.value.kind, filters.value.year, filters.value.due_year, filters.value.expense_item_id].filter(
            (v) => v.length > 0,
        ).length,
    );

    const hasActiveFilters = computed(() => activeFilterCount.value > 0 || !!filters.value.search);

    // Applica i filtri del pannello (desktop live, mobile via "Applica"). Lo
    // stato resta nel toggle.
    function applyPanelFilters(): void {
        applyFilters({
            kind: filterState.value.kind,
            year: filterState.value.year,
            due_year: filterState.value.dueYear,
            expense_item_id: filterState.value.expenseItemId,
        });
    }

    function clearAllFilters(): void {
        filterState.value = { kind: [], year: [], dueYear: [], expenseItemId: [] };
        applyFilters({ kind: [], year: [], due_year: [], expense_item_id: [] });
    }

    function goToPage(page: number): void {
        router.get(
            deadlinesIndex().url,
            {
                search: filters.value.search || undefined,
                state: filters.value.state ?? undefined,
                kind: nonEmpty(filters.value.kind),
                year: nonEmpty(filters.value.year),
                due_year: nonEmpty(filters.value.due_year),
                expense_item_id: nonEmpty(filters.value.expense_item_id),
                page,
            },
            { preserveState: true, preserveScroll: true },
        );
    }

    return {
        searchTerm,
        statusTab,
        setStatus,
        filtersOpen,
        filterState,
        activeFilterCount,
        hasActiveFilters,
        applyPanelFilters,
        clearAllFilters,
        goToPage,
    };
}
