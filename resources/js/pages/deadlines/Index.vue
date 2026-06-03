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
import { PhFunnel, PhMagnifyingGlass } from '@phosphor-icons/vue';
import { useMediaQuery } from '@vueuse/core';
import { computed, onUnmounted, ref, watch } from 'vue';
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
    Sheet,
    SheetClose,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
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
import { index as deadlinesIndex } from '@/routes/deadlines';
import type {
    DeadlineFilterState,
    DeadlineListItem,
    DeadlineStateFilter,
    DeadlineStatus,
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
    };
    availableYears: number[];
    kindOptions: EnumOption[];
}>();

// Toggle stato: segmentatore primario. 'closed' raccoglie completate + non
// dovute ("cose fatte").
const STATE_TABS = [
    { value: 'open', label: 'Aperte' },
    { value: 'closed', label: 'Completate' },
    { value: 'all', label: 'Tutte' },
];

setLayoutProps({
    pageTitle: 'Scadenze',
    pageCrumbs: [{ label: 'Scadenze' }],
    subbar: true,
});

const isMobile = useMediaQuery('(max-width: 767px)');

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

// Status badge: aperta = piena, completata = attenuata, non dovuta = outline.
const STATUS_VARIANT: Record<DeadlineStatus, 'default' | 'secondary' | 'outline'> = {
    open: 'default',
    completed: 'secondary',
    not_due: 'outline',
};

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
});

watch(
    () => [props.filters.kind, props.filters.year] as const,
    ([kind, year]) => {
        filterState.value = {
            kind: (kind as DeadlineFilterState['kind']) ?? null,
            year,
        };
    },
);

// Lo stato vive nel toggle segmentato, non nel pannello: il badge "Filtri"
// conta solo tipo e anno.
const activeFilterCount = computed(() => {
    let n = 0;

    if (props.filters.kind !== null) {
        n++;
    }

    if (props.filters.year !== null) {
        n++;
    }

    return n;
});

const hasActiveFilters = computed(() => activeFilterCount.value > 0 || !!props.filters.search);

function onFilterChangeDesktop(): void {
    applyFilters({
        kind: filterState.value.kind,
        year: filterState.value.year,
    });
}

function applyDraftFiltersMobile(): void {
    applyFilters({
        kind: filterState.value.kind,
        year: filterState.value.year,
    });
    filtersOpen.value = false;
}

function clearAllFilters(): void {
    // Pulisce i filtri del pannello (tipo, anno); lo stato resta nel toggle.
    filterState.value = { ...filterState.value, kind: null, year: null };
    applyFilters({ kind: null, year: null });
}

function applyFilters(next: {
    search?: string;
    state?: DeadlineStateFilter | null;
    kind?: string | null;
    year?: number | null;
}): void {
    router.get(
        deadlinesIndex().url,
        {
            search: (next.search ?? props.filters.search) || undefined,
            state: (next.state !== undefined ? next.state : props.filters.state) ?? undefined,
            kind: (next.kind !== undefined ? next.kind : props.filters.kind) ?? undefined,
            year: (next.year !== undefined ? next.year : props.filters.year) ?? undefined,
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
                    {{ tab.label }}
                </ToggleGroupItem>
            </ToggleGroup>

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
                    <Badge :variant="STATUS_VARIANT[deadline.status]">{{ deadline.status_label }}</Badge>
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

    <!-- DESKTOP: aside push-inline. -->
    <Teleport to="#page-right-sidebar" defer>
        <aside
            v-show="filtersOpen && !isMobile"
            class="hidden w-[260px] shrink-0 overflow-y-auto border-l border-border md:block"
        >
            <div class="p-5">
                <div class="mb-4 flex items-center justify-between">
                    <span class="text-13 font-medium text-foreground">Filtri</span>
                    <Button
                        v-if="activeFilterCount > 0"
                        type="button"
                        variant="link"
                        size="sm"
                        @click="clearAllFilters"
                    >
                        Pulisci
                    </Button>
                </div>
                <DeadlineFilters
                    v-model="filterState"
                    :kind-options="kindOptions"
                    :available-years="availableYears"
                    @update:model-value="onFilterChangeDesktop"
                />
            </div>
        </aside>
    </Teleport>

    <!-- MOBILE: Sheet slide-over (draft + Applica). -->
    <Sheet v-if="isMobile" v-model:open="filtersOpen">
        <SheetContent side="right" class="w-full max-w-sm">
            <SheetHeader>
                <SheetTitle>Filtra scadenze</SheetTitle>
                <SheetDescription>Restringi per stato, tipo o anno.</SheetDescription>
            </SheetHeader>

            <div class="px-6 py-4">
                <DeadlineFilters
                    v-model="filterState"
                    :kind-options="kindOptions"
                    :available-years="availableYears"
                />
            </div>

            <SheetFooter class="flex flex-row items-center justify-between gap-2">
                <Button
                    v-if="activeFilterCount > 0"
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="clearAllFilters"
                >
                    Pulisci filtri
                </Button>
                <span v-else />

                <div class="flex items-center gap-2">
                    <SheetClose as-child>
                        <Button type="button" variant="outline" size="sm">Annulla</Button>
                    </SheetClose>
                    <Button type="button" size="sm" @click="applyDraftFiltersMobile">Applica</Button>
                </div>
            </SheetFooter>
        </SheetContent>
    </Sheet>

    <DeadlineSheet v-model:open="sheetOpen" :deadline="selectedDeadline" />
</template>
